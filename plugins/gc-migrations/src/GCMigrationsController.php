<?php

declare(strict_types=1);

namespace GCMigrations;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;
use GoniCore\Modules\Role\AuthorizationService;

final class GCMigrationsController
{
    /** Session key holding the last validated source connection (incl. password). */
    private const SRC_KEY = 'gcm_src';

    public function __construct(
        private readonly GCMigrationsService  $svc,
        private readonly QueryBuilder         $qb,
        private readonly LoginService         $auth,
        private readonly AuthorizationService $authz,
        private readonly HookManager          $hooks,
        private readonly SessionManager       $session,
        private readonly LanguageService      $langService,
        private readonly LanguageRepository   $langRepo,
        private readonly NotificationService  $notifications,
        private readonly string               $siteName = 'GoniCore',
    ) {}

    public function index(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage($r, 'index', [
            'base'    => $r->basePath(),
            'preview' => null,
            'report'  => null,
            'message' => null,
            'ok'      => null,
            'engine'  => 'auto',
            'schema'  => null,
            'form'    => $this->defaults(),
        ]);
    }

    /** Test the source connection and show a preview of importable content. */
    public function preview(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $cfg = $this->readConfig($r);
        $res = $this->svc->preview($cfg);

        // Remember the validated connection (incl. password) so the import step
        // doesn't need the password re-typed — it's never echoed back to the page.
        if (($res['ok'] ?? false) === true) {
            $this->session->put(self::SRC_KEY, $cfg);
        }

        return $this->renderPage($r, 'index', [
            'base'    => $r->basePath(),
            'preview' => $res['counts'] ?? null,
            'report'  => null,
            'message' => $res['message'],
            'ok'      => $res['ok'],
            'engine'  => $res['engine'] ?? ($cfg['engine'] ?? 'auto'),
            'schema'  => $res['schema'] ?? null,
            'form'    => $cfg + ['password' => ''],
        ]);
    }

