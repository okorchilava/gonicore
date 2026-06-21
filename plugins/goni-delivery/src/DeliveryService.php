<?php
declare(strict_types=1);

namespace GoniDelivery;

use GoniCore\Core\Database\QueryBuilder;

final class DeliveryService
{
    public function __construct(private readonly QueryBuilder $qb) {}

    public function qb(): QueryBuilder { return $this->qb; }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function setting(string $key, string $default = ''): string
    {
        $row = $this->qb->table('gd_settings')->where('key', '=', $key)->first();
        return $row ? (string) $row['value'] : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        $exists = $this->qb->table('gd_settings')->where('key', '=', $key)->first();
        if ($exists) {
            $this->qb->table('gd_settings')->where('key', '=', $key)->update(['value' => $value]);
        } else {
            $this->qb->table('gd_settings')->insert(['key' => $key, 'value' => $value]);
        }
    }

    // ── Zones ─────────────────────────────────────────────────────────────────

    public function allZones(bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gd_zones');
        if ($activeOnly) $qb = $qb->where('active', '=', '1');
        return $qb->orderBy('sort_order', 'ASC')->get() ?: [];
    }

    public function getZone(int $id): ?array
    {
        return $this->qb->table('gd_zones')->where('id', '=', $id)->first();
    }

    public function createZone(array $data): int
    {
        return (int) $this->qb->table('gd_zones')->insert($data);
    }

    public function updateZone(int $id, array $data): void
    {
        $this->qb->table('gd_zones')->where('id', '=', $id)->update($data);
    }

    public function deleteZone(int $id): void
    {
        $this->qb->table('gd_zones')->where('id', '=', $id)->delete();
    }

    // ── Drivers ───────────────────────────────────────────────────────────────

    public function allDrivers(bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gd_drivers');
        if ($activeOnly) $qb = $qb->where('status', '=', 'active');
        return $qb->orderBy('name', 'ASC')->get() ?: [];
    }

    public function getDriver(int $id): ?array
    {
        return $this->qb->table('gd_drivers')->where('id', '=', $id)->first();
    }

    public function createDriver(array $data): int
    {
        return (int) $this->qb->table('gd_drivers')->insert($data);
    }

    public function updateDriver(int $id, array $data): void
    {
        $this->qb->table('gd_drivers')->where('id', '=', $id)->update($data);
    }

