<?php
declare(strict_types=1);

use GcPolicy\GcPolicyAdminController;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GcPolicy\\')) return;
    $rel  = substr($class, strlen('GcPolicy\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ──────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcpolicy_settings'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->bind(GcPolicyAdminController::class,
    static fn($c) => new GcPolicyAdminController(
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Admin Routes ──────────────────────────────────────────────────────────────

$router->group('/manage/gcpolicy', static function ($r) use ($container): void {
    $r->get('',           [GcPolicyAdminController::class, 'settings']);
    $r->get('/settings',  [GcPolicyAdminController::class, 'settings']);
    $r->post('/settings', [GcPolicyAdminController::class, 'settingsSave']);
});

// ── Sidebar Nav Hook ──────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gcpolicy');
    $open  = $isAct ? ' open' : '';
    $sub   = static function (string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">🍪</span> GCpolicy'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/gcpolicy/settings', '⚙', 'Settings', 'gcpolicy-settings')
       . '</ul>'
       . '</li>';
}, 35);

// ── Global consent helper (usable by themes & other plugins) ──────────────────
//
//   gc_cookie_consent()           → '' | 'accepted' | 'declined'
//   gc_cookie_consent_accepted()  → bool
//
if (!function_exists('gc_cookie_consent')) {
    function gc_cookie_consent(): string
    {
        return $_COOKIE['gc_consent'] ?? '';
    }
}
if (!function_exists('gc_cookie_consent_accepted')) {
    function gc_cookie_consent_accepted(): bool
    {
        return ($_COOKIE['gc_consent'] ?? '') === 'accepted';
    }
}

// ── Frontend Cookie Banner Injection ─────────────────────────────────────────
// • Skip entirely on /manage pages
// • Skip if the visitor already has a gc_consent cookie (PHP-side, zero flash)

$_gcpPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$_gcpHasConsent = isset($_COOKIE['gc_consent']) && $_COOKIE['gc_consent'] !== '';

if (!str_contains($_gcpPath, '/manage') && !$_gcpHasConsent) {
    try {
        $qb = $container->get(QueryBuilder::class);

        $_gcpGet = static function (string $key, string $default) use ($qb): string {
            try {
                $row = $qb->table('gcpolicy_settings')->where('key', '=', $key)->first();
                return ($row && $row['value'] !== '') ? (string) $row['value'] : $default;
            } catch (\Throwable) {
                return $default;
            }
        };

        if ($_gcpGet('enabled', '1') === '1') {
            $gcpText        = $_gcpGet('text',         'ვებსაიტი იყენებს "ქუქი ჩანაწერებს". დამატებითი ინფორმაციის მისაღებად იხილეთ:');
            $gcpLinkText    = $_gcpGet('link_text',    'Cookie პოლიტიკა');
            $gcpLinkUrl     = $_gcpGet('link_url',     '#');
            $gcpBtnText     = $_gcpGet('btn_text',     'კეთილი');
            $gcpShowDecline = $_gcpGet('show_decline', '1');
            $gcpDeclineText = $_gcpGet('decline_text', 'უარყოფა');
            $gcpExpireDays  = max(1, (int) $_gcpGet('expire_days', '365'));
            $gcpPos         = $_gcpGet('position',     'bottom');
            $gcpPosClass    = $gcpPos === 'top' ? ' gc-cb-top' : '';

            // ── Decline button HTML (optional) ────────────────────────────────
            $gcpDeclineBtn = $gcpShowDecline === '1'
                ? '<button class="gc-cb-btn-decline" onclick="gcCbDecline()">'
                  . htmlspecialchars($gcpDeclineText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                  . '</button>'
                : '';

            // ── Full banner HTML ──────────────────────────────────────────────
            $gcpBannerHtml =
                  '<style>'
                . '#gc-cookie-bar{'
                .   'position:fixed;bottom:0;left:0;right:0;z-index:99999;'
                .   'transform:translateY(110%);transition:transform .38s cubic-bezier(.4,0,.2,1);'
                .   'background:#1e293b;border-top:1px solid rgba(255,255,255,.08);'
                .   'padding:14px 24px;display:flex;align-items:center;gap:14px;'
                .   'box-shadow:0 -4px 28px rgba(0,0,0,.32);'
                .   'font-family:"Noto Sans Georgian",system-ui,sans-serif}'
                . '#gc-cookie-bar.gc-cb-top{'
                .   'bottom:auto;top:0;transform:translateY(-110%);'
                .   'border-top:none;border-bottom:1px solid rgba(255,255,255,.08);'
                .   'box-shadow:0 4px 28px rgba(0,0,0,.32)}'
                . '#gc-cookie-bar.gc-cb-in{transform:translateY(0)!important}'
                . '.gc-cb-icon{font-size:26px;flex-shrink:0;line-height:1}'
                . '.gc-cb-text{flex:1;font-size:13.5px;color:#cbd5e1;line-height:1.55}'
                . '.gc-cb-text a{color:#a78bfa;text-decoration:underline;text-underline-offset:2px}'
                . '.gc-cb-text a:hover{color:#c4b5fd}'
                . '.gc-cb-btns{display:flex;gap:8px;align-items:center;flex-shrink:0}'
                . '.gc-cb-btn-decline{'
                .   'background:transparent;color:#94a3b8;'
                .   'border:1px solid rgba(255,255,255,.18);border-radius:10px;'
                .   'padding:9px 18px;font-size:13.5px;font-weight:600;cursor:pointer;'
                .   'white-space:nowrap;font-family:inherit;transition:background .15s,color .15s}'
                . '.gc-cb-btn-decline:hover{background:rgba(255,255,255,.1);color:#f1f5f9}'
                . '.gc-cb-btn-accept{'
                .   'background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;'
                .   'border:none;border-radius:10px;padding:9px 22px;'
                .   'font-size:13.5px;font-weight:700;cursor:pointer;'
                .   'white-space:nowrap;font-family:inherit;transition:opacity .15s}'
                . '.gc-cb-btn-accept:hover{opacity:.88}'
                . 'html.gt-light #gc-cookie-bar{'
                .   'background:#fff;border-color:rgba(0,0,0,.1);'
                .   'box-shadow:0 -4px 24px rgba(0,0,0,.08)}'
                . 'html.gt-light #gc-cookie-bar.gc-cb-top{box-shadow:0 4px 24px rgba(0,0,0,.08)}'
                . 'html.gt-light .gc-cb-text{color:#374151}'
                . 'html.gt-light .gc-cb-btn-decline{'
                .   'color:#475569;border-color:rgba(0,0,0,.18)}'
                . 'html.gt-light .gc-cb-btn-decline:hover{'
                .   'background:rgba(0,0,0,.05);color:#1e293b}'
                . '@media(max-width:600px){'
                .   '#gc-cookie-bar{flex-wrap:wrap;gap:10px;padding:14px 16px}'
                .   '.gc-cb-btns{width:100%;gap:8px}'
                .   '.gc-cb-btn-decline,.gc-cb-btn-accept{flex:1;text-align:center;padding:11px}}'
                . '</style>'
                . '<div id="gc-cookie-bar" class="' . htmlspecialchars($gcpPosClass, ENT_QUOTES, 'UTF-8') . '">'
                .   '<span class="gc-cb-icon">🍪</span>'
                .   '<span class="gc-cb-text">'
                .     htmlspecialchars($gcpText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .     ' <a href="' . htmlspecialchars($gcpLinkUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .     '" target="_blank" rel="noopener">'
                .     htmlspecialchars($gcpLinkText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .     '</a>.</span>'
                .   '<div class="gc-cb-btns">'
                .     $gcpDeclineBtn
                .     '<button class="gc-cb-btn-accept" onclick="gcCbAccept()">'
                .     htmlspecialchars($gcpBtnText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .     '</button>'
                .   '</div>'
                . '</div>'
                . '<script>'
                // No JS cookie-check needed: PHP already skips banner when cookie exists.
                // Just slide in after a short delay.
                .   '(function(){'
                .     'var b=document.getElementById("gc-cookie-bar");'
                .     'if(b)setTimeout(function(){b.classList.add("gc-cb-in");},350);'
                .   '})();'
                // Set cookie helper
                .   'var _gcDays=' . $gcpExpireDays . ';'
                .   'function _gcSetCookie(v){'
                .     'document.cookie="gc_consent="+v+"; max-age="+(_gcDays*86400)+"; path=/; SameSite=Lax";'
                .   '}'
                // Hide helper
                .   'function _gcHide(){'
                .     'var b=document.getElementById("gc-cookie-bar");'
                .     'if(b){b.classList.remove("gc-cb-in");'
                .     'setTimeout(function(){if(b.parentNode)b.remove();},420);}'
                .   '}'
                // Accept
                .   'function gcCbAccept(){_gcSetCookie("accepted");_gcHide();}'
                // Decline
                .   'function gcCbDecline(){_gcSetCookie("declined");_gcHide();}'
                // Reset (call this to show banner again, e.g. from a footer "Cookie Settings" link)
                .   'function gcCbReset(){'
                .     'document.cookie="gc_consent=; max-age=0; path=/; SameSite=Lax";'
                .     'location.reload();'
                .   '}'
                . '</script>';

            // Inject just before </body> — only runs when no cookie found server-side
            ob_start(static function (string $buffer) use ($gcpBannerHtml): string {
                $pos = strrpos($buffer, '</body>');
                if ($pos === false) return $buffer;
                return substr($buffer, 0, $pos) . $gcpBannerHtml . substr($buffer, $pos);
            });
        }
    } catch (\Throwable) {}
}
unset($_gcpPath, $_gcpHasConsent);
