<?php
declare(strict_types=1);

namespace GoniDelivery;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Mail\MailService;
use GoniCore\Modules\Login\LoginService;

final class AdminController
{
    public function __construct(
        private readonly DeliveryService $delivery,
        private readonly QueryBuilder    $qb,
        private readonly LoginService    $auth,
        private readonly HookManager     $hooks,
        private readonly MailService     $mail,
        private readonly string          $siteName = 'GoniCore',
    ) {}

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $stats   = $this->delivery->globalStats();
        $recent  = $this->delivery->allOrders(1, 8)['items'];
        $drivers = $this->delivery->allDrivers(true);
        return $this->page('dashboard', compact('stats','recent','drivers') + ['base' => $r->basePath(), 'delivery' => $this->delivery]);
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function orders(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page   = max(1, (int) $r->query('page', '1'));
        $status = (string) $r->query('status', '');
        $type   = (string) $r->query('type', '');
        $data   = $this->delivery->allOrders($page, 25, $status, $type);
        return $this->page('orders', $data + [
            'base'     => $r->basePath(),
            'page'     => $page,
            'filterStatus' => $status,
            'filterType'   => $type,
            'delivery' => $this->delivery,
        ]);
    }

    public function orderView(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id    = (int) $r->getAttribute('id');
        $order = $this->delivery->getOrder($id);
        if (!$order) return Response::redirect($r->basePath() . '/manage/delivery/orders');
        return $this->page('order', [
            'base'     => $r->basePath(),
            'order'    => $order,
            'drivers'  => $this->delivery->allDrivers(true),
            'delivery' => $this->delivery,
            'flash'    => $r->query('msg', ''),
            'error'    => $r->query('err', ''),
        ]);
    }