    public function deleteDriver(int $id): void
    {
        $this->qb->table('gd_drivers')->where('id', '=', $id)->delete();
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function allOrders(int $page = 1, int $perPage = 25, string $status = '', string $type = ''): array
    {
        $qb = $this->qb->table('gd_orders');
        if ($status !== '') $qb = $qb->where('status', '=', $status);
        if ($type !== '')   $qb = $qb->where('type', '=', $type);
        $total = (int) ($qb->count() ?? 0);
        $items = $qb->orderBy('created_at', 'DESC')->limit($perPage)->offset(($page - 1) * $perPage)->get() ?: [];
        return ['items' => $items, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function getOrder(int $id): ?array
    {
        $o = $this->qb->table('gd_orders')->where('id', '=', $id)->first();
        if (!$o) return null;
        if ($o['driver_id']) $o['driver'] = $this->getDriver((int) $o['driver_id']);
        if ($o['zone_id'])   $o['zone']   = $this->getZone((int) $o['zone_id']);
        return $o;
    }

    public function getOrderByNumber(string $number): ?array
    {
        $o = $this->qb->table('gd_orders')->where('order_number', '=', $number)->first();
        if (!$o) return null;
        if ($o['driver_id']) $o['driver'] = $this->getDriver((int) $o['driver_id']);
        if ($o['zone_id'])   $o['zone']   = $this->getZone((int) $o['zone_id']);
        return $o;
    }

    public function getOrderByToken(string $token): ?array
    {
        $o = $this->qb->table('gd_orders')->where('track_token', '=', $token)->first();
        if (!$o) return null;
        if ($o['driver_id']) $o['driver'] = $this->getDriver((int) $o['driver_id']);
        if ($o['zone_id'])   $o['zone']   = $this->getZone((int) $o['zone_id']);
        return $o;
    }

    public function createOrder(array $data): int
    {
        $data['order_number'] = $this->generateOrderNumber();
        $data['track_token']  = bin2hex(random_bytes(16));
        return (int) $this->qb->table('gd_orders')->insert($data);
    }

    public function updateOrder(int $id, array $data): void
    {
        if (isset($data['status']) && $data['status'] === 'delivered' && !isset($data['delivered_at'])) {
            $data['delivered_at'] = date('Y-m-d H:i:s');
        }
        $this->qb->table('gd_orders')->where('id', '=', $id)->update($data);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function globalStats(): array
    {
        $total     = (int) ($this->qb->table('gd_orders')->count() ?? 0);
        $pending   = (int) ($this->qb->table('gd_orders')->where('status', '=', 'pending')->count() ?? 0);
        $delivered = (int) ($this->qb->table('gd_orders')->where('status', '=', 'delivered')->count() ?? 0);
        $rows      = $this->qb->table('gd_orders')->where('payment_status', '=', 'paid')->get() ?: [];
        $revenue   = (float) array_sum(array_column($rows, 'price'));
        return compact('total', 'pending', 'delivered', 'revenue');
    }

    // ── Vendors ───────────────────────────────────────────────────────────────

    public function allVendors(bool $activeOnly = false, string $category = ''): array
    {
        $qb = $this->qb->table('gd_vendors');
        if ($activeOnly) $qb = $qb->where('status', '!=', 'inactive');
        if ($category)   $qb = $qb->where('category', '=', $category);
        return $qb->orderBy('is_featured','DESC')->orderBy('rating','DESC')->get() ?: [];
    }

    public function getVendor(int $id): ?array { return $this->qb->table('gd_vendors')->where('id','=',$id)->first(); }

    public function getVendorBySlug(string $slug): ?array { return $this->qb->table('gd_vendors')->where('slug','=',$slug)->first(); }

    public function getVendorByToken(string $token): ?array
    {
        if (!$token) return null;
        return $this->qb->table('gd_vendors')->where('vendor_token','=',$token)->first();
    }

    public function createVendor(array $data): int
    {
        if (empty($data['vendor_token'])) $data['vendor_token'] = bin2hex(random_bytes(16));
        if (empty($data['slug'])) $data['slug'] = $this->makeSlug($data['name'] ?? 'vendor');
        return (int)$this->qb->table('gd_vendors')->insert($data);
    }

    public function updateVendor(int $id, array $data): void { $this->qb->table('gd_vendors')->where('id','=',$id)->update($data); }

    public function deleteVendor(int $id): void { $this->qb->table('gd_vendors')->where('id','=',$id)->delete(); }

    public function regenVendorToken(int $id): string
    {
        $t = bin2hex(random_bytes(16));
        $this->qb->table('gd_vendors')->where('id','=',$id)->update(['vendor_token' => $t]);
        return $t;
    }

    private function makeSlug(string $name): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
        $base = $slug;
        $i = 2;
        while ($this->qb->table('gd_vendors')->where('slug','=',$slug)->first()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function isVendorOpen(array $vendor): bool
    {
        $now = date('H:i:s');
        $dow = (int)date('N');
        if (!str_contains((string)$vendor['days_open'], (string)$dow)) return false;
        if (!empty($vendor['open_time']) && !empty($vendor['close_time'])) {
            if ($now < $vendor['open_time'] || $now > $vendor['close_time']) return false;
        }
        return $vendor['status'] !== 'inactive';
    }

    // ── Catalog ───────────────────────────────────────────────────────────────

    public function vendorCategories(int $vendorId, bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gd_categories')->where('vendor_id','=',$vendorId);
        if ($activeOnly) $qb = $qb->where('active','=',1);
        return $qb->orderBy('sort_order','ASC')->get() ?: [];
    }

    public function getCategory(int $id): ?array { return $this->qb->table('gd_categories')->where('id','=',$id)->first(); }
    public function createCategory(array $d): int { return (int)$this->qb->table('gd_categories')->insert($d); }
    public function updateCategory(int $id, array $d): void { $this->qb->table('gd_categories')->where('id','=',$id)->update($d); }
    public function deleteCategory(int $id): void { $this->qb->table('gd_categories')->where('id','=',$id)->delete(); }

    public function vendorProducts(int $vendorId, ?int $categoryId = null, bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gd_products')->where('vendor_id','=',$vendorId);
        if ($categoryId !== null) $qb = $qb->where('category_id','=',$categoryId);
        if ($activeOnly) $qb = $qb->where('active','=',1);
        return $qb->orderBy('sort_order','ASC')->orderBy('name','ASC')->get() ?: [];
    }

    public function getProduct(int $id): ?array { return $this->qb->table('gd_products')->where('id','=',$id)->first(); }
    public function createProduct(array $d): int { return (int)$this->qb->table('gd_products')->insert($d); }
    public function updateProduct(int $id, array $d): void { $this->qb->table('gd_products')->where('id','=',$id)->update($d); }
    public function deleteProduct(int $id): void { $this->qb->table('gd_products')->where('id','=',$id)->delete(); }

    public function getProductWithModifiers(int $id): ?array
    {
        $p = $this->getProduct($id);
        if (!$p) return null;
        $groups = $this->qb->table('gd_modifier_groups')->where('product_id','=',$id)->orderBy('sort_order','ASC')->get() ?: [];
        foreach ($groups as &$g) {
            $g['modifiers'] = $this->qb->table('gd_modifiers')->where('group_id','=',(int)$g['id'])->orderBy('sort_order','ASC')->get() ?: [];
        }
        $p['modifier_groups'] = $groups;
        return $p;
    }

    public function vendorMenuGrouped(int $vendorId): array
    {
        $cats = $this->vendorCategories($vendorId, true);
        $prods = $this->vendorProducts($vendorId, null, true);
        $groups = [];
        // Uncategorized
        $uncategorized = array_filter($prods, fn($p) => empty($p['category_id']));
        foreach ($cats as $cat) {
            $catProds = array_values(array_filter($prods, fn($p) => (int)$p['category_id'] === (int)$cat['id']));
            if ($catProds) {
                foreach ($catProds as &$cp) {
                    $cp['modifier_groups'] = $this->qb->table('gd_modifier_groups')->where('product_id','=',(int)$cp['id'])->orderBy('sort_order','ASC')->get() ?: [];
                    foreach ($cp['modifier_groups'] as &$g) {
                        $g['modifiers'] = $this->qb->table('gd_modifiers')->where('group_id','=',(int)$g['id'])->orderBy('sort_order','ASC')->get() ?: [];
                    }
                    $cp['combos'] = $this->productCombos((int)$cp['id']);
                }
                $groups[] = ['category' => $cat, 'products' => $catProds];
            }
        }
        if (!empty($uncategorized)) {
            foreach ($uncategorized as &$uc) {
                $uc['modifier_groups'] = $this->qb->table('gd_modifier_groups')->where('product_id','=',(int)$uc['id'])->orderBy('sort_order','ASC')->get() ?: [];
                foreach ($uc['modifier_groups'] as &$g) {
                    $g['modifiers'] = $this->qb->table('gd_modifiers')->where('group_id','=',(int)$g['id'])->orderBy('sort_order','ASC')->get() ?: [];
                }
                $uc['combos'] = $this->productCombos((int)$uc['id']);
            }
            $groups[] = ['category' => ['id'=>0,'name'=>'სხვა','sort_order'=>999], 'products' => array_values($uncategorized)];
        }
        return $groups;
    }

    // Modifiers
    public function createModifierGroup(array $d): int { return (int)$this->qb->table('gd_modifier_groups')->insert($d); }
    public function deleteModifierGroup(int $id): void
    {
        $this->qb->table('gd_modifiers')->where('group_id','=',$id)->delete();
        $this->qb->table('gd_modifier_groups')->where('id','=',$id)->delete();
    }
    public function createModifier(array $d): int { return (int)$this->qb->table('gd_modifiers')->insert($d); }
    public function deleteModifier(int $id): void { $this->qb->table('gd_modifiers')->where('id','=',$id)->delete(); }

    /** Return all modifier groups (with their options) for a product. */
    public function getProductModifiers(int $productId): array
    {
        $groups = $this->qb->table('gd_modifier_groups')
            ->where('product_id', '=', $productId)
            ->orderBy('sort_order', 'ASC')
            ->get() ?: [];
        foreach ($groups as &$g) {
            $g['modifiers'] = $this->qb->table('gd_modifiers')
                ->where('group_id', '=', (int)$g['id'])
                ->orderBy('sort_order', 'ASC')
                ->get() ?: [];
        }
        return $groups;
    }

    /**
     * Replace ALL modifier groups for a product (full overwrite).
     * $groups = [{name, type, required, max_select, modifiers:[{name,price}]}]
     */
    public function saveProductModifiers(int $productId, array $groups): void
    {
        // Wipe existing
        $existing = $this->qb->table('gd_modifier_groups')
            ->where('product_id', '=', $productId)->get() ?: [];
        foreach ($existing as $eg) {
            $this->qb->table('gd_modifiers')->where('group_id', '=', (int)$eg['id'])->delete();
            $this->qb->table('gd_modifier_groups')->where('id', '=', (int)$eg['id'])->delete();
        }
        // Re-create
        foreach ($groups as $i => $group) {
            $name = trim((string)($group['name'] ?? '')) ?: 'ოფცია';
            $type = in_array($group['type'] ?? '', ['choice', 'exclusion', 'size']) ? $group['type'] : 'choice';
            $gid  = (int)$this->qb->table('gd_modifier_groups')->insert([
                'product_id'  => $productId,
                'name'        => $name,
                'type'        => $type,
                'required'    => (int)(bool)($group['required'] ?? 0),
                'min_select'  => (int)($group['min_select'] ?? 0),
                'max_select'  => (int)($group['max_select'] ?? ($type === 'exclusion' ? 20 : 1)),
                'sort_order'  => $i,
            ]);
            foreach (($group['modifiers'] ?? []) as $j => $mod) {
                $mname = trim((string)($mod['name'] ?? ''));
                if (!$mname) continue;
                $this->qb->table('gd_modifiers')->insert([
                    'group_id'   => $gid,
                    'name'       => $mname,
                    'price'      => (float)($mod['price'] ?? 0),
                    'in_stock'   => 1,
                    'sort_order' => $j,
                ]);
            }
        }
    }

    // ── Modifier Templates ────────────────────────────────────────────────────

    /** List all modifier templates for a vendor (with items). */
    public function getVendorModifierTemplates(int $vendorId): array
    {
        $templates = $this->qb->table('gd_modifier_templates')
            ->where('vendor_id', '=', $vendorId)
            ->orderBy('sort_order', 'ASC')
            ->get() ?: [];
        foreach ($templates as &$t) {
            $t['items'] = $this->qb->table('gd_modifier_template_items')
                ->where('template_id', '=', (int)$t['id'])
                ->orderBy('sort_order', 'ASC')
                ->get() ?: [];
        }
        return $templates;
    }

    /**
     * Create or update a modifier template.
     * $data = {name, type, required, max_select, items:[{name,price}]}
     */
    public function saveModifierTemplate(int $vendorId, array $data, ?int $templateId = null): int
    {
        $name      = trim((string)($data['name'] ?? '')) ?: 'შაბლონი';
        $type      = in_array($data['type'] ?? '', ['choice', 'exclusion', 'size']) ? $data['type'] : 'choice';
        $required  = (int)(bool)($data['required'] ?? 0);
        $maxSelect = (int)($data['max_select'] ?? ($type === 'exclusion' ? 20 : 1));

        if ($templateId) {
            $existing = $this->qb->table('gd_modifier_templates')
                ->where('id', '=', $templateId)->where('vendor_id', '=', $vendorId)->first();
            if (!$existing) throw new \RuntimeException('Template not found');
            $this->qb->table('gd_modifier_templates')->where('id', '=', $templateId)->update([
                'name'       => $name,
                'type'       => $type,
                'required'   => $required,
                'max_select' => $maxSelect,
            ]);
        } else {
            $sort = (int)(($this->qb->table('gd_modifier_templates')
                ->where('vendor_id', '=', $vendorId)
                ->selectRaw('MAX(sort_order) AS mx')
                ->first() ?: [])['mx'] ?? 0) + 1;
            $templateId = (int)$this->qb->table('gd_modifier_templates')->insert([
                'vendor_id'  => $vendorId,
                'name'       => $name,
                'type'       => $type,
                'required'   => $required,
                'max_select' => $maxSelect,
                'sort_order' => $sort,
            ]);
        }

        // Replace items
        $this->qb->table('gd_modifier_template_items')
            ->where('template_id', '=', $templateId)->delete();
        foreach (($data['items'] ?? []) as $i => $item) {
            $iname = trim((string)($item['name'] ?? ''));
            if (!$iname) continue;
            $this->qb->table('gd_modifier_template_items')->insert([
                'template_id' => $templateId,
                'name'        => $iname,
                'price'       => (float)($item['price'] ?? 0),
                'sort_order'  => $i,
            ]);
        }
        return $templateId;
    }

    /** Delete a modifier template (and its items). */
    public function deleteModifierTemplate(int $vendorId, int $templateId): void
    {
        $existing = $this->qb->table('gd_modifier_templates')
            ->where('id', '=', $templateId)->where('vendor_id', '=', $vendorId)->first();
        if (!$existing) throw new \RuntimeException('Template not found');
        $this->qb->table('gd_modifier_template_items')
            ->where('template_id', '=', $templateId)->delete();
        $this->qb->table('gd_modifier_templates')
            ->where('id', '=', $templateId)->delete();
    }

    /**
     * Apply a template to ALL products of this vendor.
     * Replaces/adds a modifier group with the same name as the template in each product.
     */
    public function applyTemplateToAllProducts(int $vendorId, int $templateId): int
    {
        $tpl = $this->qb->table('gd_modifier_templates')
            ->where('id', '=', $templateId)->where('vendor_id', '=', $vendorId)->first();
        if (!$tpl) throw new \RuntimeException('Template not found');

        $items = $this->qb->table('gd_modifier_template_items')
            ->where('template_id', '=', $templateId)->orderBy('sort_order', 'ASC')->get() ?: [];

        $products = $this->qb->table('gd_products')
            ->where('vendor_id', '=', $vendorId)->get() ?: [];

        $applied = 0;
        foreach ($products as $prod) {
            $prodId = (int)$prod['id'];
            // Remove existing group with same name for this product
            $existing = $this->qb->table('gd_modifier_groups')
                ->where('product_id', '=', $prodId)
                ->where('name', '=', $tpl['name'])
                ->get() ?: [];
            foreach ($existing as $eg) {
                $this->qb->table('gd_modifiers')->where('group_id', '=', (int)$eg['id'])->delete();
                $this->qb->table('gd_modifier_groups')->where('id', '=', (int)$eg['id'])->delete();
            }
            // Find max sort_order
            $maxSort = (int)(($this->qb->table('gd_modifier_groups')
                ->where('product_id', '=', $prodId)
                ->selectRaw('MAX(sort_order) AS mx')
                ->first() ?: [])['mx'] ?? -1);
            // Insert new group
            $gid = (int)$this->qb->table('gd_modifier_groups')->insert([
                'product_id'  => $prodId,
                'name'        => $tpl['name'],
                'type'        => $tpl['type'],
                'required'    => (int)$tpl['required'],
                'min_select'  => 0,
                'max_select'  => (int)$tpl['max_select'],
                'sort_order'  => $maxSort + 1,
            ]);
            foreach ($items as $j => $item) {
                $this->qb->table('gd_modifiers')->insert([
                    'group_id'   => $gid,
                    'name'       => $item['name'],
                    'price'      => (float)$item['price'],
                    'in_stock'   => 1,
                    'sort_order' => $j,
                ]);
            }
            $applied++;
        }
        return $applied;
    }

    // ── Order Items ───────────────────────────────────────────────────────────

    public function orderItems(int $orderId): array
    {
        $rows = $this->qb->table('gd_order_items')->where('order_id','=',$orderId)->get() ?: [];
        return $this->enrichModifierGroupTypes($rows);
    }

    /**
     * Back-fills `group_type` on modifiers that were saved before the field was added.
     * Does a single bulk lookup per call — no N+1.
     */
    private function enrichModifierGroupTypes(array $items): array
    {
        // Collect modifier IDs that are missing group_type
        $missingIds = [];
        foreach ($items as $item) {
            if (empty($item['modifiers_json'])) continue;
            $extras = json_decode($item['modifiers_json'], true) ?? [];
            foreach ($extras['modifiers'] ?? [] as $modId => $mod) {
                if (!isset($mod['group_type'])) $missingIds[(int)$modId] = true;
            }
        }
        if (empty($missingIds)) return $items;

        // Bulk look up: modifier → group → type
        $ids      = array_keys($missingIds);
        $modRows  = $this->qb->table('gd_modifiers')->where('id', 'IN', $ids)->get() ?: [];
        $groupIds = array_unique(array_map(fn($r) => (int)$r['group_id'], $modRows));
        $grpRows  = !empty($groupIds)
            ? ($this->qb->table('gd_modifier_groups')->where('id', 'IN', $groupIds)->get() ?: [])
            : [];
        $grpTypeMap = array_column($grpRows, 'type', 'id');
        $modTypeMap = [];
        foreach ($modRows as $mr) {
            $modTypeMap[(int)$mr['id']] = $grpTypeMap[(int)$mr['group_id']] ?? 'choice';
        }

        // Re-encode with group_type injected
        foreach ($items as &$item) {
            if (empty($item['modifiers_json'])) continue;
            $extras  = json_decode($item['modifiers_json'], true) ?? [];
            if (empty($extras['modifiers'])) continue;
            $changed = false;
            foreach ($extras['modifiers'] as $modId => &$mod) {
                if (!isset($mod['group_type']) && isset($modTypeMap[(int)$modId])) {
                    $mod['group_type'] = $modTypeMap[(int)$modId];
                    $changed = true;
                }
            }
            unset($mod);
            if ($changed) $item['modifiers_json'] = json_encode($extras);
        }
        unset($item);

        return $items;
    }

    /**
     * Back-fills `group_type` on cart session items' modifier arrays.
     * Cart modifiers are stored as [modifierId => {name, price}] in the session.
     */
    public function enrichCartModifiers(array $cart): array
    {
        $missingIds = [];
        foreach ($cart as $item) {
            foreach ($item['modifiers'] ?? [] as $modId => $mod) {
                if (!isset($mod['group_type'])) $missingIds[(int)$modId] = true;
            }
        }
        if (empty($missingIds)) return $cart;

        $ids        = array_keys($missingIds);
        $modRows    = $this->qb->table('gd_modifiers')->where('id', 'IN', $ids)->get() ?: [];
        $groupIds   = array_unique(array_map(fn($r) => (int)$r['group_id'], $modRows));
        $grpRows    = !empty($groupIds)
            ? ($this->qb->table('gd_modifier_groups')->where('id', 'IN', $groupIds)->get() ?: [])
            : [];
        $grpTypeMap = array_column($grpRows, 'type', 'id');
        $modTypeMap = [];
        foreach ($modRows as $mr) {
            $modTypeMap[(int)$mr['id']] = $grpTypeMap[(int)$mr['group_id']] ?? 'choice';
        }

        foreach ($cart as &$item) {
            foreach ($item['modifiers'] ?? [] as $modId => &$mod) {
                if (!isset($mod['group_type']) && isset($modTypeMap[(int)$modId])) {
                    $mod['group_type'] = $modTypeMap[(int)$modId];
                }
            }
            unset($mod);
        }
        unset($item);

        return $cart;
    }

    public function createOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $extras = [];
            if (!empty($item['modifiers'])) $extras['modifiers'] = $item['modifiers'];
            if (!empty($item['combos']))    $extras['combos']    = $item['combos'];
            $modJson = $extras ? json_encode($extras) : null;
            $this->qb->table('gd_order_items')->insert([
                'order_id'       => $orderId,
                'product_id'     => $item['product_id'],
                'vendor_id'      => $item['vendor_id'],
                'name'           => $item['name'],
                'price'          => (float)($item['unit_price'] ?? $item['price'] ?? 0),
                'quantity'       => $item['quantity'],
                'modifiers_json' => $modJson,
                'item_total'     => $item['item_total'],
            ]);
        }
    }

    // ── Customers ─────────────────────────────────────────────────────────────

    public function getCustomerByPhone(string $phone): ?array { return $this->qb->table('gd_customers')->where('phone','=',$phone)->first(); }
    public function getCustomerById(int $id): ?array { return $this->qb->table('gd_customers')->where('id','=',$id)->first(); }

    public function getOrCreateCustomer(string $phone, string $name = ''): array
    {
        $c = $this->getCustomerByPhone($phone);
        if ($c) return $c;
        $id = $this->qb->table('gd_customers')->insert(['phone'=>$phone,'name'=>$name ?: $phone,'email'=>'']);
        return $this->getCustomerById((int)$id);
    }

    public function updateCustomer(int $id, array $data): void { $this->qb->table('gd_customers')->where('id','=',$id)->update($data); }

    // ── OTP ───────────────────────────────────────────────────────────────────

    public function generateOtp(string $phone): string
    {
        $this->qb->table('gd_otp_codes')->where('phone','=',$phone)->where('used','=',0)->update(['used'=>1]);
        $code    = str_pad((string)random_int(1000,9999), 4, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 600);
        $this->qb->table('gd_otp_codes')->insert(['phone'=>$phone,'code'=>$code,'expires_at'=>$expires,'used'=>0,'attempts'=>0]);
        return $code;
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $row = $this->qb->table('gd_otp_codes')->where('phone','=',$phone)->where('used','=',0)->orderBy('id','DESC')->first();
        if (!$row || strtotime((string)$row['expires_at']) < time() || (int)$row['attempts'] >= 5) return false;
        $this->qb->table('gd_otp_codes')->where('id','=',(int)$row['id'])->update(['attempts'=>(int)$row['attempts']+1]);
        if ((string)$row['code'] !== trim($code)) return false;
        $this->qb->table('gd_otp_codes')->where('id','=',(int)$row['id'])->update(['used'=>1]);
        return true;
    }

    // ── Dispatch — offer-based random assignment ──────────────────────────────

    /**
     * Offer the order to a random available courier.
     *
     * Round-robin logic:
     *  1. Skip couriers who already declined or timed-out on THIS order in the current round.
     *  2. If every online courier has been tried (round exhausted), delete their
     *     declined/expired records and start a fresh round so they get asked again.
     *  3. Return null only when there are genuinely no online/active couriers at all,
     *     or every online courier is busy on another delivery or already holds a live offer.
     */
    public function dispatchOffer(int $orderId): ?int
    {
        $order = $this->getOrder($orderId);
        if (!$order || $order['driver_id']) return null;

        // Expire any stale pending offer for this order first
        $stale = $this->qb->table('gd_order_offers')
            ->where('order_id', '=', $orderId)
            ->where('status',   '=', 'pending')
            ->get() ?: [];
        foreach ($stale as $s) {
            $this->qb->table('gd_order_offers')->where('id','=',(int)$s['id'])->update(['status'=>'expired']);
        }

        // Couriers who already declined or had expired offer for this order (current round)
        $tried = $this->qb->table('gd_order_offers')
            ->where('order_id', '=', $orderId)
            ->where('status',   'IN', ['declined','expired'])
            ->get() ?: [];
        $triedIds = array_unique(array_map(fn($r) => (int)$r['driver_id'], $tried));

        // Couriers currently busy on another active order
        $busyOrders = $this->qb->table('gd_orders')
            ->where('status', 'IN', ['accepted','picked_up','in_transit'])
            ->get() ?: [];
        $busyIds = array_filter(array_unique(array_map(fn($o) => (int)($o['driver_id'] ?? 0), $busyOrders)));

        // Couriers already holding a live offer (for any order)
        $liveOffers = $this->qb->table('gd_order_offers')
            ->where('status',     '=', 'pending')
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->get() ?: [];
        $withOfferIds = array_map(fn($o) => (int)$o['driver_id'], $liveOffers);

        $excludeIds = array_unique(array_merge($triedIds, $busyIds, $withOfferIds));

        // All online active couriers
        $drivers = $this->qb->table('gd_drivers')
            ->where('status',    '=', 'active')
            ->where('is_online', '=', 1)
            ->get() ?: [];

        $available = array_values(array_filter($drivers, fn($d) => !in_array((int)$d['id'], $excludeIds)));

        if (empty($available)) {
            // Are there any online couriers that are simply "not yet tried" if we
            // ignore the tried-list?  (i.e. the round is exhausted, not absent)
            $permanentExclude = array_unique(array_merge((array)$busyIds, $withOfferIds));
            $freshPool = array_values(array_filter($drivers, fn($d) => !in_array((int)$d['id'], $permanentExclude)));

            if (empty($freshPool)) {
                // Genuinely nobody online, or everyone is busy / already has a live offer
                return null;
            }

            // Round exhausted — reset this order's declined/expired records and go again
            $this->qb->table('gd_order_offers')
                ->where('order_id', '=', $orderId)
                ->where('status',   'IN', ['declined', 'expired'])
                ->delete();

            $available = $freshPool;
        }

        // Pick one at random
        $driver   = $available[array_rand($available)];
        $driverId = (int)$driver['id'];

        // Ensure driver has a token
        if (empty($driver['driver_token'])) {
            $tok = bin2hex(random_bytes(16));
            $this->qb->table('gd_drivers')->where('id','=',$driverId)->update(['driver_token'=>$tok]);
        }

        // Create the offer (30-second window)
        $this->qb->table('gd_order_offers')->insert([
            'order_id'   => $orderId,
            'driver_id'  => $driverId,
            'expires_at' => date('Y-m-d H:i:s', time() + 30),
            'status'     => 'pending',
        ]);

        return $driverId;
    }

    /** Expire stale offers and re-dispatch them to new couriers. Called lazily on each courier poll. */
    public function retryExpiredOffers(): void
    {
        $expired = $this->qb->table('gd_order_offers')
            ->where('status',     '=', 'pending')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->get() ?: [];

        foreach ($expired as $offer) {
            $this->qb->table('gd_order_offers')
                ->where('id','=',(int)$offer['id'])
                ->update(['status'=>'expired']);

            $order = $this->getOrder((int)$offer['order_id']);
            if ($order && !$order['driver_id']) {
                $this->dispatchOffer((int)$offer['order_id']);
            }
        }
    }

    /** Get the live pending offer for a specific driver, or null. */
    public function getActiveOfferForDriver(int $driverId): ?array
    {
        $offer = $this->qb->table('gd_order_offers')
            ->where('driver_id', '=', $driverId)
            ->where('status',    '=', 'pending')
            ->where('expires_at','>', date('Y-m-d H:i:s'))
            ->first();
        if (!$offer) return null;
        return $offer;
    }

    // ── Vendor Orders ─────────────────────────────────────────────────────────

    /**
     * Deterministic 4-digit handoff PIN for an order.
     * The same orderId always produces the same PIN — no DB column needed.
     */
    public function generateHandoffPin(int $orderId): string
    {
        return str_pad((string)((abs(crc32('gd_handoff_v1_' . $orderId)) % 9000) + 1000), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Deterministic 4-digit portal PIN for a branch.
     * Shown in vendor admin; required when selecting a branch in the portal.
     */
    public function generateBranchPin(int $branchId): string
    {
        return str_pad((string)((abs(crc32('gd_branch_v1_' . $branchId)) % 9000) + 1000), 4, '0', STR_PAD_LEFT);
    }

    public function vendorOrders(int $vendorId, string $status = '', ?int $branchId = null): array
    {
        $qb = $this->qb->table('gd_orders')->where('vendor_id','=',$vendorId);
        if ($status) {
            $qb = $qb->where('vendor_status','=',$status);
        } else {
            // Show only active vendor states; completed/dismissed orders must not re-appear
            $qb = $qb->where('vendor_status','IN',['pending','accepted','preparing','ready']);
        }
        $items = $qb->orderBy('created_at','DESC')->limit(50)->get() ?: [];
        // Branch filter in PHP: show orders for this branch OR unassigned orders (branch_id=0/NULL)
        // QueryBuilder has no IS NULL operator, so we filter after fetch.
        if ($branchId !== null) {
            $items = array_values(array_filter($items, function (array $o) use ($branchId): bool {
                $bid = (int)($o['branch_id'] ?? 0);
                return $bid === $branchId || $bid === 0;
            }));
        }
        foreach ($items as &$o) {
            $o['items'] = $this->orderItems((int)$o['id']);
        }
        return $items;
    }

    public function getOrderWithItems(int $id): ?array
    {
        $o = $this->getOrder($id);
        if (!$o) return null;
        $o['items'] = $this->orderItems($id);
        if ($o['vendor_id']) $o['vendor'] = $this->getVendor((int)$o['vendor_id']);
        if ($o['customer_id']) $o['customer'] = $this->getCustomerById((int)$o['customer_id']);
        return $o;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = ($lat2-$lat1)*M_PI/180;
        $dLng = ($lng2-$lng1)*M_PI/180;
        $a = sin($dLat/2)**2 + cos($lat1*M_PI/180)*cos($lat2*M_PI/180)*sin($dLng/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function generateOrderNumber(): string
    {
        $last = $this->qb->table('gd_orders')->orderBy('id', 'DESC')->first();
        $next = $last ? (int) $last['id'] + 1 : 1;
        return 'GD-' . date('Ym') . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', ',') . $this->setting('currency_symbol', '₾');
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'    => '⏳ Pending',
            'accepted'   => '✅ Accepted',
            'picked_up'  => '📦 Picked Up',
            'in_transit' => '🚗 In Transit',
            'delivered'  => '🏁 Delivered',
            'cancelled'  => '❌ Cancelled',
            default      => ucfirst($status),
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'pending'    => '#f59e0b',
            'accepted'   => '#3b82f6',
            'picked_up'  => '#8b5cf6',
            'in_transit' => '#10b981',
            'delivered'  => '#059669',
            'cancelled'  => '#ef4444',
            default      => '#94a3b8',
        };
    }

    public function allStatuses(): array
    {
        return ['pending','accepted','picked_up','in_transit','delivered','cancelled'];
    }

    // ── Vendor Locations (Branches) ───────────────────────────────────────────

    public function vendorBranches(int $vendorId, bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gd_vendor_locations')->where('vendor_id','=',$vendorId);
        if ($activeOnly) $qb = $qb->where('active','=',1);
        return $qb->orderBy('sort_order','ASC')->orderBy('id','ASC')->get() ?: [];
    }

    public function getBranch(int $id): ?array { return $this->qb->table('gd_vendor_locations')->where('id','=',$id)->first(); }
    public function createBranch(array $d): int { return (int)$this->qb->table('gd_vendor_locations')->insert($d); }
    public function updateBranch(int $id, array $d): void { $this->qb->table('gd_vendor_locations')->where('id','=',$id)->update($d); }
    public function deleteBranch(int $id): void { $this->qb->table('gd_vendor_locations')->where('id','=',$id)->delete(); }

    /**
     * Haversine great-circle distance (km) — public for use in controllers.
     */
    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }

    /**
     * Find the nearest active branch to the given customer coordinates.
     * Falls back to null if the vendor has no geo-tagged branches.
     */
    public function findNearestBranch(int $vendorId, float $lat, float $lng): ?array
    {
        $branches = $this->vendorBranches($vendorId, true);
        $geo      = array_filter($branches, fn($b) => !empty($b['lat']) && !empty($b['lng']));
        if (empty($geo)) return null;
        $nearest = null;
        $minDist = PHP_FLOAT_MAX;
        foreach ($geo as $b) {
            $d = $this->haversine($lat, $lng, (float)$b['lat'], (float)$b['lng']);
            if ($d < $minDist) { $minDist = $d; $nearest = $b; }
        }
        return $nearest;
    }

    // ── Offers / Discounts ────────────────────────────────────────────────────

    public function vendorOffers(int $vendorId): array
    {
        return $this->qb->table('gd_offers')
            ->where('vendor_id', '=', $vendorId)
            ->orderBy('created_at', 'DESC')
            ->get() ?: [];
    }

    public function activeOffers(int $vendorId): array
    {
        $today = date('Y-m-d');
        $all   = $this->vendorOffers($vendorId);
        return array_values(array_filter($all, function ($o) use ($today) {
            if (!(bool)$o['active']) return false;
            if ($o['start_date'] && $o['start_date'] > $today) return false;
            if ($o['end_date']   && $o['end_date']   < $today) return false;
            return true;
        }));
    }

    public function getOffer(int $id): ?array
    {
        return $this->qb->table('gd_offers')->where('id', '=', $id)->first();
    }

    public function createOffer(array $d): int
    {
        return (int) $this->qb->table('gd_offers')->insert($d);
    }

    public function updateOffer(int $id, array $d): void
    {
        $this->qb->table('gd_offers')->where('id', '=', $id)->update($d);
    }

    public function deleteOffer(int $id): void
    {
        $this->qb->table('gd_offers')->where('id', '=', $id)->delete();
    }

    // ── Combos ────────────────────────────────────────────────────────────────────

    public function vendorCombos(int $vendorId): array
    {
        try {
            $combos = $this->qb->table('gd_combos')
                ->where('vendor_id', '=', $vendorId)
                ->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')
                ->get() ?: [];
            foreach ($combos as &$c) {
                $c['products'] = $this->comboProducts((int)$c['id']);
            }
            return $combos;
        } catch (\Throwable) {
            return [];
        }
    }

    public function getCombo(int $id): ?array
    {
        try {
            return $this->qb->table('gd_combos')->where('id', '=', $id)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    public function createCombo(array $d): int
    {
        try {
            return (int)$this->qb->table('gd_combos')->insert($d);
        } catch (\Throwable $e) {
            throw $e; // re-throw so API returns proper error
        }
    }

    public function updateCombo(int $id, array $d): void
    {
        $this->qb->table('gd_combos')->where('id', '=', $id)->update($d);
    }

    public function deleteCombo(int $id): void
    {
        try {
            $this->qb->table('gd_combo_products')->where('combo_id', '=', $id)->delete();
        } catch (\Throwable) {}
        try {
            $this->qb->table('gd_product_combos')->where('combo_id', '=', $id)->delete();
        } catch (\Throwable) {}
        $this->qb->table('gd_combos')->where('id', '=', $id)->delete();
    }

    /** Returns products inside a given combo group, with their per-item price_modifier. */
    public function comboProducts(int $comboId): array
    {
        try {
            $items = $this->qb->table('gd_combo_products')
                ->where('combo_id', '=', $comboId)
                ->orderBy('sort_order', 'ASC')
                ->get() ?: [];
            $result = [];
            foreach ($items as $item) {
                $p = $this->getProduct((int)$item['product_id']);
                if ($p) {
                    $result[] = [
                        'id'             => (int)$p['id'],
                        'name'           => $p['name'],
                        'price'          => (float)$p['price'],
                        'price_modifier' => (float)($item['price_modifier'] ?? 0),
                        'image'          => $p['image'] ?? '',
                        'in_stock'       => (int)$p['in_stock'],
                    ];
                }
            }
            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Replace all products in a combo group.
     * $products = array of int (product_id) OR array of ['product_id'=>int, 'price_modifier'=>float]
     */
    public function setComboProducts(int $comboId, array $products): void
    {
        try {
            $this->qb->table('gd_combo_products')->where('combo_id', '=', $comboId)->delete();
            foreach (array_values($products) as $i => $item) {
                $pid  = is_array($item) ? (int)($item['product_id'] ?? 0) : (int)$item;
                $pmod = is_array($item) ? (float)($item['price_modifier'] ?? 0) : 0.0;
                if ($pid > 0) {
                    $this->qb->table('gd_combo_products')->insert([
                        'combo_id'       => $comboId,
                        'product_id'     => $pid,
                        'price_modifier' => $pmod,
                        'sort_order'     => $i,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            throw $e; // let the API layer handle it
        }
    }

    /** Returns combo groups attached to a product (with their selectable products). */
    public function productCombos(int $productId): array
    {
        try {
            $links = $this->qb->table('gd_product_combos')
                ->where('product_id', '=', $productId)
                ->orderBy('sort_order', 'ASC')
                ->get() ?: [];
            $result = [];
            foreach ($links as $link) {
                $c = $this->getCombo((int)$link['combo_id']);
                if ($c && (int)$c['active'] === 1) {
                    $c['products'] = $this->comboProducts((int)$c['id']);
                    $result[] = $c;
                }
            }
            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /** Replace all combo groups attached to a product. */
    public function syncProductCombos(int $productId, array $comboIds): void
    {
        try {
            $this->qb->table('gd_product_combos')->where('product_id', '=', $productId)->delete();
            foreach (array_values($comboIds) as $i => $cid) {
                $cid = (int)$cid;
                if ($cid > 0) {
                    $this->qb->table('gd_product_combos')->insert([
                        'product_id' => $productId,
                        'combo_id'   => $cid,
                        'sort_order' => $i,
                    ]);
                }
            }
        } catch (\Throwable) {
            // table may not exist yet — silently ignore
        }
    }

    // ── Combo Meals ───────────────────────────────────────────────────────────

    /** Find or auto-create a category named "კომბო" for the vendor. */
    private function findOrCreateComboCategory(int $vendorId): int
    {
        $cat = $this->qb->table('gd_categories')
            ->where('vendor_id', '=', $vendorId)
            ->where('name', '=', 'კომბო')
            ->first();
        if ($cat) return (int)$cat['id'];
        return (int)$this->qb->table('gd_categories')->insert([
            'vendor_id'  => $vendorId,
            'name'       => 'კომბო',
            'sort_order' => 99,
            'active'     => 1,
        ]);
    }

    /**
     * Create or update a combo-meal product with its 3 dedicated combo groups.
     * $data keys: name, price, image, description, category_id (optional),
     *             mandatory[], optional[], drinks[]  — each an array of product_ids.
     */
    public function saveComboMeal(int $vendorId, array $data, ?int $productId = null): int
    {
        $catId = isset($data['category_id']) && (int)$data['category_id']
            ? (int)$data['category_id']
            : $this->findOrCreateComboCategory($vendorId);

        $prodData = [
            'vendor_id'   => $vendorId,
            'category_id' => $catId,
            'name'        => trim((string)($data['name'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')),
            'price'       => (float)($data['price'] ?? 0),
            'image'       => trim((string)($data['image'] ?? '')),
            'active'      => isset($data['active']) ? (int)(bool)$data['active'] : 1,
            'in_stock'    => 1,
            'sort_order'  => (int)($data['sort_order'] ?? 0),
        ];

        if ($productId) {
            $this->updateProduct($productId, $prodData);
        } else {
            $productId = $this->createProduct($prodData);
        }

        // Wipe all existing combo groups dedicated to this product
        $links = $this->qb->table('gd_product_combos')
            ->where('product_id', '=', $productId)->get() ?: [];
        foreach ($links as $link) {
            $gid = (int)$link['combo_id'];
            $this->qb->table('gd_product_combos')
                ->where('product_id', '=', $productId)
                ->where('combo_id',   '=', $gid)->delete();
            // Delete the combo group itself if no other product uses it
            $other = $this->qb->table('gd_product_combos')
                ->where('combo_id', '=', $gid)->first();
            if (!$other) {
                $this->qb->table('gd_combo_products')->where('combo_id', '=', $gid)->delete();
                $this->qb->table('gd_combos')->where('id', '=', $gid)->delete();
            }
        }

        // ── Mandatory (included, always part of combo) ───────────────────────
        $mandItems = array_values(array_filter(
            array_map('intval', (array)($data['mandatory'] ?? []))
        ));
        if ($mandItems) {
            $gid = (int)$this->qb->table('gd_combos')->insert([
                'vendor_id'  => $vendorId,
                'name'       => 'სავალდებულო',
                'type'       => 'included',
                'required'   => 0,
                'max_select' => 0,
                'sort_order' => 1,
                'active'     => 1,
            ]);
            $this->setComboProducts($gid, $mandItems);
            $this->qb->table('gd_product_combos')->insert(['product_id'=>$productId,'combo_id'=>$gid,'sort_order'=>1]);
        }

        // ── Dynamic extras (choice, optional — can be multiple sections) ────
        $extras = (array)($data['extras'] ?? []);
        foreach ($extras as $i => $extra) {
            $items = array_values(array_filter(
                array_map('intval', (array)($extra['products'] ?? []))
            ));
            if (!$items) continue;
            $name = trim((string)($extra['name'] ?? 'დამატებითი')) ?: 'დამატებითი';
            $gid = (int)$this->qb->table('gd_combos')->insert([
                'vendor_id'  => $vendorId,
                'name'       => $name,
                'type'       => 'choice',
                'required'   => 0,
                'max_select' => 10,
                'sort_order' => 2 + $i,
                'active'     => 1,
            ]);
            $this->setComboProducts($gid, $items);
            $this->qb->table('gd_product_combos')->insert(['product_id'=>$productId,'combo_id'=>$gid,'sort_order'=>2+$i]);
        }

        // ── Drinks (choice, required — customer picks one) ────────────────
        $drinkItems = array_values(array_filter(
            array_map('intval', (array)($data['drinks'] ?? []))
        ));
        if ($drinkItems) {
            $sort = 10 + count($extras);
            $gid = (int)$this->qb->table('gd_combos')->insert([
                'vendor_id'  => $vendorId,
                'name'       => 'სასმელი',
                'type'       => 'choice',
                'required'   => 1,
                'max_select' => 1,
                'sort_order' => $sort,
                'active'     => 1,
            ]);
            $this->setComboProducts($gid, $drinkItems);
            $this->qb->table('gd_product_combos')->insert(['product_id'=>$productId,'combo_id'=>$gid,'sort_order'=>$sort]);
        }

        return $productId;
    }

    /**
     * Returns all vendor products that have at least one combo group attached,
     * each decorated with mandatory/optional/drinks arrays.
     */
    public function vendorComboMeals(int $vendorId): array
    {
        try {
            $prods = $this->vendorProducts($vendorId);
            $meals = [];
            foreach ($prods as $p) {
                $groups = $this->productCombos((int)$p['id']);
                if (!$groups) continue;
                $meal = (array)$p;
                $meal['mandatory'] = [];
                $meal['extras']    = [];   // [{id, name, products:[{id,name}]}]
                $meal['drinks']    = [];
                foreach ($groups as $g) {
                    $type  = $g['type'] ?? 'choice';
                    $req   = (int)($g['required'] ?? 0);
                    $plist = array_map(
                        fn($pr) => ['id' => (int)$pr['id'], 'name' => $pr['name']],
                        $g['products'] ?? []
                    );
                    if ($type === 'included') {
                        $meal['mandatory'] = $plist;
                    } elseif ($type === 'choice' && $req === 0) {
                        $meal['extras'][] = [
                            'id'       => (int)$g['id'],
                            'name'     => $g['name'],
                            'products' => $plist,
                        ];
                    } elseif ($type === 'choice' && $req === 1) {
                        $meal['drinks'] = $plist;
                    }
                }
                $meals[] = $meal;
            }
            return $meals;
        } catch (\Throwable) {
            return [];
        }
    }

    /** Delete a combo-meal product and its dedicated combo groups. */
    public function deleteComboMeal(int $vendorId, int $productId): void
    {
        $p = $this->getProduct($productId);
        if (!$p || (int)$p['vendor_id'] !== $vendorId) return;
        $links = $this->qb->table('gd_product_combos')
            ->where('product_id', '=', $productId)->get() ?: [];
        foreach ($links as $link) {
            $gid = (int)$link['combo_id'];
            $this->qb->table('gd_product_combos')
                ->where('product_id', '=', $productId)
                ->where('combo_id',   '=', $gid)->delete();
            $other = $this->qb->table('gd_product_combos')
                ->where('combo_id', '=', $gid)->first();
            if (!$other) {
                $this->qb->table('gd_combo_products')->where('combo_id', '=', $gid)->delete();
                $this->qb->table('gd_combos')->where('id', '=', $gid)->delete();
            }
        }
        $this->deleteProduct($productId);
    }

    /**
     * Apply active offers to cart and return discount amount + label.
     * Returns ['discount' => float, 'free_delivery' => bool, 'label' => string]
     */
    public function applyOffers(int $vendorId, float $subtotal): array
    {
        $offers      = $this->activeOffers($vendorId);
        $discount    = 0.0;
        $freeDelivery= false;
        $labels      = [];

        foreach ($offers as $offer) {
            $minOk = $subtotal >= (float)$offer['min_order'];
            if (!$minOk) continue;

            if ($offer['applies_to'] === 'order') {
                if ($offer['type'] === 'percent') {
                    $d = round($subtotal * (float)$offer['value'] / 100, 2);
                    $discount += $d;
                    $labels[] = $offer['name'].' (-'.$offer['value'].'%)';
                } elseif ($offer['type'] === 'fixed') {
                    $discount += (float)$offer['value'];
                    $labels[] = $offer['name'].' (-'.$offer['value'].'₾)';
                } elseif ($offer['type'] === 'free_delivery') {
                    $freeDelivery = true;
                    $labels[] = $offer['name'].' (Free delivery)';
                }
            }
        }

        return [
            'discount'      => min($discount, $subtotal),
            'free_delivery' => $freeDelivery,
            'label'         => implode(', ', $labels),
        ];
    }
}
