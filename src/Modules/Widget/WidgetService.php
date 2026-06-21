<?php

declare(strict_types=1);

namespace GoniCore\Modules\Widget;

use GoniCore\Core\Hooks\HookManager;

/**
 * Renders widgets for a given area.
 *
 * Built-in widget types:
 *   html          — raw HTML block
 *   recent-posts  — latest posts list
 *   text          — plain text
 *
 * Plugins can register additional types via the hook:
 *   manage_widget_types   (filter) → adds entries to the types array
 *   render_widget_{type}  (action) → echoes widget HTML
 */
final class WidgetService
{
    /** @var list<array{slug:string, name:string, fields:list<array{name:string,label:string,type:string}>}> */
    private static array $types = [
        [
            'slug'   => 'html',
            'name'   => 'HTML',
            'fields' => [
                ['name' => 'html', 'label' => 'HTML Content', 'type' => 'textarea'],
            ],
        ],
        [
            'slug'   => 'text',
            'name'   => 'Text',
            'fields' => [
                ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
            ],
        ],
    ];

    /**
     * Theme-registered widget areas.
     * Starts empty — each active theme populates this via registerArea()
     * in its functions.php, which is loaded during bootstrap.
     *
     * @var list<array{slug:string, name:string, description:string}>
     */
    private static array $areas = [];

    public function __construct(
        private readonly WidgetRepository $repo,
        private readonly HookManager      $hooks,
    ) {}

    // ── Registration ──────────────────────────────────────────────────────────

    /**
     * Register a widget area.
     * Called from the active theme's functions.php.
     *
     * @param string $slug        Unique identifier, e.g. 'sidebar'
     * @param string $name        Human-readable label shown in the admin panel
     * @param string $description Optional hint shown below the area header
     */
    public static function registerArea(string $slug, string $name, string $description = ''): void
    {
        self::$areas[] = ['slug' => $slug, 'name' => $name, 'description' => $description];
    }

    public static function registerType(string $slug, string $name, array $fields = []): void
    {
        self::$types[] = compact('slug', 'name', 'fields');
    }

    public function areas(): array   { return self::$areas; }
    public function types(): array   { return self::$types; }

    public function findType(string $slug): ?array
    {
        foreach (self::$types as $t) {
            if ($t['slug'] === $slug) return $t;
        }
        return null;
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    /**
     * Render all active widgets in $area and return the combined HTML.
     */
    public function renderArea(string $area): string
    {
        $widgets = $this->repo->forArea($area);
        if (empty($widgets)) return '';

        $out = '';
        foreach ($widgets as $w) {
            $out .= $this->renderOne($w);
        }
        return $out;
    }

    private function renderOne(array $widget): string
    {
        $type     = (string) $widget['type'];
        $title    = (string) ($widget['title'] ?? '');
        $settings = is_string($widget['settings'])
            ? (json_decode($widget['settings'], true) ?? [])
            : ($widget['settings'] ?? []);

        ob_start();

        // Built-in rendering
        switch ($type) {
            case 'html':
                if ($title) echo '<div class="widget-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
                echo '<div class="widget-html">' . ($settings['html'] ?? '') . '</div>';
                break;
            case 'text':
                if ($title) echo '<div class="widget-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
                echo '<div class="widget-text">' . nl2br(htmlspecialchars((string)($settings['text'] ?? ''), ENT_QUOTES, 'UTF-8')) . '</div>';
                break;
            default:
                // Plugin-provided widget type
                $this->hooks->emit('render_widget_' . $type, $widget, $settings);
                break;
        }

        $inner = ob_get_clean();
        return '<div class="widget widget-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">' . $inner . '</div>';
    }
}
