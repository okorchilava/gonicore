<?php

declare(strict_types=1);

namespace GoniCore\Modules\Manage;

/**
 * Reads plugin metadata and handles plugin upload/delete in the manage panel.
 */
final class PluginManager
{
    public function __construct(
        private readonly string $pluginsDir,
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
        $f = $this->pluginsDir . '/' . $slug . '/.disabled';
        if (file_exists($f)) @unlink($f);
    }

    public function deactivate(string $slug): void
    {
        $dir = $this->pluginsDir . '/' . $slug;
        if (is_dir($dir)) touch($dir . '/.disabled');
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

        // Determine plugin slug from the first directory in the ZIP
        $slug = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) continue;
            $parts = explode('/', $name, 2);
            if (!empty($parts[0])) { $slug = $parts[0]; break; }
        }

        if (!$slug) {
            $zip->close();
            throw new \RuntimeException('Could not determine plugin slug from ZIP.');
        }

        $slug = preg_replace('/[^a-z0-9\-_]/i', '', $slug);
        $dest = $this->pluginsDir . '/' . $slug;

        $zip->extractTo($this->pluginsDir);
        $zip->close();

        return $slug;
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(string $slug): void
    {
        $dir = $this->pluginsDir . '/' . $slug;
        if (!is_dir($dir)) return;
        $this->rmrf($dir);
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