    /** Run the import. */
    public function import(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $cfg  = $this->readConfig($r);
        $opts = [
            'importPosts'        => (string) $r->post('import_posts', '')        === '1',
            'importPages'        => (string) $r->post('import_pages', '')        === '1',
            'importCategories'   => (string) $r->post('import_categories', '')   === '1',
            'importTranslations' => (string) $r->post('import_translations', '') === '1',
            'duplicateMode'      => (string) $r->post('duplicate_mode', 'skip'),
        ];

        $fail = function (string $msg) use ($r, $cfg): Response {
            return $this->renderPage($r, 'index', [
                'base' => $r->basePath(), 'preview' => null, 'report' => null,
                'message' => $msg, 'ok' => false,
                'engine' => $cfg['engine'] ?? 'auto', 'schema' => null,
                'form' => $cfg + ['password' => ''],
            ]);
        };

        if (!$opts['importPosts'] && !$opts['importPages']) {
            return $fail('Select at least one of Posts or Pages to import.');
        }

        // Imported content is stored and rendered as raw HTML — require the admin
        // to confirm the source is trusted (fail-closed).
        if ((string) $r->post('confirm_trust', '') !== '1') {
            return $fail('You must confirm that the source database is trusted before importing (content is imported as raw HTML).');
        }

        $userId = (int) ($this->auth->currentUserId() ?? 0);
        $res    = $this->svc->import($cfg, $opts, $userId);

        if (($res['ok'] ?? false) === true) {
            $report   = $res['report'] ?? [];
            $imported = (int) ($report['posts'] ?? 0) + (int) ($report['pages'] ?? 0);
            $summary  = sprintf(
                '%d posts, %d pages and %d categories imported via GC Migrations.',
                (int) ($report['posts'] ?? 0),
                (int) ($report['pages'] ?? 0),
                (int) ($report['categories'] ?? 0),
            );

            // The importer writes posts with a raw INSERT (bypassing the normal
            // post-create path), so notifications would never fire — raise a
            // single summary notification here instead of one per imported row.
            try { $this->notifications->system('Content imported', $summary, null); } catch (\Throwable) {}

            // Admin email — same toggle as a normal new post.
            if ($imported > 0 && function_exists('gc_setting') && (string) gc_setting('notify_post_new', '1') === '1') {
                $this->hooks->emit(
                    'admin.notify',
                    'Content imported',
                    '<p>' . htmlspecialchars($summary, ENT_QUOTES) . '</p>',
                    $r->basePath() . '/manage/posts',
                    'View posts',
                );
            }

            $this->hooks->emit('gcmigrations.imported', $report);
            $this->session->forget(self::SRC_KEY);
        }

        return $this->renderPage($r, 'index', [
            'base'    => $r->basePath(),
            'preview' => null,
            'report'  => $res['report'] ?? null,
            'message' => $res['message'],
            'ok'      => $res['ok'],
            'engine'  => $cfg['engine'] ?? 'auto',
            'schema'  => null,
            'form'    => $cfg + ['password' => ''],
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function readConfig(Request $r): array
    {
        $cfg = [
            'host'     => trim((string) $r->post('host', '127.0.0.1')),
            'port'     => (int) $r->post('port', '3306'),
            'dbname'   => trim((string) $r->post('dbname', '')),
            'username' => trim((string) $r->post('username', '')),
            'password' => (string) $r->post('password', ''),
            'prefix'   => trim((string) $r->post('prefix', '')),
            'engine'   => $this->validEngine((string) $r->post('engine', 'auto')),
            // Custom column mapping (only used when engine = custom)
            'src_table'        => trim((string) $r->post('src_table', '')),
            'map_title'        => trim((string) $r->post('map_title', '')),
            'map_content'      => trim((string) $r->post('map_content', '')),
            'map_slug'         => trim((string) $r->post('map_slug', '')),
            'map_excerpt'      => trim((string) $r->post('map_excerpt', '')),
            'map_status'       => trim((string) $r->post('map_status', '')),
            'status_published' => trim((string) $r->post('status_published', '')),
            'map_type'         => trim((string) $r->post('map_type', '')),
            'default_type'     => (string) $r->post('default_type', 'post') === 'page' ? 'page' : 'post',
            'map_created'      => trim((string) $r->post('map_created', '')),
        ];

        // If the password field was left blank, reuse the one remembered from a
        // successful preview against the SAME target (fixes the "could not
        // connect" error when importing after a preview blanked the field).
        if ($cfg['password'] === '') {
            $remembered = $this->session->get(self::SRC_KEY, []);
            if (is_array($remembered)
                && ($remembered['host'] ?? null)     === $cfg['host']
                && (int) ($remembered['port'] ?? 0)  === $cfg['port']
                && ($remembered['dbname'] ?? null)   === $cfg['dbname']
                && ($remembered['username'] ?? null) === $cfg['username']
                && (string) ($remembered['password'] ?? '') !== '') {
                $cfg['password'] = (string) $remembered['password'];
            }
        }

        return $cfg;
    }

    private function validEngine(string $e): string
    {
        return in_array($e, ['auto', 'gonicore', 'wordpress', 'custom'], true) ? $e : 'auto';
    }

    /** @return array<string,mixed> */
    private function defaults(): array
    {
        return [
            'host' => '127.0.0.1', 'port' => 3306, 'dbname' => '', 'username' => '',
            'password' => '', 'prefix' => '', 'engine' => 'auto',
            'src_table' => '', 'map_title' => '', 'map_content' => '', 'map_slug' => '',
            'map_excerpt' => '', 'map_status' => '', 'status_published' => '',
            'map_type' => '', 'default_type' => 'post', 'map_created' => '',
        ];
    }

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        // CSRF (403 — Apache turns a non-standard 419 into a 500).
        if ($r->method() === 'POST'
            && !$this->session->verifyCsrf((string) $r->post('_csrf', ''))) {
            return Response::error('Invalid or missing CSRF token.', 403);
        }
        $userId = (int) ($this->auth->currentUserId() ?? 0);
        $user   = $userId ? $this->qb->table('users')->where('id', '=', $userId)->first() : null;
        if ($user === null) {
            return Response::redirect($r->basePath() . '/manage');
        }
        try {
            $allowed = $this->authz->userCan($user, 'settings.manage');
        } catch (\Throwable) {
            $allowed = ((string) ($user['role'] ?? '')) === 'admin';
        }
        if (!$allowed) {
            return Response::redirect($r->basePath() . '/manage');
        }
        return null;
    }

    /** @param array<string,mixed> $data */
    private function renderPage(Request $request, string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $t = gc_plugin_translator(dirname(__DIR__));

        $base     = $data['base'] ?? $request->basePath();
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        $notifList       = $user ? $this->notifications->forUser((int) $user['id']) : [];
        $notifUnread     = $user ? $this->notifications->unreadCount((int) $user['id']) : 0;
        $panelLangs      = $this->langRepo->allActive();
        $currentLangCode = $this->langService->currentCode();

        $flashMsg  = $this->session->getFlash('gc_msg');
        $flashIcon = $this->session->getFlash('gc_icon') ?? 'success';
        $csrfToken = $this->session->csrfToken();

        $pageTitle = $t('title');
        $activeNav = 'migrations';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/admin/' . $view . '.php';
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $themeDir . '/manage/layout.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