    public function orderUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->getAttribute('id');
        $newDriverId = $r->post('driver_id') ? (int) $r->post('driver_id') : null;
        $update = [
            'driver_id'      => $newDriverId,
            'driver_note'    => trim((string) $r->post('driver_note', '')),
            'payment_status' => (string) $r->post('payment_status', 'unpaid'),
        ];
        // Only set status if the form explicitly posted it (status select removed from UI)
        if ((string)$r->post('status','') !== '') {
            $update['status'] = (string) $r->post('status');
        } elseif ($newDriverId) {
            // Admin manually assigned a courier — move to 'accepted' so courier portal sees it
            $existing = $this->delivery->getOrder($id);
            if ($existing && in_array($existing['status'], ['pending'], true)) {
                $update['status']                = 'accepted';
                $update['courier_dispatched_at'] = date('Y-m-d H:i:s');
            }
        }
        $this->delivery->updateOrder($id, $update);
        return Response::redirect($r->basePath() . '/manage/delivery/orders/' . $id . '?msg=Updated.');
    }

    // ── Drivers ───────────────────────────────────────────────────────────────

    public function drivers(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $drivers = $this->delivery->allDrivers();
        return $this->page('drivers', ['base' => $r->basePath(), 'drivers' => $drivers, 'delivery' => $this->delivery]);
    }

    public function driverCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->createDriver([
            'name'         => trim((string) $r->post('name', '')),
            'phone'        => trim((string) $r->post('phone', '')),
            'email'        => trim((string) $r->post('email', '')),
            'vehicle_type' => trim((string) $r->post('vehicle_type', '')),
            'vehicle_num'  => trim((string) $r->post('vehicle_num', '')),
            'status'       => $r->post('status', 'active') === 'active' ? 'active' : 'inactive',
            'notes'        => trim((string) $r->post('notes', '')),
        ]);
        return Response::redirect($r->basePath() . '/manage/delivery/drivers?saved=1');
    }

    public function driverUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->getAttribute('id');
        $this->delivery->updateDriver($id, [
            'name'         => trim((string) $r->post('name', '')),
            'phone'        => trim((string) $r->post('phone', '')),
            'email'        => trim((string) $r->post('email', '')),
            'vehicle_type' => trim((string) $r->post('vehicle_type', '')),
            'vehicle_num'  => trim((string) $r->post('vehicle_num', '')),
            'status'       => $r->post('status', 'active') === 'active' ? 'active' : 'inactive',
            'notes'        => trim((string) $r->post('notes', '')),
        ]);
        return Response::redirect($r->basePath() . '/manage/delivery/drivers?saved=1');
    }

    public function driverDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->deleteDriver((int) $r->getAttribute('id'));
        return Response::redirect($r->basePath() . '/manage/delivery/drivers');
    }

    // ── Zones ─────────────────────────────────────────────────────────────────

    public function zones(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $zones = $this->delivery->allZones();
        return $this->page('zones', ['base' => $r->basePath(), 'zones' => $zones, 'delivery' => $this->delivery, 'saved' => $r->query('saved')==='1']);
    }

    public function zoneCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->createZone([
            'name'        => trim((string) $r->post('name', '')),
            'price'       => max(0.0, (float) $r->post('price', '0')),
            'min_order'   => max(0.0, (float) $r->post('min_order', '0')),
            'eta_minutes' => max(1, (int) $r->post('eta_minutes', '30')),
            'active'      => $r->post('active') === '1' ? 1 : 0,
            'sort_order'  => (int) $r->post('sort_order', '0'),
        ]);
        return Response::redirect($r->basePath() . '/manage/delivery/zones?saved=1');
    }

    public function zoneUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->getAttribute('id');
        $this->delivery->updateZone($id, [
            'name'        => trim((string) $r->post('name', '')),
            'price'       => max(0.0, (float) $r->post('price', '0')),
            'min_order'   => max(0.0, (float) $r->post('min_order', '0')),
            'eta_minutes' => max(1, (int) $r->post('eta_minutes', '30')),
            'active'      => $r->post('active') === '1' ? 1 : 0,
            'sort_order'  => (int) $r->post('sort_order', '0'),
        ]);
        return Response::redirect($r->basePath() . '/manage/delivery/zones?saved=1');
    }

    public function zoneDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->deleteZone((int) $r->getAttribute('id'));
        return Response::redirect($r->basePath() . '/manage/delivery/zones');
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settingsForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->page('settings', ['base' => $r->basePath(), 'delivery' => $this->delivery, 'saved' => $r->query('saved')==='1']);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        foreach (['currency','currency_symbol','page_slug','brand_name','phone','min_order','base_fee','food_enabled','courier_enabled','below_min_surcharge','weather_surcharge_active','weather_surcharge_amount','per_km_rate','per_km_threshold'] as $k) {
            $this->delivery->setSetting($k, trim((string) $r->post($k, '')));
        }
        return Response::redirect($r->basePath() . '/manage/delivery/settings?saved=1');
    }

    // ── Live Map ──────────────────────────────────────────────────────────────

    public function liveMap(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $drivers = $this->delivery->allDrivers();
        $orders  = $this->delivery->allOrders(1, 200)['items'];
        $vendors = $this->delivery->allVendors();
        $stats   = $this->delivery->globalStats();
        $sym     = $this->delivery->setting('currency_symbol','₾');
        return $this->page('livemap', compact('drivers','orders','vendors','stats','sym') + [
            'base'=>$r->basePath(), 'taxi'=>$this->delivery, 'activeNav'=>'delivery-livemap',
        ]);
    }

    // ── Vendors ───────────────────────────────────────────────────────────────

    public function vendors(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $vendors = $this->delivery->allVendors();
        return $this->page('vendors', compact('vendors') + ['base'=>$r->basePath(),'activeNav'=>'delivery-vendors']);
    }

    public function vendorCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->page('vendor_edit', ['vendor'=>null,'isNew'=>true,'base'=>$r->basePath(),'activeNav'=>'delivery-vendors']);
    }

    public function vendorCreatePost(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $data = $this->vendorFormData($r);
        $id   = $this->delivery->createVendor($data);
        return Response::redirect($r->basePath().'/manage/delivery/vendors/'.$id.'?saved=1');
    }

    public function vendorEdit(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $vendor = $this->delivery->getVendor((int)$r->getAttribute('id'));
        if (!$vendor) return Response::redirect($r->basePath().'/manage/delivery/vendors');
        return $this->page('vendor_edit', compact('vendor') + ['isNew'=>false,'base'=>$r->basePath(),'activeNav'=>'delivery-vendors']);
    }

    public function vendorUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int)$r->getAttribute('id');
        $this->delivery->updateVendor($id, $this->vendorFormData($r));
        return Response::redirect($r->basePath().'/manage/delivery/vendors/'.$id.'?saved=1');
    }

    public function vendorDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->deleteVendor((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/delivery/vendors?deleted=1');
    }

    public function vendorRegenToken(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->regenVendorToken((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/delivery/vendors/'.(int)$r->getAttribute('id').'?token=1');
    }

    private function vendorFormData(Request $r): array
    {
        return [
            'name'           => trim((string)$r->post('name','')),
            'slug'           => trim((string)$r->post('slug','')),
            'description'    => trim((string)$r->post('description','')),
            'phone'          => trim((string)$r->post('phone','')),
            'email'          => trim((string)$r->post('email','')),
            'address'        => trim((string)$r->post('address','')),
            'lat'            => $r->post('lat') !== '' ? (float)$r->post('lat') : null,
            'lng'            => $r->post('lng') !== '' ? (float)$r->post('lng') : null,
            'category'       => (string)$r->post('category','restaurant'),
            'cuisine_tags'   => trim((string)$r->post('cuisine_tags','')),
            'open_time'      => $r->post('open_time') ?: null,
            'close_time'     => $r->post('close_time') ?: null,
            'days_open'      => implode('', (array)($r->post('days_open') ?? [])) ?: '1234567',
            'prep_time_min'  => max(1,(int)$r->post('prep_time_min','20')),
            'min_order'      => (float)$r->post('min_order','0'),
            'delivery_fee'   => (float)$r->post('delivery_fee','3'),
            'free_delivery_threshold' => (float)$r->post('free_delivery_threshold','0'),
            'commission_pct' => (float)$r->post('commission_pct','15'),
            'status'         => (string)$r->post('status','active'),
            'is_featured'    => $r->post('is_featured') ? 1 : 0,
        ];
    }

    // ── Catalog ───────────────────────────────────────────────────────────────

    public function catalog(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $vendor     = $this->delivery->getVendor((int)$r->getAttribute('id'));
        if (!$vendor) return Response::redirect($r->basePath().'/manage/delivery/vendors');
        $categories = $this->delivery->vendorCategories((int)$vendor['id']);
        $products   = $this->delivery->vendorProducts((int)$vendor['id']);
        foreach ($products as &$p) {
            $groups = $this->delivery->getProductWithModifiers((int)$p['id']);
            $p['modifier_groups'] = $groups['modifier_groups'] ?? [];
        }
        return $this->page('catalog', compact('vendor','categories','products') + ['base'=>$r->basePath(),'activeNav'=>'delivery-vendors']);
    }

    public function categoryCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $vid = (int)$r->getAttribute('id');
        $this->delivery->createCategory(['vendor_id'=>$vid,'name'=>trim((string)$r->post('name','')),'sort_order'=>(int)$r->post('sort_order',0),'active'=>1]);
        return Response::redirect($r->basePath().'/manage/delivery/vendors/'.$vid.'/catalog?saved=1');
    }
    public function categoryUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $cat = $this->delivery->getCategory((int)$r->getAttribute('id'));
        $this->delivery->updateCategory((int)$r->getAttribute('id'),['name'=>trim((string)$r->post('name','')),'sort_order'=>(int)$r->post('sort_order',0),'active'=>$r->post('active')?1:0]);
        return Response::json(['ok'=>true]);
    }
    public function categoryDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->deleteCategory((int)$r->getAttribute('id'));
        return Response::json(['ok'=>true]);
    }

    public function productCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $vid = (int)$r->getAttribute('id');
        $this->delivery->createProduct([
            'vendor_id'   => $vid,
            'category_id' => $r->post('category_id') ? (int)$r->post('category_id') : null,
            'name'        => trim((string)$r->post('name','')),
            'description' => trim((string)$r->post('description','')),
            'price'       => (float)$r->post('price','0'),
            'compare_price'=> $r->post('compare_price') ? (float)$r->post('compare_price') : null,
            'in_stock'    => 1, 'active' => 1,
            'sort_order'  => (int)$r->post('sort_order',0),
        ]);
        return Response::redirect($r->basePath().'/manage/delivery/vendors/'.$vid.'/catalog?saved=1');
    }
    public function productUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $pid = (int)$r->getAttribute('id');
        $this->delivery->updateProduct($pid,[
            'name'        => trim((string)$r->post('name','')),
            'description' => trim((string)$r->post('description','')),
            'price'       => (float)$r->post('price','0'),
            'compare_price'=> $r->post('compare_price') ? (float)$r->post('compare_price') : null,
            'category_id' => $r->post('category_id') ? (int)$r->post('category_id') : null,
            'sort_order'  => (int)$r->post('sort_order',0),
            'active'      => $r->post('active') ? 1 : 0,
        ]);
        return Response::json(['ok'=>true]);
    }
    public function productDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->deleteProduct((int)$r->getAttribute('id'));
        return Response::json(['ok'=>true]);
    }
    public function productToggleStock(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $pid = (int)$r->getAttribute('id');
        $p   = $this->delivery->getProduct($pid);
        if ($p) $this->delivery->updateProduct($pid, ['in_stock' => $p['in_stock'] ? 0 : 1]);
        return Response::json(['ok'=>true,'in_stock'=>(int)!$p['in_stock']]);
    }

    public function modifierGroupCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $this->delivery->createModifierGroup([
            'product_id'  => (int)$r->getAttribute('id'),
            'name'        => trim((string)$r->post('name','')),
            'required'    => $r->post('required') ? 1 : 0,
            'min_select'  => (int)$r->post('min_select',0),
            'max_select'  => max(1,(int)$r->post('max_select',1)),
            'sort_order'  => (int)$r->post('sort_order',0),
        ]);
        return Response::json(['ok'=>true,'id'=>$id]);
    }
    public function modifierGroupDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->deleteModifierGroup((int)$r->getAttribute('id'));
        return Response::json(['ok'=>true]);
    }
    public function modifierCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $this->delivery->createModifier([
            'group_id'   => (int)$r->post('group_id',0),
            'name'       => trim((string)$r->post('name','')),
            'price'      => (float)$r->post('price','0'),
            'in_stock'   => 1,
            'sort_order' => (int)$r->post('sort_order',0),
        ]);
        return Response::json(['ok'=>true,'id'=>$id]);
    }
    public function modifierDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->delivery->deleteModifier((int)$r->getAttribute('id'));
        return Response::json(['ok'=>true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function guard(Request $r): ?Response
    {
        return $this->auth->isLoggedIn() ? null : Response::redirect($r->basePath() . '/login');
    }

    private function page(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';
        $base = $data['base'] ?? '';
        $siteName = $this->siteName;
        $hooks    = $this->hooks;
        $userId   = $this->auth->currentUserId();
        $user     = $userId ? $this->qb->table('users')->where('id', '=', $userId)->first() : null;
        $notifList = []; $notifUnread = 0; $panelLangs = []; $currentLangCode = 'en';
        extract($data, EXTR_SKIP);
        ob_start();
        try { include __DIR__ . '/../views/admin/' . $view . '.php'; $content = (string) ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
        ob_start();
        try { include $themeDir . '/manage/layout.php'; return Response::html((string) ob_get_clean()); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }
}
