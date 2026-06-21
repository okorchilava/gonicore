<?php

declare(strict_types=1);

namespace GCLazyLoader;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;
use GoniCore\Modules\Settings\SettingsService;

/**
 * Settings page for GC Lazy Loader, rendered inside the GoniCore manage layout.
 */
final class LazyLoaderAdmin
{
    /** Boolean (checkbox) settings + their defaults. */
    public const BOOLS = [
        'lazyload_images'     => '1',
        'lazyload_iframes'    => '1',
        'lazyload_fade'       => '1',
        'lazyload_pageloader' => '1',
    ];

    /** Page-transition loader style. */
    public const STYLES = ['bar' => 'Top progress bar', 'overlay' => 'Full-screen spinner'];

    /** Spinner animations. */
    public const SPINNERS = ['ring' => 'Ring', 'dual' => 'Dual ring', 'dots' => 'Dots', 'pulse' => 'Pulse', 'bars' => 'Bars'];

    public const DEFAULT_COLOR = '#10B27C';

    /** Markup for a spinner of the given type (CSS in spinnerCss()). */
    public static function spinnerHtml(string $type): string
    {
        return match ($type) {
            'dots'  => '<span class="gcsp gcsp-dots"><i></i><i></i><i></i></span>',
            'bars'  => '<span class="gcsp gcsp-bars"><i></i><i></i><i></i><i></i></span>',
            'dual'  => '<span class="gcsp gcsp-dual"></span>',
            'pulse' => '<span class="gcsp gcsp-pulse"></span>',
            default => '<span class="gcsp gcsp-ring"></span>',
        };
    }

    /** Shared spinner CSS (used by the frontend loader AND the admin preview). */
    public static function spinnerCss(): string
    {
        return <<<'CSS'
.gcsp{--s:22px;--c:#10B27C;display:inline-block;line-height:0}
.gcsp-ring{width:var(--s);height:var(--s);border:3px solid rgba(120,120,120,.25);border-top-color:var(--c);border-radius:50%;animation:gcspin .6s linear infinite}
.gcsp-dual{width:var(--s);height:var(--s);border:3px solid transparent;border-top-color:var(--c);border-bottom-color:var(--c);border-radius:50%;animation:gcspin .8s linear infinite}
.gcsp-pulse{width:var(--s);height:var(--s);background:var(--c);border-radius:50%;animation:gcpulse 1s ease-in-out infinite}
.gcsp-dots{display:inline-flex;gap:calc(var(--s)/5);align-items:center}
.gcsp-dots i{width:calc(var(--s)/3.5);height:calc(var(--s)/3.5);background:var(--c);border-radius:50%;display:block;animation:gcbounce .6s infinite alternate}
.gcsp-dots i:nth-child(2){animation-delay:.2s}.gcsp-dots i:nth-child(3){animation-delay:.4s}
.gcsp-bars{display:inline-flex;gap:calc(var(--s)/7);align-items:flex-end;height:var(--s)}
.gcsp-bars i{width:calc(var(--s)/6);height:100%;background:var(--c);display:block;transform-origin:bottom;animation:gcbars .9s ease-in-out infinite}
.gcsp-bars i:nth-child(2){animation-delay:.15s}.gcsp-bars i:nth-child(3){animation-delay:.3s}.gcsp-bars i:nth-child(4){animation-delay:.45s}
@keyframes gcspin{to{transform:rotate(360deg)}}
@keyframes gcpulse{0%,100%{transform:scale(.5);opacity:.4}50%{transform:scale(1);opacity:1}}
@keyframes gcbounce{to{transform:translateY(-8px);opacity:.45}}
@keyframes gcbars{0%,100%{transform:scaleY(.4)}50%{transform:scaleY(1)}}
CSS;
    }

    public function __construct(
        private readonly LoginService        $auth,
        private readonly SessionManager      $session,
        private readonly SettingsService     $settings,
        private readonly QueryBuilder        $qb,
        private readonly HookManager         $hooks,
        private readonly LanguageService     $langService,
        private readonly LanguageRepository  $langRepo,
        private readonly NotificationService $notifications,
        private readonly string              $siteName = 'GoniCore',
    ) {}

    private function flash(string $msg, string $icon = 'success'): void
    {
        $this->session->flash('gc_msg',  $msg);
        $this->session->flash('gc_icon', $icon);
    }

    /** GET /manage/lazyloader */
    public function settings(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }
        return $this->renderPage($request, 'settings', [
            'base'   => $request->basePath(),
            'values' => $this->currentValues(),
        ]);
    }

    /** POST /manage/lazyloader */
    public function save(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }
        if (!$this->session->verifyCsrf((string) $request->post('_csrf', ''))) {
            $this->flash('Security token expired — please try again.', 'error');
            return Response::redirect($request->basePath() . '/manage/lazyloader');
        }

        // Booleans (checkboxes).
        foreach (array_keys(self::BOOLS) as $key) {
            $this->settings->set($key, $request->post($key) !== null ? '1' : '0');
        }

        // Loader style (whitelisted).
        $style = (string) $request->post('lazyload_loader_style', 'bar');
        $this->settings->set('lazyload_loader_style', isset(self::STYLES[$style]) ? $style : 'bar');

        // Spinner animation (whitelisted).
        $spin = (string) $request->post('lazyload_spinner', 'ring');
        $this->settings->set('lazyload_spinner', isset(self::SPINNERS[$spin]) ? $spin : 'ring');

        // Accent color — must be a valid hex to be safely inlined into CSS.
        $color = (string) $request->post('lazyload_color', self::DEFAULT_COLOR);
        $this->settings->set('lazyload_color', preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : self::DEFAULT_COLOR);

        $this->flash('Lazy Loader settings saved.');
        return Response::redirect($request->basePath() . '/manage/lazyloader');
    }

    /** @return array<string,mixed> */
    private function currentValues(): array
    {
        $out = [];
        foreach (self::BOOLS as $key => $default) {
            $out[$key] = $this->settings->get($key, $default) === '1';
        }
        $out['lazyload_loader_style'] = (string) $this->settings->get('lazyload_loader_style', 'bar');
        $out['lazyload_spinner']      = (string) $this->settings->get('lazyload_spinner', 'ring');
        $out['lazyload_color']        = (string) $this->settings->get('lazyload_color', self::DEFAULT_COLOR);
        return $out;
    }

    /** @param array<string,mixed> $data */
    private function renderPage(Request $request, string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        // The shared manage layout (sidebar/topbar) translates itself via the
        // engine's t(), which auto-boots the language — so this plugin does NOT
        // touch the engine language pack. This plugin's OWN strings come from
        // its own pack (plugins/gc-lazyloader/lang/*.php) via $t().
        $t = gc_plugin_translator(dirname(__DIR__));

        $base     = $data['base'] ?? $request->basePath();
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        // Real notifications + language switcher so the topbar matches core pages.
        $notifList       = $user ? $this->notifications->forUser((int) $user['id']) : [];
        $notifUnread     = $user ? $this->notifications->unreadCount((int) $user['id']) : 0;
        $panelLangs      = $this->langRepo->allActive();
        $currentLangCode = $this->langService->currentCode();

        // Flash + CSRF for the manage layout
        $flashMsg  = $this->session->getFlash('gc_msg');
        $flashIcon = $this->session->getFlash('gc_icon') ?? 'success';
        $csrfToken = $this->session->csrfToken();

        $activeNav = 'lazyloader';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/' . $view . '.php';
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
