<?php

declare(strict_types=1);

namespace GoniCore\Modules\Menu;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Shared\Support\Str;

/**
 * Menu system — WordPress-style.
 *
 * Theme registers named locations in functions.php:
 *   MenuService::registerLocation('primary', 'Primary Navigation');
 *
 * Admin assigns a menu to a location via the Menus page.
 * Frontend renders with: $menuService->render('primary')
 */
final class MenuService
{
    /** @var array<string, string>  slug => label */
    private static array $locations = [];

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Location registration (called from theme functions.php) ───────────────

    public static function registerLocation(string $slug, string $name): void
    {
        self::$locations[$slug] = $name;
    }

    /** @return array<string, string> */
    public static function registeredLocations(): array
    {
        return self::$locations;
    }

    // ── Menu CRUD ─────────────────────────────────────────────────────────────

    public function allMenus(): array
    {
        return $this->qb->table('menus')->orderBy('name')->get();
    }

    public function findMenu(int $id): ?array
    {
        return $this->qb->table('menus')->where('id', '=', $id)->first();
    }

    public function createMenu(string $name): int
    {
        $slug = $this->uniqueMenuSlug($name);
        return (int) $this->qb->table('menus')->insert(['name' => $name, 'slug' => $slug]);
    }

    public function renameMenu(int $id, string $name): void
    {
        $this->qb->table('menus')->where('id', '=', $id)->update(['name' => $name]);
    }

    public function deleteMenu(int $id): void
    {
        $this->qb->table('menus')->where('id', '=', $id)->delete();
        $this->qb->table('menu_locations')->where('menu_id', '=', $id)->update(['menu_id' => null]);
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function itemsForMenu(int $menuId): array
    {
        return $this->qb->table('menu_items')
            ->where('menu_id', '=', $menuId)
            ->orderBy('sort_order')
            ->get();
    }

    public function addItem(int $menuId, array $data): int
    {
        $maxOrder = $this->qb->table('menu_items')->where('menu_id', '=', $menuId)->count();
        return (int) $this->qb->table('menu_items')->insert([
            'menu_id'    => $menuId,
            'parent_id'  => $data['parent_id'] ?? null,
            'type'       => $data['type']      ?? 'custom',
            'object_id'  => $data['object_id'] ?? null,
            'label'      => $data['label'],
            'url'        => $data['url']    ?? null,
            'target'     => $data['target'] ?? '_self',
            'sort_order' => $maxOrder,
        ]);
    }

    public function updateItem(int $id, array $data): void
    {
        $allowed = ['label', 'url', 'target', 'parent_id'];
        $update  = array_intersect_key($data, array_flip($allowed));
        if ($update) {
            $this->qb->table('menu_items')->where('id', '=', $id)->update($update);
        }
    }

    public function deleteItem(int $id): void
    {
        $this->qb->table('menu_items')->where('parent_id', '=', $id)->update(['parent_id' => null]);
        $this->qb->table('menu_items')->where('id', '=', $id)->delete();
    }

    public function reorderItems(array $ids): void
    {
        foreach ($ids as $order => $id) {
            $this->qb->table('menu_items')->where('id', '=', (int)$id)->update(['sort_order' => $order]);
        }
    }

    // ── Location assignments ──────────────────────────────────────────────────

    /** @return array<string, int|null> */
    public function locationAssignments(): array
    {
        $map = [];
        foreach ($this->qb->table('menu_locations')->get() as $r) {
            $map[(string)$r['location']] = $r['menu_id'] ? (int)$r['menu_id'] : null;
        }
        return $map;
    }

    public function assignMenuToLocation(string $location, ?int $menuId): void
    {
        $exists = $this->qb->table('menu_locations')->where('location', '=', $location)->first();
        if ($exists) {
            $this->qb->table('menu_locations')->where('location', '=', $location)->update(['menu_id' => $menuId]);
        } else {
            $this->qb->table('menu_locations')->insert(['location' => $location, 'menu_id' => $menuId]);
        }
    }

    // ── Frontend rendering ────────────────────────────────────────────────────

    public function render(string $location, string $ulClass = 'gc-menu'): string
    {
        $assignments = $this->locationAssignments();
        $menuId      = $assignments[$location] ?? null;
        if (!$menuId) return '';
        $items = $this->itemsForMenu($menuId);
        if (!$items) return '';
        return '<ul class="' . htmlspecialchars($ulClass, ENT_QUOTES) . '">'
            . $this->renderItems($items, null)
            . '</ul>';
    }

    private function renderItems(array $items, ?int $parentId): string
    {
        $html = '';
        foreach ($items as $item) {
            if ((int)($item['parent_id'] ?? 0) !== (int)$parentId) continue;
            $url      = htmlspecialchars((string)($item['url'] ?? '#'), ENT_QUOTES);
            $label    = htmlspecialchars((string)$item['label'], ENT_QUOTES);
            $target   = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
            $children = $this->renderItems($items, (int)$item['id']);
            $html .= '<li><a href="' . $url . '"' . $target . '>' . $label . '</a>';
            if ($children) $html .= '<ul>' . $children . '</ul>';
            $html .= '</li>';
        }
        return $html;
    }

    private function uniqueMenuSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;
        while ($this->qb->table('menus')->where('slug', '=', $slug)->first()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
