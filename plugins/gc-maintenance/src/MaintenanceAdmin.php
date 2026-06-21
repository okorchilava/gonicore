<?php

declare(strict_types=1);

namespace GCMaintenance;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;

/**
 * Admin settings for Maintenance Mode. Settings live in the core `settings`
 * table (no plugin table):
 *   maintenance_enabled  '0' | '1'
 *   maintenance_title    heading shown on the public 503 page
 *   maintenance_message  body text on the public 503 page
 */
final class MaintenanceAdmin
{
    public const DEFAULT_TITLE   = 'We\'ll be right back';
    public const DEFAULT_MESSAGE = 'The site is undergoing scheduled maintenance. Please check back soon.';

    public function __construct(
        private readonly LoginService        $auth,
        private readonly SessionManager      $session,
        private readonly QueryBuilder        $qb,
        private readonly HookManager         $hooks,
        private readonly LanguageService     $langService,
        private readonly LanguageRepository  $langRepo,
        private readonly NotificationService $notifications,
        private readonly string              $siteName = 'GoniCore',
    ) {}

    private function flash(string $msg, string $icon = 'success'): void
    {
        $this->session->flash('gc_msg', $msg);
        $this->session->flash('gc_icon', $icon);
    }

    /** GET /manage/maintenance */
    public function settings(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }
        return $this->renderPage($request, 'settings', [
            'base'    => $request->basePath(),
            'enabled' => gc_setting('maintenance_enabled', '0') === '1',
            'title'   => (string) gc_setting('maintenance_title', ''),
            'message' => (string) gc_setting('maintenance_message', ''),
        ]);
    }

    /** POST /manage/maintenance */
    public function save(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }
        if (!$this->session->verifyCsrf((string) $request->post('_csrf', ''))) {
            $this->flash('Security token expired — please try again.', 'error');
            return Response::redirect($request->basePath() . '/manage/maintenance');
        }

        $enabled = $request->post('maintenance_enabled') !== null ? '1' : '0';
        gc_set_setting('maintenance_enabled', $enabled);
        gc_set_setting('maintenance_title',   trim((string) $request->post('maintenance_title', '')));
        gc_set_setting('maintenance_message', trim((string) $request->post('maintenance_message', '')));

        $this->flash($enabled === '1' ? 'Maintenance mode is ON.' : 'Maintenance mode is OFF.');
        return Response::redirect($request->basePath() . '/manage/maintenance');
    }

    /**
     * Render the public 503 "under maintenance" page and set the status.
     * Self-contained (no theme/DB beyond gc_setting) — safe to call mid-boot.
     */
    public static function render503(): void
    {
        $title = trim((string) gc_setting('maintenance_title', ''))   ?: self::DEFAULT_TITLE;
        $msg   = trim((string) gc_setting('maintenance_message', '')) ?: self::DEFAULT_MESSAGE;
        $site  = trim((string) gc_setting('site_name', '')) ?: 'GoniCore';

        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 3600');
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>' . $e($title) . ' — ' . $e($site) . '</title><style>'
           . '*{box-sizing:border-box}body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
           . 'background:linear-gradient(135deg,#0f172a,#1e1b4b 60%,#312e81);color:#e2e8f0;'
           . 'font-family:system-ui,-apple-system,"Noto Sans Georgian",Segoe UI,sans-serif;padding:24px}'
           . '.box{max-width:520px;text-align:center}'
           . '.ic{width:84px;height:84px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;'
           . 'align-items:center;justify-content:center;margin:0 auto 24px;font-size:40px}'
           . 'h1{font-size:28px;font-weight:800;margin:0 0 14px;color:#fff;letter-spacing:-.5px}'
           . 'p{font-size:16px;line-height:1.7;color:#94a3b8;margin:0 auto;max-width:420px}'
           . '.brand{margin-top:34px;font-size:13px;color:#475569}'
           . '.brand b{color:#10B27C;font-weight:800}'
           . '</style></head><body><div class="box">'
           . '<div class="ic">🛠️</div>'
           . '<h1>' . $e($title) . '</h1>'
           . '<p>' . nl2br($e($msg)) . '</p>'
           . '<div class="brand">' . $e($site) . '</div>'
           . '</div></body></html>';
    }

    // ── Admin layout rendering ──────────────────────────────────────────────────

    /** @param array<string,mixed> $data */
    private function renderPage(Request $request, string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $base     = $data['base'] ?? $request->basePath();
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        $notifList       = $user ? $this->notifications->forUser((int) $user['id']) : [];
        $notifUnread     = $user ? $this->notifications->unreadCount((int) $user['id']) : 0;
        $panelLangs      = $this->langRepo->allActive();
        $currentLangCode = $this->langService->currentCode();

        $flashMsg  = $this->session->getFlash('gc_msg');
        $flashIcon = $this->session->getFlash('gc_icon') ?? 'success';
        $csrfToken = $this->session->csrfToken();

        $pageTitle = 'Maintenance Mode';
        $activeNav = 'maintenance';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/admin/' . $view . '.php';
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
