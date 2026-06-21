<?php
declare(strict_types=1);

namespace GoniStore;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

final class AdminController
{
    private string $viewsDir;

    public function __construct(
        private readonly StoreService $store,
        private readonly LoginService $auth,
        private readonly HookManager  $hooks,
        private readonly string       $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__) . '/views/admin';
    }

    // ── Guard ─────────────────────────────────────────────────────────────────
    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($r->basePath().'/login');
        return null;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────
    public function dashboard(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $data = $this->store->allOrders(1,5);
        $recentOrders = $data['orders'];
        $totals = [
            'orders'   => $data['total'],
            'products' => $this->store->allProducts([],1,1)['total'],
            'revenue'  => 0.0,
        ];
        return $this->render('dashboard', compact('recentOrders','totals'), $r);
    }

    // ── Products ──────────────────────────────────────────────────────────────
    public function products(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $page    = max(1,(int)$r->query('page','1'));
        $status  = $r->query('status','');
        $catId   = $r->query('category','');
        $filters = array_filter(['status'=>$status,'category_id'=>$catId?:(null)]);
        $data    = $this->store->allProducts($filters, $page, 20);
        $cats    = $this->store->allCategories();
        $success = $r->query('success');
        $error   = $r->query('error');
        return $this->render('products', array_merge($data, compact('page','status','cats','success','error')), $r);
    }

    public function productNew(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $cats    = $this->store->allCategories();
        $product = null;
        $error   = null;
        return $this->render('product_form', compact('product','cats','error'), $r);
    }

    public function productCreate(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $name  = trim((string)$r->post('name',''));
        if (!$name) return Response::redirect($r->basePath().'/manage/store/products/new?error='.urlencode('Name required.'));
        $slug  = $this->store->uniqueSlug($r->post('slug') ?: $name, 'gs_products');
        $data  = $this->productFormData($r, $slug);
        $id    = $this->store->createProduct($data);
        return Response::redirect($r->basePath().'/manage/store/products/'.$id.'/edit?success='.urlencode('Product created.'));
    }

    public function productEdit(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $id      = (int)$r->getAttribute('id');
        $product = $this->store->getProduct($id);
        if (!$product) return Response::redirect($r->basePath().'/manage/store/products');
        $cats       = $this->store->allCategories();
        $variations = $this->store->getVariations($id);
        $success    = $r->query('success');
        $error      = $r->query('error');
        return $this->render('product_form', compact('product','cats','variations','success','error'), $r);
    }

    public function productUpdate(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $id   = (int)$r->getAttribute('id');
        $p    = $this->store->getProduct($id);
        if (!$p) return Response::redirect($r->basePath().'/manage/store/products');
        $slug = $r->post('slug') ?: $p['slug'];
        $data = $this->productFormData($r, $slug);
        $this->store->updateProduct($id, $data);
        return Response::redirect($r->basePath().'/manage/store/products/'.$id.'/edit?success='.urlencode('Saved.'));
    }

    public function productDelete(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $this->store->deleteProduct((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/store/products?success='.urlencode('Product deleted.'));
    }

    private function productFormData(Request $r, string $slug): array
    {
        $images = array_filter(array_map('trim', explode("\n", (string)$r->post('images',''))));
        $galleryRaw = array_filter(array_map('trim', explode("\n", (string)$r->post('gallery',''))));
        $attrsRaw   = $r->post('attributes') ?? [];
        $attrs      = [];
        if (is_array($attrsRaw)) {
            foreach ($attrsRaw as $row) {
                if (!empty($row['name'])) $attrs[$row['name']] = $row['value'] ?? '';
            }
        }
        return [
            'category_id'       => $r->post('category_id') ? (int)$r->post('category_id') : null,
            'name'              => trim((string)$r->post('name','')),
            'slug'              => $slug,
            'short_description' => trim((string)$r->post('short_description','')),
            'description'       => trim((string)$r->post('description','')),
            'price'             => (float)str_replace(',','.',(string)$r->post('price','0')),
            'sale_price'        => $r->post('sale_price') !== '' ? (float)str_replace(',','.',(string)$r->post('sale_price')) : null,
            'sale_from'         => trim((string)$r->post('sale_from','')) ?: null,
            'sale_to'           => trim((string)$r->post('sale_to',''))   ?: null,
            'sku'               => trim((string)$r->post('sku','')),
            'stock'             => $r->post('manage_stock') ? (int)$r->post('stock',0) : null,
            'manage_stock'      => $r->post('manage_stock') ? 1 : 0,
            'weight'            => $r->post('weight') !== '' ? (float)$r->post('weight') : null,
            'images'            => array_values($images),
            'gallery'           => array_values($galleryRaw),
            'attributes'        => $attrs,
            'type'              => (string)$r->post('type','simple'),
            'status'            => (string)$r->post('status','draft'),
            'featured'          => $r->post('featured') ? 1 : 0,
            'virtual'           => $r->post('virtual') ? 1 : 0,
            'meta_title'        => trim((string)$r->post('meta_title','')),
            'meta_description'  => trim((string)$r->post('meta_description','')),
        ];
    }

    // ── Categories ────────────────────────────────────────────────────────────
    public function categories(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $cats    = $this->store->allCategories();
        $success = $r->query('success');
        $error   = $r->query('error');
        return $this->render('categories', compact('cats','success','error'), $r);
    }

    public function categoryCreate(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $name = trim((string)$r->post('name',''));
        if (!$name) return Response::redirect($r->basePath().'/manage/store/categories?error='.urlencode('Name required.'));
        $slug = $this->store->uniqueSlug($r->post('slug') ?: $name, 'gs_categories');
        $this->store->createCategory([
            'name'      => $name,
            'slug'      => $slug,
            'description' => trim((string)$r->post('description','')),
            'image'     => trim((string)$r->post('image','')),
            'parent_id' => $r->post('parent_id') ? (int)$r->post('parent_id') : null,
        ]);
        return Response::redirect($r->basePath().'/manage/store/categories?success='.urlencode('Category created.'));
    }

    public function categoryUpdate(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $id = (int)$r->getAttribute('id');
        $this->store->updateCategory($id,[
            'name'        => trim((string)$r->post('name','')),
            'description' => trim((string)$r->post('description','')),
            'image'       => trim((string)$r->post('image','')),
            'parent_id'   => $r->post('parent_id') ? (int)$r->post('parent_id') : null,
        ]);
        return Response::redirect($r->basePath().'/manage/store/categories?success='.urlencode('Updated.'));
    }

    public function categoryDelete(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $this->store->deleteCategory((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/store/categories?success='.urlencode('Deleted.'));
    }

    // ── Orders ────────────────────────────────────────────────────────────────
    public function orders(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $page    = max(1,(int)$r->query('page','1'));
        $status  = $r->query('status','');
        $data    = $this->store->allOrders($page, 20, $status);
        $statuses= StoreService::orderStatuses();
        $success = $r->query('success');
        return $this->render('orders', array_merge($data, compact('page','status','statuses','success')), $r);
    }

    public function orderView(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $id    = (int)$r->getAttribute('id');
        $order = $this->store->getOrder($id);
        if (!$order) return Response::redirect($r->basePath().'/manage/store/orders');
        $statuses = StoreService::orderStatuses();
        $success  = $r->query('success');
        return $this->render('order_view', compact('order','statuses','success'), $r);
    }

    public function orderUpdateStatus(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $id     = (int)$r->getAttribute('id');
        $status = (string)$r->post('status','');
        $note   = (string)$r->post('note','');
        if ($status) $this->store->updateOrderStatus($id, $status, $note);
        return Response::redirect($r->basePath().'/manage/store/orders/'.$id.'?success='.urlencode('Status updated.'));
    }

    // ── Coupons ───────────────────────────────────────────────────────────────
    public function coupons(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $coupons = $this->store->allCoupons();
        $success = $r->query('success');
        return $this->render('coupons', compact('coupons','success'), $r);
    }

    public function couponSave(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $data = [
            'id'        => $r->post('id') ? (int)$r->post('id') : null,
            'code'      => strtoupper(trim((string)$r->post('code',''))),
            'type'      => (string)$r->post('type','percent'),
            'value'     => (float)$r->post('value',0),
            'min_order' => (float)$r->post('min_order',0),
            'max_uses'  => $r->post('max_uses') ? (int)$r->post('max_uses') : null,
            'expires_at'=> $r->post('expires_at') ?: null,
            'active'    => $r->post('active') ? 1 : 0,
        ];
        $this->store->saveCoupon(array_filter($data, fn($v) => $v !== null));
        return Response::redirect($r->basePath().'/manage/store/coupons?success='.urlencode('Saved.'));
    }

    public function couponDelete(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $this->store->deleteCoupon((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/store/coupons?success='.urlencode('Deleted.'));
    }

    // ── Settings ──────────────────────────────────────────────────────────────
    public function storeSettings(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        require_once dirname(__DIR__,3).'/themes/default/views/helpers.php';
        $settings = $this->store->settings();
        $success  = $r->query('success');
        return $this->render('settings', compact('settings','success'), $r);
    }

    public function storeSettingsSave(Request $r): Response
    {
        if ($g = $this->guard($r)) return $g;
        $keys = ['currency','currency_symbol','currency_position','thousand_sep','decimal_sep',
                 'decimals','shop_page_slug','cart_page_slug','checkout_page_slug',
                 'tax_rate','tax_included','free_shipping_min','shipping_cost',
                 'order_email','from_email','products_per_page','shop_layout','allow_guest_checkout'];
        $data = [];
        foreach ($keys as $k) {
            $v = $r->post($k);
            if ($v !== null) $data[$k] = $v;
        }
        $this->store->bulkSettings($data);
        return Response::redirect($r->basePath().'/manage/store/settings?success='.urlencode('Saved.'));
    }

    // ── Renderer ─────────────────────────────────────────────────────────────
    /** @param array<string,mixed> $data */
    private function render(string $tpl, array $data, Request $r): Response
    {
        $viewFile = $this->viewsDir.'/'.$tpl.'.php';
        if (!is_file($viewFile)) return Response::error("View not found: $tpl", 500);
        $base      = $r->basePath();
        $siteName  = $this->siteName;
        $hooks     = $this->hooks;
        $activeNav = 'store';
        $pageTitle = 'GoniStore';
        $user      = null;
        extract($data, EXTR_SKIP);
        ob_start();
        try { include $viewFile; $content = (string)ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
        $viewsDir = dirname(__DIR__,3).'/themes/default/views/manage';
        ob_start();
        try { include $viewsDir.'/layout.php'; return Response::html((string)ob_get_clean()); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }
}
