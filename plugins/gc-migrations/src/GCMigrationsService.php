<?php

declare(strict_types=1);

namespace GCMigrations;

use GoniCore\Core\Database\Connection;

/**
 * Universal content importer.
 *
 * Reads posts / pages / categories from ANY external database and writes them
 * into the local GoniCore content tables. Three source modes:
 *
 *   • gonicore  — another GoniCore site (auto-mapped)
 *   • wordpress — a WordPress site (wp_posts / wp_terms…, auto-mapped)
 *   • custom    — ANY other / unknown engine: the admin picks a source table
 *                 and maps its columns onto title/content/slug/excerpt/status.
 *
 * SECURITY
 * --------
 * WRITES are restricted to a fixed allow-list (categories, posts,
 * post_translations) — users, settings, roles and every other system table are
 * never written. READS may come from any source table, but for the custom mode
 * the chosen table and every mapped column are validated against the source's
 * own information_schema before use, so an attacker cannot inject SQL through
 * the mapping. All identifiers are back-tick quoted.
 *
 * Cross-table references are neutralised: imported posts are re-authored to the
 * importing admin; category / parent references are remapped to locally-created
 * rows or set NULL.
 */
final class GCMigrationsService
{
    /**
     * Allow-list for WRITES: local table => columns that may be written.
     * Identity / FK columns are excluded and set programmatically.
     *
     * @var array<string, list<string>>
     */
    private const WRITE_TABLES = [
        'categories' => ['name', 'slug', 'parent_id'],
        'posts' => [
            'type', 'template', 'title', 'slug', 'content', 'excerpt',
            'featured_image', 'use_builder', 'builder_data', 'status',
            'created_at', 'updated_at',
        ],
        'post_translations' => [
            'language_code', 'title', 'slug', 'content', 'status',
            'created_at', 'updated_at',
        ],
    ];

    public function __construct(private readonly Connection $local) {}

    // ── Source connection ───────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $cfg
     */
    public function connect(array $cfg): Connection
    {
        $host = trim((string) ($cfg['host'] ?? '127.0.0.1'));
        $this->assertHostAllowed($host);

        return Connection::fromConfig([
            'driver'   => 'mysql',
            'host'     => $host,
            'port'     => (int) ($cfg['port'] ?? 3306),
            'dbname'   => trim((string) ($cfg['dbname'] ?? '')),
            'username' => trim((string) ($cfg['username'] ?? '')),
            'password' => (string) ($cfg['password'] ?? ''),
            'charset'  => 'utf8mb4',
        ]);
    }

    /**
     * SSRF guard for the source host. Loopback / private LAN are allowed (the
     * intended use); link-local / cloud-metadata ranges are blocked. Set
     * GC_MIGRATIONS_ALLOWED_HOSTS in .env for a strict deny-by-default allowlist.
     */
    private function assertHostAllowed(string $host): void
    {
        if ($host === '') {
            throw new \RuntimeException('Database host is required.');
        }

        $allow = (string) (getenv('GC_MIGRATIONS_ALLOWED_HOSTS') ?: '');
        if ($allow !== '') {
            $list = array_filter(array_map('trim', explode(',', $allow)));
            if (!in_array($host, $list, true)) {
                throw new \RuntimeException("Host \"{$host}\" is not in GC_MIGRATIONS_ALLOWED_HOSTS.");
            }
            return;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $ips = gethostbynamel($host) ?: [];
            $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
            foreach ($aaaa as $r) {
                if (!empty($r['ipv6'])) $ips[] = (string) $r['ipv6'];
            }
            // gethostbynamel can momentarily fail for 'localhost' on some Windows
            // setups even though PDO connects fine — don't hard-block loopback.
            if ($ips === [] && in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
                $ips = ['127.0.0.1'];
            }
        }

        if ($ips === []) {
            throw new \RuntimeException("Cannot resolve database host: {$host}");
        }

        foreach ($ips as $ip) {
            if (in_array($ip, ['0.0.0.0', '::'], true)
                || str_starts_with($ip, '169.254.')
                || stripos($ip, 'fe80:') === 0
                || stripos($ip, 'fd00:') === 0) {
                throw new \RuntimeException("Refusing to connect to reserved address {$ip}.");
            }
        }
    }

