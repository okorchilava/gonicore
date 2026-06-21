<?php

declare(strict_types=1);

namespace GoniCore\Modules\Manage;

use GoniCore\Core\Database\Connection;

/**
 * Reads plugin metadata and handles plugin upload/delete in the manage panel.
 *
 * Lifecycle:
 *  - activate()  removes the .disabled marker AND runs the plugin's
 *    database/migration.php up() so its tables exist before next boot.
 *  - deactivate() only writes the .disabled marker — data is kept.
 *  - delete()    runs migration down() (drops the plugin's tables/data)
 *    and then removes the plugin directory. Irreversible.
 */
final class PluginManager
{
    public function __construct(
        private readonly string $pluginsDir,
        private readonly Connection $connection,
    ) {}

    // ── Discovery ─────────────────────────────────────────────────────────────

    /**
     * @return list<array<string,mixed>>
     */
    public function all(): array
    {
        $plugins = [];

        if (!is_dir($this->pluginsDir)) return $plugins;

        foreach (scandir($this->pluginsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $dir = $this->pluginsDir . '/' . $entry;
            if (!is_dir($dir)) continue;

            $plugins[] = $this->readMeta($entry, $dir);
        }

        usort($plugins, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $plugins;
    }

    private function readMeta(string $slug, string $dir): array
    {
        $jsonFile = $dir . '/plugin.json';
        $meta = [];

        if (is_file($jsonFile)) {
            $decoded = json_decode((string) file_get_contents($jsonFile), true);
            if (is_array($decoded)) $meta = $decoded;
        }

        // Fallback: read from bootstrap.php header comments
        if (empty($meta) && is_file($dir . '/bootstrap.php')) {
            $src = file_get_contents($dir . '/bootstrap.php');
            preg_match('/Plugin Name:\s*(.+)/i',    (string)$src, $n);
            preg_match('/Description:\s*(.+)/i',    (string)$src, $d);
            preg_match('/Version:\s*(.+)/i',        (string)$src, $v);
            preg_match('/Author:\s*(.+)/i',         (string)$src, $a);
            $meta = [
                'name'        => trim($n[1] ?? ucwords(str_replace(['-','_'], ' ', $slug))),
                'description' => trim($d[1] ?? ''),
                'version'     => trim($v[1] ?? '1.0.0'),
                'author'      => trim($a[1] ?? ''),
            ];
        }

        $hasBootstrap = is_file($dir . '/bootstrap.php');
        $disabledFile = $dir . '/.disabled';

        return [
            'slug'         => $slug,
            'name'         => $meta['name']        ?? ucwords(str_replace(['-','_'], ' ', $slug)),
            'description'  => $meta['description'] ?? '',
            'version'      => $meta['version']     ?? '1.0.0',
            'author'       => $meta['author']      ?? '',
            'active'       => $hasBootstrap && !file_exists($disabledFile),
            'has_bootstrap'=> $hasBootstrap,
            'dir'          => $dir,
        ];
    }

    // ── Toggle ────────────────────────────────────────────────────────────────

    public function activate(string $slug): void
    {
        $slug = $this->sanitizeSlug($slug);
        $dir  = $this->pluginsDir . '/' . $slug;
        if (!is_dir($dir)) {
            throw new \RuntimeException("Plugin \"{$slug}\" not found.");
        }

        // Create the plugin's tables before it boots on the next request.
        $this->runMigration($slug, 'up');

        $f = $dir . '/.disabled';
        if (file_exists($f)) @unlink($f);
    }

    public function deactivate(string $slug): void
    {
        $slug = $this->sanitizeSlug($slug);
        $dir  = $this->pluginsDir . '/' . $slug;
        if (is_dir($dir)) touch($dir . '/.disabled');
    }

    // ── Migrations ────────────────────────────────────────────────────────────

    /**
     * Run the plugin's database/migration.php in the given direction.
     * Convention: the file returns an object with up(Connection) / down(Connection).
     * Missing file or method is fine — not every plugin has tables.
     */
    private function runMigration(string $slug, string $direction): void
    {
        $file = $this->pluginsDir . '/' . $slug . '/database/migration.php';
        if (!is_file($file)) return;

        $migration = require $file;
        if (!is_object($migration) || !method_exists($migration, $direction)) return;

        $migration->{$direction}($this->connection);
    }

    // ── Upload ────────────────────────────────────────────────────────────────

    /**
     * Extract an uploaded ZIP file into the plugins directory.
     * Returns the new plugin slug on success, or throws on failure.
     */
    public function uploadZip(string $tmpPath, string $originalName): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive extension is not available.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            throw new \RuntimeException('Could not open ZIP file.');
        }

        // Determine plugin slug from the first directory in the ZIP and
        // reject any entry that could escape the plugins directory.
        $slug = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) continue;
            if (str_contains($name, '..') || str_starts_with($name, '/') || preg_match('/^[a-z]:/i', $name)) {
                $zip->close();
                throw new \RuntimeException('ZIP contains unsafe paths.');
            }
            if ($slug === '') {
                $parts = explode('/', $name, 2);
                if (!empty($parts[0])) $slug = $parts[0];
            }
        }

        if (!$slug) {
            $zip->close();
            throw new \RuntimeException('Could not determine plugin slug from ZIP.');
        }

        $clean = (string) preg_replace('/[^a-z0-9\-_]/i', '', $slug);
        if ($clean === '' || $clean !== $slug) {
            $zip->close();
            throw new \RuntimeException('Plugin folder name contains invalid characters.');
        }

        $zip->extractTo($this->pluginsDir);
        $zip->close();

        // New plugins arrive deactivated: the admin reviews and activates
        // explicitly, which also runs the plugin's migration.
        $dest = $this->pluginsDir . '/' . $clean;
        if (is_dir($dest) && !file_exists($dest . '/.disabled')) {
            touch($dest . '/.disabled');
        }

        return $clean;
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    /**
     * Uninstall: drop the plugin's database tables, then remove its files.
     * The manage UI must show a data-loss warning before calling this.
     */
    public function delete(string $slug): void
    {
        $slug = $this->sanitizeSlug($slug);
        $dir  = $this->pluginsDir . '/' . $slug;
        if (!is_dir($dir)) return;

        // Drop plugin data first while migration file still exists.
        try {
            $this->runMigration($slug, 'down');
        } catch (\Throwable $e) {
            // File removal still proceeds; log so the orphaned tables are traceable.
            error_log("[GoniCore] Plugin \"{$slug}\" down() migration failed: " . $e->getMessage());
        }

        $this->rmrf($dir);
    }

    private function sanitizeSlug(string $slug): string
    {
        // Strip path traversal — slugs are plain directory names.
        $clean = (string) preg_replace('/[^a-z0-9\-_]/i', '', $slug);
        if ($clean === '' || $clean !== $slug) {
            throw new \RuntimeException('Invalid plugin slug.');
        }
        return $clean;
    }

    private function rmrf(string $path): void
    {
        if (is_file($path) || is_link($path)) { @unlink($path); return; }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $this->rmrf($path . '/' . $item);
        }
        @rmdir($path);
    }
}
