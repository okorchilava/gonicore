<?php
declare(strict_types=1);

use GCRating\GCRatingAdminController;
use GCRating\GCRatingService;
use GoniCore\Core\Config\Config;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ─────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCRating\\')) return;
    $rel  = substr($class, strlen('GCRating\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    /** @var Connection $conn */
    $conn = $container->get(Connection::class);
    $cnt  = $conn->scalar(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcrating_sessions'"
    );
    if ((int)$cnt === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI Bindings ────────────────────────────────────────────────────────────────

$container->singleton(GCRatingService::class,
    static fn($c) => new GCRatingService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GCRatingAdminController::class,
    static fn($c) => new GCRatingAdminController(
        $c->get(GCRatingService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string)$c->get(Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Register singleton instance ────────────────────────────────────────────────

GCRatingService::register($container->get(GCRatingService::class));

// ── Frontend tracking JS injection ─────────────────────────────────────────────
//
// Injects a small async script before </body> on every non-admin page.
// The script uses sessionStorage + localStorage for visitor identification
// so no cookies are set. IP addresses are never sent to the server.

(static function () use ($container, $pluginDir): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    // Skip admin pages entirely
    if (str_contains($uri, '/manage/') || str_contains($uri, '/login')) return;
    // Skip the tracking endpoints themselves
    if (str_contains($uri, '/gcrating/')) return;

    ob_start(static function (string $buffer) use ($container): string {
        try {
            /** @var GCRatingService $svc */
            $svc = GCRatingService::getInstance();
            if (!$svc || $svc->getSetting('enabled', '1') !== '1') return $buffer;

            // Exclude logged-in admin users if the setting is on
            if ((bool)(int)$svc->getSetting('exclude_admin', '1')) {
                try {
                    /** @var LoginService $auth */
                    $auth = $container->get(LoginService::class);
                    if ($auth->isLoggedIn()) return $buffer;
                } catch (\Throwable) {}
            }

            if (!str_contains($buffer, '</body>')) return $buffer;

            $base = defined('BASE_URL') ? rtrim((string)\constant('BASE_URL'), '/') : '';

            $script = <<<SCRIPT
<script>
(function(){
var _gcr_base='{$base}';
var _gcr_vid=localStorage.getItem('gcr_vid');
if(!_gcr_vid){_gcr_vid=Math.random().toString(36).slice(2,18)+Math.random().toString(36).slice(2,6);localStorage.setItem('gcr_vid',_gcr_vid);}
var _gcr_sid=sessionStorage.getItem('gcr_sid');
if(!_gcr_sid){_gcr_sid=Math.random().toString(36).slice(2,18)+Math.random().toString(36).slice(2,6);sessionStorage.setItem('gcr_sid',_gcr_sid);}
var _gcr_start=Date.now();
var _gcr_pv=null;
var _gcr_tracked=false;
function gcrGetUtm(k){var p=new URLSearchParams(location.search);return p.get(k)||'';}
function gcrTrack(){
  if(_gcr_tracked)return;_gcr_tracked=true;
  var payload={
    sid:_gcr_sid,vid:_gcr_vid,
    url:location.pathname+(location.search||''),
    title:document.title,
    ref:document.referrer,
    w:screen.width,touch:('ontouchstart' in window),
    utm_source:gcrGetUtm('utm_source'),
    utm_medium:gcrGetUtm('utm_medium'),
    utm_campaign:gcrGetUtm('utm_campaign')
  };
  fetch(_gcr_base+'/gcrating/track',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(payload),
    keepalive:true
  }).then(function(r){return r.json();}).then(function(d){
    if(d.ok){_gcr_pv={pv_id:d.pv_id,sid:d.sid};}
  }).catch(function(){});
}
function gcrDuration(){
  if(!_gcr_pv)return;
  var sec=Math.round((Date.now()-_gcr_start)/1000);
  if(sec<1)return;
  var payload=JSON.stringify({pv_id:_gcr_pv.pv_id,sid:_gcr_pv.sid,seconds:sec});
  if(navigator.sendBeacon){
    navigator.sendBeacon(_gcr_base+'/gcrating/duration',new Blob([payload],{type:'application/json'}));
  }
}
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',gcrTrack);}else{gcrTrack();}
window.addEventListener('pagehide',gcrDuration);
document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden')gcrDuration();});
})();
</script>
SCRIPT;

            return str_replace('</body>', $script . '</body>', $buffer);
        } catch (\Throwable) {
            return $buffer;
        }
    });
})();

// ── Retention cleanup (once per ~100 requests) ─────────────────────────────────

(static function () use ($container): void {
    if (mt_rand(1, 100) !== 1) return;
    try {
        /** @var GCRatingService $svc */
        $svc  = $container->get(GCRatingService::class);
        $days = (int)$svc->getSetting('retention_days', '365');
        if ($days > 0) $svc->cleanup($days);
    } catch (\Throwable) {}
})();

// ── Routes ─────────────────────────────────────────────────────────────────────

// Public tracking endpoints (no auth required)
$router->post('/gcrating/track',    [GCRatingAdminController::class, 'apiTrack']);
$router->post('/gcrating/duration', [GCRatingAdminController::class, 'apiDuration']);

// Admin panel
$router->group('/manage/gcrating', static function ($r) use ($container): void {
    $r->get('',           [GCRatingAdminController::class, 'dashboard']);
    $r->get('/pages',     [GCRatingAdminController::class, 'pages']);
    $r->get('/referrers', [GCRatingAdminController::class, 'referrers']);
    $r->get('/settings',  [GCRatingAdminController::class, 'settings']);
    $r->post('/settings', [GCRatingAdminController::class, 'settingsSave']);
    $r->post('/clear',    [GCRatingAdminController::class, 'clearData']);
});

// ── Sidebar nav ─────────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gcrating');
    $cls   = $isAct ? ' active' : '';
    echo '<li>'
       . '<a href="' . $h($base . '/manage/gcrating') . '" class="' . $cls . '">'
       . '<span class="nav-icon">📊</span> GCRating'
       . '</a>'
       . '</li>';
}, 45);