    /** Sanitised table-name prefix (e.g. "wp_"). */
    private function prefix(array $cfg): string
    {
        $p = (string) ($cfg['prefix'] ?? '');
        return preg_replace('/[^A-Za-z0-9_]/', '', $p) ?? '';
    }

    // ── Engine detection & schema introspection ──────────────────────────────

    /**
     * Detect the source engine from its schema.
     *
     * @return 'gonicore'|'wordpress'|'unknown'
     */
    public function detectEngine(Connection $src, string $prefix): string
    {
        $postsCols = $this->columnsOf($src, $prefix . 'posts');
        if ($postsCols !== []) {
            if (in_array('post_title', $postsCols, true) && in_array('post_type', $postsCols, true)) {
                return 'wordpress';
            }
            if (in_array('title', $postsCols, true) && in_array('type', $postsCols, true)) {
                return 'gonicore';
            }
        }
        // WordPress with a non-empty prefix where 'posts' wasn't found at the
        // bare name — try the common wp_ default as a hint is out of scope; the
        // admin can set the prefix explicitly.
        return 'unknown';
    }

    /**
     * Full schema map of the source DB: table => [column, …].
     *
     * @return array<string, list<string>>
     */
    public function schema(Connection $src): array
    {
        $rows = $src->query(
            "SELECT TABLE_NAME, COLUMN_NAME
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
              ORDER BY TABLE_NAME, ORDINAL_POSITION"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['TABLE_NAME']][] = (string) $r['COLUMN_NAME'];
        }
        return $out;
    }

    // ── Validation / preview ─────────────────────────────────────────────────

    /**
     * Validate the connection and return a preview of what can be imported.
     *
     * @param array<string,mixed> $cfg
     * @return array{ok:bool, message:string, engine?:string, counts?:array<string,int>, schema?:array<string,list<string>>}
     */
    public function preview(array $cfg): array
    {
        foreach (['host', 'dbname', 'username'] as $req) {
            if (trim((string) ($cfg[$req] ?? '')) === '') {
                return ['ok' => false, 'message' => "Missing required field: {$req}."];
            }
        }

        try {
            $src    = $this->connect($cfg);
            $prefix = $this->prefix($cfg);
            $schema = $this->schema($src);

            $engine = (string) ($cfg['engine'] ?? 'auto');
            if ($engine === 'auto' || $engine === '') {
                $engine = $this->detectEngine($src, $prefix);
            }

            $counts = match ($engine) {
                'gonicore'  => $this->countGoniCore($src, $prefix),
                'wordpress' => $this->countWordPress($src, $prefix),
                'custom'    => $this->countCustom($src, $cfg, $schema),
                default     => [],
            };

            $msg = match ($engine) {
                'gonicore'  => 'Connected — detected a GoniCore database.',
                'wordpress' => 'Connected — detected a WordPress database.',
                'custom'    => 'Connected — custom mapping.',
                default     => 'Connected, but the engine could not be auto-detected. Choose “Custom mapping” and map the columns manually.',
            };

            return [
                'ok'      => true,
                'message' => $msg,
                'engine'  => $engine,
                'counts'  => $counts,
                'schema'  => $schema,
            ];
        } catch (\GoniCore\Core\Database\DatabaseException $e) {
            error_log('[gc-migrations] source connection failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => $this->friendlyDbError($e->getMessage())];
        } catch (\Throwable $e) {
            // Our own guards (SSRF, unresolvable host) carry safe messages.
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** Turn a raw PDO/driver error into an actionable, safe admin message. */
    private function friendlyDbError(string $raw): string
    {
        $l = strtolower($raw);
        if (str_contains($l, 'access denied')) {
            return 'Access denied — the username or password is wrong for this database.';
        }
        if (str_contains($l, 'unknown database')) {
            return 'That database name does not exist on the source server.';
        }
        if (str_contains($l, 'unknown mysql server host') || str_contains($l, 'getaddrinfo')) {
            return 'The host name could not be resolved. Check the host.';
        }
        if (str_contains($l, "can't connect") || str_contains($l, 'connection refused')
            || str_contains($l, 'timed out') || str_contains($l, 'actively refused')) {
            return 'Could not reach the database server. Check the host, port, and that MySQL is running and accepts TCP connections.';
        }
        return 'Could not connect to the source database. Check the host, port, username, password, and that the server is reachable.';
    }

    // ── Counts per engine ─────────────────────────────────────────────────────

    /** @return array<string,int> */
    private function countGoniCore(Connection $src, string $prefix): array
    {
        if (!$this->sourceTableExists($src, $prefix . 'posts')) {
            throw new \RuntimeException('No "' . $prefix . 'posts" table found for the GoniCore engine.');
        }
        return [
            'posts'        => $this->sourceCount($src, $prefix . 'posts', "type = 'post'"),
            'pages'        => $this->sourceCount($src, $prefix . 'posts', "type = 'page'"),
            'categories'   => $this->sourceTableExists($src, $prefix . 'categories')
                                ? $this->sourceCount($src, $prefix . 'categories') : 0,
            'translations' => $this->sourceTableExists($src, $prefix . 'post_translations')
                                ? $this->sourceCount($src, $prefix . 'post_translations') : 0,
        ];
    }

    /** @return array<string,int> */
    private function countWordPress(Connection $src, string $prefix): array
    {
        if (!$this->sourceTableExists($src, $prefix . 'posts')) {
            throw new \RuntimeException('No "' . $prefix . 'posts" table found. Set the correct table prefix (e.g. wp_).');
        }
        $notTrash = "post_status NOT IN ('trash','auto-draft','inherit')";
        return [
            'posts'        => $this->sourceCount($src, $prefix . 'posts', "post_type='post' AND {$notTrash}"),
            'pages'        => $this->sourceCount($src, $prefix . 'posts', "post_type='page' AND {$notTrash}"),
            'categories'   => $this->sourceTableExists($src, $prefix . 'term_taxonomy')
                                ? $this->sourceCount($src, $prefix . 'term_taxonomy', "taxonomy='category'") : 0,
            'translations' => 0,
        ];
    }

    /**
     * @param array<string,mixed>             $cfg
     * @param array<string,list<string>>      $schema
     * @return array<string,int>
     */
    private function countCustom(Connection $src, array $cfg, array $schema): array
    {
        $table = (string) ($cfg['src_table'] ?? '');
        if ($table === '' || !isset($schema[$table])) {
            return ['posts' => 0, 'pages' => 0, 'categories' => 0, 'translations' => 0];
        }
        $total = $this->sourceCount($src, $table);
        return ['posts' => $total, 'pages' => 0, 'categories' => 0, 'translations' => 0];
    }

    // ── Import ────────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $cfg
     * @param array<string,mixed> $opts  importPosts, importPages, importCategories,
     *                                   importTranslations, duplicateMode ('skip'|'rename')
     * @return array{ok:bool, message:string, report?:array<string,int>}
     */
    public function import(array $cfg, array $opts, int $importingUserId): array
    {
        if ($importingUserId <= 0) {
            return ['ok' => false, 'message' => 'Could not determine the importing user.'];
        }

        try {
            $src    = $this->connect($cfg);
            $prefix = $this->prefix($cfg);
            $schema = $this->schema($src);

            $engine = (string) ($cfg['engine'] ?? 'auto');
            if ($engine === 'auto' || $engine === '') {
                $engine = $this->detectEngine($src, $prefix);
            }
        } catch (\GoniCore\Core\Database\DatabaseException $e) {
            error_log('[gc-migrations] import connect failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => $this->friendlyDbError($e->getMessage())];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $dupMode = ($opts['duplicateMode'] ?? 'skip') === 'rename' ? 'rename' : 'skip';
        $report  = ['categories' => 0, 'posts' => 0, 'pages' => 0, 'translations' => 0, 'skipped' => 0];

        try {
            $this->local->transact(function () use ($engine, $src, $prefix, $cfg, $schema, $opts, $dupMode, $importingUserId, &$report): void {
                match ($engine) {
                    'gonicore'  => $this->importGoniCore($src, $prefix, $opts, $dupMode, $importingUserId, $report),
                    'wordpress' => $this->importWordPress($src, $prefix, $opts, $dupMode, $importingUserId, $report),
                    'custom'    => $this->importCustom($src, $cfg, $schema, $opts, $dupMode, $importingUserId, $report),
                    default     => throw new \RuntimeException('Unknown source engine — choose Custom mapping and map the columns.'),
                };
            });
        } catch (\Throwable $e) {
            error_log('[gc-migrations] import failed and was rolled back: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Import failed and was rolled back: ' . $e->getMessage()];
        }

        return ['ok' => true, 'message' => 'Import complete.', 'report' => $report];
    }

    // ── GoniCore importer ─────────────────────────────────────────────────────

    private function importGoniCore(Connection $src, string $prefix, array $opts, string $dupMode, int $uid, array &$report): void
    {
        $catMap = [];
        if (!empty($opts['importCategories']) && $this->sourceTableExists($src, $prefix . 'categories')) {
            $catMap = $this->importCategoriesGeneric(
                $src,
                'SELECT id, name, slug, parent_id FROM ' . $this->q($prefix . 'categories'),
                static fn(array $r) => [
                    'srcId'    => (int) $r['id'],
                    'name'     => (string) ($r['name'] ?? ''),
                    'slug'     => (string) ($r['slug'] ?? ''),
                    'srcParent'=> !empty($r['parent_id']) ? (int) $r['parent_id'] : 0,
                ],
                $report
            );
        }

        $types = [];
        if (!empty($opts['importPosts'])) $types[] = 'post';
        if (!empty($opts['importPages'])) $types[] = 'page';

        $postMap = [];
        if ($types !== []) {
            $cols  = $this->copyableColumns($src, $prefix . 'posts', 'posts');
            $place = implode(', ', array_fill(0, count($types), '?'));
            $rows  = $src->query('SELECT * FROM ' . $this->q($prefix . 'posts') . ' WHERE type IN (' . $place . ')', $types);

            $srcParents = [];
            foreach ($rows as $row) {
                $data = $this->pick($row, $cols);
                $slug = $this->finalSlug((string) ($data['slug'] ?? ''), (string) ($data['title'] ?? ''), (int) ($row['id'] ?? 0), $dupMode, $report);
                if ($slug === null) continue;
                $data['slug']        = $slug;
                $data['author_id']   = $uid;
                $data['category_id'] = (!empty($row['category_id']) && isset($catMap[(int) $row['category_id']])) ? $catMap[(int) $row['category_id']] : null;

                $localId = (int) $this->insert('posts', $data);
                $postMap[(int) $row['id']] = $localId;
                if (!empty($row['parent_id'])) $srcParents[$localId] = (int) $row['parent_id'];
                (($row['type'] ?? 'post') === 'page') ? $report['pages']++ : $report['posts']++;
            }
            $this->remapParents('posts', $srcParents, $postMap);
        }

        if (!empty($opts['importTranslations']) && $postMap !== []
            && $this->sourceTableExists($src, $prefix . 'post_translations')
            && $this->localTableExists('post_translations')) {
            $cols = $this->copyableColumns($src, $prefix . 'post_translations', 'post_translations');
            foreach ($src->query('SELECT * FROM ' . $this->q($prefix . 'post_translations')) as $row) {
                $srcPostId = (int) ($row['post_id'] ?? 0);
                if (!isset($postMap[$srcPostId])) continue;
                $lang = (string) ($row['language_code'] ?? '');
                if ($lang === '') continue;
                if ($this->local->queryOne('SELECT id FROM `post_translations` WHERE post_id=? AND language_code=? LIMIT 1', [$postMap[$srcPostId], $lang]) !== null) continue;
                $data = $this->pick($row, $cols);
                $data['post_id'] = $postMap[$srcPostId];
                $this->insert('post_translations', $data);
                $report['translations']++;
            }
        }
    }

    // ── WordPress importer ─────────────────────────────────────────────────────

    private function importWordPress(Connection $src, string $prefix, array $opts, string $dupMode, int $uid, array &$report): void
    {
        // 1. Categories (terms + term_taxonomy).
        $catMap = [];      // wp term_id   => local category id
        $ttToLocalCat = []; // term_taxonomy_id => local category id
        if (!empty($opts['importCategories'])
            && $this->sourceTableExists($src, $prefix . 'term_taxonomy')
            && $this->sourceTableExists($src, $prefix . 'terms')) {

            $rows = $src->query(
                'SELECT t.term_id, t.name, t.slug, tt.parent, tt.term_taxonomy_id
                   FROM ' . $this->q($prefix . 'terms') . ' t
                   JOIN ' . $this->q($prefix . 'term_taxonomy') . " tt ON t.term_id = tt.term_id
                  WHERE tt.taxonomy = 'category'"
            );
            $srcParents = [];
            $ttToTerm   = [];
            foreach ($rows as $rawR) {
                $r = array_change_key_case($rawR, CASE_LOWER);
                $ttToTerm[(int) $r['term_taxonomy_id']] = (int) $r['term_id'];
                $slug = (string) ($r['slug'] ?? '');
                if ($slug === '') continue;
                $existing = $this->local->queryOne('SELECT id FROM `categories` WHERE slug=? LIMIT 1', [$slug]);
                if ($existing !== null) {
                    $catMap[(int) $r['term_id']] = (int) $existing['id'];
                } else {
                    $localId = (int) $this->insert('categories', ['name' => (string) ($r['name'] ?? $slug), 'slug' => $slug, 'parent_id' => null]);
                    $catMap[(int) $r['term_id']] = $localId;
                    if (!empty($r['parent'])) $srcParents[$localId] = (int) $r['parent'];
                    $report['categories']++;
                }
            }
            $this->remapParents('categories', $srcParents, $catMap);
            foreach ($ttToTerm as $ttId => $termId) {
                if (isset($catMap[$termId])) $ttToLocalCat[$ttId] = $catMap[$termId];
            }
        }

        // Post -> first category map (object_id => local category id).
        $objToCat = [];
        if ($ttToLocalCat !== [] && $this->sourceTableExists($src, $prefix . 'term_relationships')) {
            foreach ($src->query('SELECT object_id, term_taxonomy_id FROM ' . $this->q($prefix . 'term_relationships')) as $rawRel) {
                $rel  = array_change_key_case($rawRel, CASE_LOWER);
                $obj  = (int) $rel['object_id'];
                $ttId = (int) $rel['term_taxonomy_id'];
                if (!isset($objToCat[$obj]) && isset($ttToLocalCat[$ttId])) {
                    $objToCat[$obj] = $ttToLocalCat[$ttId];
                }
            }
        }

        // 2. Posts / pages.
        $types = [];
        if (!empty($opts['importPosts'])) $types[] = 'post';
        if (!empty($opts['importPages'])) $types[] = 'page';
        if ($types === []) return;

        $hasLocal = fn(string $c): bool => $this->localColumnExists('posts', $c);
        $place = implode(', ', array_fill(0, count($types), '?'));
        $rows  = $src->query(
            'SELECT * FROM ' . $this->q($prefix . 'posts')
            . ' WHERE post_type IN (' . $place . ") AND post_status NOT IN ('trash','auto-draft','inherit')",
            $types
        );

        $postMap = [];
        $srcParents = [];
        foreach ($rows as $raw) {
            // WordPress names its PK `ID`; some exports lower-case it. Be
            // case-insensitive so ANY source variant works without warnings.
            $row   = array_change_key_case($raw, CASE_LOWER);
            $srcId = (int) ($row['id'] ?? 0);
            $title = (string) ($row['post_title'] ?? '');
            $slug  = $this->finalSlug((string) ($row['post_name'] ?? ''), $title, $srcId, $dupMode, $report);
            if ($slug === null) continue;

            $data = [
                'title'   => $title,
                'content' => (string) ($row['post_content'] ?? ''),
                'slug'    => $slug,
                'status'  => ((string) ($row['post_status'] ?? '') === 'publish') ? 'published' : 'draft',
                'type'    => ((string) ($row['post_type'] ?? 'post') === 'page') ? 'page' : 'post',
            ];
            if ($hasLocal('excerpt'))    $data['excerpt']    = (string) ($row['post_excerpt'] ?? '');
            if ($hasLocal('created_at') && !empty($row['post_date']))     $data['created_at'] = (string) $row['post_date'];
            if ($hasLocal('updated_at') && !empty($row['post_modified'])) $data['updated_at'] = (string) $row['post_modified'];
            $data['author_id']   = $uid;
            $data['category_id'] = ($srcId > 0 && isset($objToCat[$srcId])) ? $objToCat[$srcId] : null;

            $localId = (int) $this->insert('posts', $data);
            if ($srcId > 0) $postMap[$srcId] = $localId;
            if (!empty($row['post_parent'])) $srcParents[$localId] = (int) $row['post_parent'];
            ($data['type'] === 'page') ? $report['pages']++ : $report['posts']++;
        }
        $this->remapParents('posts', $srcParents, $postMap);
    }

    // ── Custom mapping importer (any/unknown engine) ───────────────────────────

    /**
     * @param array<string,mixed>        $cfg
     * @param array<string,list<string>> $schema
     */
    private function importCustom(Connection $src, array $cfg, array $schema, array $opts, string $dupMode, int $uid, array &$report): void
    {
        $table = (string) ($cfg['src_table'] ?? '');
        if ($table === '' || !isset($schema[$table])) {
            throw new \RuntimeException('Choose a valid source table for custom mapping.');
        }
        $cols = $schema[$table];

        // Validate every mapped column actually exists in the chosen table.
        $col = function (string $key) use ($cfg, $cols): string {
            $name = trim((string) ($cfg[$key] ?? ''));
            if ($name === '') return '';
            if (!in_array($name, $cols, true)) {
                throw new \RuntimeException("Mapped column \"{$name}\" does not exist in the selected table.");
            }
            return $name;
        };

        $cTitle   = $col('map_title');
        $cContent = $col('map_content');
        $cSlug    = $col('map_slug');
        $cExcerpt = $col('map_excerpt');
        $cStatus  = $col('map_status');
        $cType    = $col('map_type');
        $cDate    = $col('map_created');

        if ($cTitle === '') {
            throw new \RuntimeException('Map at least the Title column for custom mapping.');
        }

        $publishedValue = strtolower(trim((string) ($cfg['status_published'] ?? '')));
        $defaultType    = ((string) ($cfg['default_type'] ?? 'post') === 'page') ? 'page' : 'post';

        $wantPosts = !empty($opts['importPosts']);
        $wantPages = !empty($opts['importPages']);
        $hasLocal  = fn(string $c): bool => $this->localColumnExists('posts', $c);

        $i = 0;
        foreach ($src->query('SELECT * FROM ' . $this->q($table)) as $row) {
            $title = trim((string) ($row[$cTitle] ?? ''));
            if ($title === '') { continue; }

            // Type
            $type = $defaultType;
            if ($cType !== '') {
                $type = (strtolower((string) ($row[$cType] ?? '')) === 'page') ? 'page' : 'post';
            }
            if ($type === 'post' && !$wantPosts) continue;
            if ($type === 'page' && !$wantPages) continue;

            // Status
            $status = 'published';
            if ($cStatus !== '') {
                $val = strtolower(trim((string) ($row[$cStatus] ?? '')));
                if ($publishedValue !== '') {
                    $status = ($val === $publishedValue) ? 'published' : 'draft';
                } else {
                    $status = in_array($val, ['publish', 'published', '1', 'active', 'live', 'public'], true) ? 'published' : 'draft';
                }
            }

            $slug = $this->finalSlug($cSlug !== '' ? (string) ($row[$cSlug] ?? '') : '', $title, $i++, $dupMode, $report);
            if ($slug === null) continue;

            $data = [
                'title'   => $title,
                'content' => $cContent !== '' ? (string) ($row[$cContent] ?? '') : '',
                'slug'    => $slug,
                'status'  => $status,
                'type'    => $type,
            ];
            if ($cExcerpt !== '' && $hasLocal('excerpt')) $data['excerpt'] = (string) ($row[$cExcerpt] ?? '');
            if ($cDate !== '' && $hasLocal('created_at') && !empty($row[$cDate])) $data['created_at'] = (string) $row[$cDate];
            $data['author_id']   = $uid;
            $data['category_id'] = null;

            $this->insert('posts', $data);
            ($type === 'page') ? $report['pages']++ : $report['posts']++;
        }
    }

    // ── Shared helpers ─────────────────────────────────────────────────────────

    /**
     * Generic category import from a row mapper. Returns srcId => localId.
     *
     * @param callable(array<string,mixed>):array{srcId:int,name:string,slug:string,srcParent:int} $mapper
     * @return array<int,int>
     */
    private function importCategoriesGeneric(Connection $src, string $sql, callable $mapper, array &$report): array
    {
        $map = $srcParents = [];
        foreach ($src->query($sql) as $row) {
            $m = $mapper($row);
            if ($m['slug'] === '') continue;
            $existing = $this->local->queryOne('SELECT id FROM `categories` WHERE slug=? LIMIT 1', [$m['slug']]);
            if ($existing !== null) { $map[$m['srcId']] = (int) $existing['id']; continue; }
            $localId = (int) $this->insert('categories', ['name' => $m['name'] !== '' ? $m['name'] : $m['slug'], 'slug' => $m['slug'], 'parent_id' => null]);
            $map[$m['srcId']] = $localId;
            if ($m['srcParent'] > 0) $srcParents[$localId] = $m['srcParent'];
            $report['categories']++;
        }
        $this->remapParents('categories', $srcParents, $map);
        return $map;
    }

    /**
     * Resolve a final unique slug for a post, applying duplicate handling.
     * Returns null when the row should be SKIPPED (duplicate + skip mode).
     *
     * @param array<string,int> $report  (skipped counter is bumped)
     */
    private function finalSlug(string $rawSlug, string $title, int $seed, string $dupMode, array &$report): ?string
    {
        $slug = $this->slugify($rawSlug !== '' ? $rawSlug : $title);
        if ($slug === '') $slug = 'post-' . substr(md5((string) $seed), 0, 8);

        $dup = $this->local->queryOne('SELECT id FROM `posts` WHERE slug=? LIMIT 1', [$slug]);
        if ($dup !== null) {
            if ($dupMode === 'skip') { $report['skipped']++; return null; }
            $slug = $this->uniqueSlug($slug);
        }
        return $slug;
    }

    private function slugify(string $s): string
    {
        $s = strip_tags($s);
        if (function_exists('iconv')) {
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($t !== false) $s = $t;
        }
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/u', '-', $s) ?? '';
        return trim($s, '-');
    }

    /** @param array<int,int> $srcParents localId => srcParentId; @param array<int,int> $map srcId => localId */
    private function remapParents(string $table, array $srcParents, array $map): void
    {
        if ($srcParents === [] || !$this->localColumnExists($table, 'parent_id')) return;
        foreach ($srcParents as $localId => $srcParentId) {
            if (isset($map[$srcParentId])) {
                $this->local->execute("UPDATE `{$table}` SET parent_id = ? WHERE id = ?", [$map[$srcParentId], $localId]);
            }
        }
    }

    /** @return list<string> */
    private function copyableColumns(Connection $src, string $sourceTable, string $localTable): array
    {
        $allowed = self::WRITE_TABLES[$localTable] ?? [];
        $srcCols = $this->columnsOf($src, $sourceTable);
        $locCols = $this->columnsOf($this->local, $localTable);
        return array_values(array_filter($allowed, static fn(string $c): bool => in_array($c, $srcCols, true) && in_array($c, $locCols, true)));
    }

    /**
     * @param array<string,mixed> $row
     * @param list<string>        $cols
     * @return array<string,mixed>
     */
    private function pick(array $row, array $cols): array
    {
        $out = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $row)) $out[$c] = $row[$c];
        }
        return $out;
    }

    /**
     * Insert into a LOCAL allow-listed table only.
     *
     * @param array<string,mixed> $data
     */
    private function insert(string $table, array $data): string
    {
        if (!array_key_exists($table, self::WRITE_TABLES)) {
            throw new \RuntimeException("Refusing to write to non-whitelisted table: {$table}");
        }
        if ($data === []) {
            throw new \RuntimeException("No columns to insert for table: {$table}");
        }
        $columns      = implode(', ', array_map(fn(string $c) => '`' . str_replace('`', '', $c) . '`', array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->local->execute("INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})", array_values($data));
        return $this->local->lastInsertId();
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base; $i = 1;
        while ($this->local->queryOne('SELECT id FROM `posts` WHERE slug=? LIMIT 1', [$slug]) !== null) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** @return list<string> */
    private function columnsOf(Connection $conn, string $table): array
    {
        $rows = $conn->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
        return array_map(static fn(array $r): string => (string) $r['COLUMN_NAME'], $rows);
    }

    private function sourceTableExists(Connection $src, string $table): bool
    {
        return $src->query(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1",
            [$table]
        ) !== [];
    }

    private function localTableExists(string $table): bool
    {
        return $this->local->query(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1",
            [$table]
        ) !== [];
    }

    private function localColumnExists(string $table, string $column): bool
    {
        return in_array($column, $this->columnsOf($this->local, $table), true);
    }

    private function sourceCount(Connection $src, string $table, string $where = ''): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM ' . $this->q($table);
        if ($where !== '') $sql .= ' WHERE ' . $where;
        $row = $src->queryOne($sql);
        return (int) ($row['c'] ?? 0);
    }

    /** Quote a table identifier (name already validated against the schema). */
    private function q(string $table): string
    {
        return '`' . str_replace('`', '', $table) . '`';
    }
}
