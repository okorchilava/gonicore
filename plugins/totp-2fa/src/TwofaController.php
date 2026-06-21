<?php

declare(strict_types=1);

namespace GoniTotp;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;

final class TwofaController
{
    private const PENDING_KEY  = 'gc_2fa_pending_uid';
    private const REDIRECT_KEY = 'gc_2fa_redirect';
    private const TEMP_KEY     = 'gc_2fa_temp_secret';

    public function __construct(
        private readonly TotpService    $totp,
        private readonly QueryBuilder   $qb,
        private readonly LoginService   $loginService,
        private readonly SessionManager $session,
        private readonly HookManager    $hooks,
        private readonly string         $siteName = 'GoniCore',
    ) {}

    // ── Setup: generate secret, show QR ───────────────────────────────────────

    public function setupForm(Request $request): Response
    {
        if (!$this->loginService->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $userId = (int) $this->loginService->currentUserId();
        $user   = $this->qb->table('users')->where('id', '=', $userId)->first();

        if ($user && !empty($user['totp_enabled'])) {
            return Response::redirect($request->basePath() . '/manage/2fa');
        }

        $this->session->start();
        if (empty($_SESSION[self::TEMP_KEY])) {
            $_SESSION[self::TEMP_KEY] = $this->totp->generateSecret();
        }
        $secret  = $_SESSION[self::TEMP_KEY];
        $label   = (string) ($user['email'] ?? $this->siteName);
        $otpauth = $this->totp->getOtpauthUrl($secret, $label, $this->siteName);

        return $this->renderPage('setup', [
            'secret'  => $secret,
            'otpauth' => $otpauth,
            'error'   => $this->session->getFlash('2fa_error'),
            'base'    => $request->basePath(),
        ]);
    }

    public function enable(Request $request): Response
    {
        if (!$this->loginService->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $this->session->start();
        $secret = (string) ($_SESSION[self::TEMP_KEY] ?? '');
        $code   = trim((string) $request->post('code', ''));

        if (!$secret || !$this->totp->verify($secret, $code)) {
            $this->session->flash('2fa_error', 'Invalid code. Please try again.');
            return Response::redirect($request->basePath() . '/manage/2fa/setup');
        }

        $userId = (int) $this->loginService->currentUserId();
        $this->qb->table('users')->where('id', '=', $userId)->update([
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ]);

        unset($_SESSION[self::TEMP_KEY]);
        $this->session->flash('2fa_success', '2FA has been enabled on your account.');

        return Response::redirect($request->basePath() . '/manage/2fa');
    }

    // ── Manage: show status, allow disable ────────────────────────────────────

    public function manageForm(Request $request): Response
    {
        if (!$this->loginService->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $userId  = (int) $this->loginService->currentUserId();
        $user    = $this->qb->table('users')->where('id', '=', $userId)->first();
        $enabled = !empty($user['totp_enabled']);

        return $this->renderPage('manage', [
            'enabled' => $enabled,
            'error'   => $this->session->getFlash('2fa_error'),
            'success' => $this->session->getFlash('2fa_success'),
            'base'    => $request->basePath(),
        ]);
    }

    public function disable(Request $request): Response
    {
        if (!$this->loginService->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }

        $userId = (int) $this->loginService->currentUserId();
        $user   = $this->qb->table('users')->where('id', '=', $userId)->first();
        $secret = (string) ($user['totp_secret'] ?? '');
        $code   = trim((string) $request->post('code', ''));

        if (!$secret || !$this->totp->verify($secret, $code)) {
            $this->session->flash('2fa_error', 'Invalid code. Please try again.');
            return Response::redirect($request->basePath() . '/manage/2fa');
        }

        $this->qb->table('users')->where('id', '=', $userId)->update([
            'totp_secret'  => null,
            'totp_enabled' => 0,
        ]);

        $this->session->flash('2fa_success', '2FA has been disabled.');
        return Response::redirect($request->basePath() . '/manage/2fa');
    }

    // ── Verify: shown during login when 2FA is pending ────────────────────────

    // ── QR code PNG (server-side, no CDN needed) ──────────────────────────────

    public function qrImage(Request $request): Response
    {
        if (!$this->loginService->isLoggedIn()) {
            return Response::error('Unauthorized', 401);
        }

        $data = trim((string) ($request->query('d') ?? ''));
        if ($data === '' || !str_starts_with($data, 'otpauth://')) {
            return Response::error('Invalid request', 400);
        }

        if (!function_exists('imagecreatetruecolor')) {
            return Response::error('GD extension not available', 500);
        }

        $png = QrRenderer::png($data, 5);

        return Response::html($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function verifyForm(Request $request): Response
    {
        $this->session->start();
        if (empty($_SESSION[self::PENDING_KEY])) {
            return Response::redirect($request->basePath() . '/login');
        }

        return $this->renderStandalone('verify', [
            'error'    => $this->session->getFlash('2fa_error'),
            'siteName' => $this->siteName,
            'base'     => $request->basePath(),
        ]);
    }

    public function verifySubmit(Request $request): Response
    {
        $this->session->start();
        $pendingUid = (int) ($_SESSION[self::PENDING_KEY] ?? 0);

        if (!$pendingUid) {
            return Response::redirect($request->basePath() . '/login');
        }

        $user   = $this->qb->table('users')->where('id', '=', $pendingUid)->first();
        $secret = (string) ($user['totp_secret'] ?? '');
        $code   = trim((string) $request->post('code', ''));

        if (!$secret || !$this->totp->verify($secret, $code)) {
            $this->session->flash('2fa_error', 'Invalid code. Please try again.');
            return Response::redirect($request->basePath() . '/2fa/verify');
        }

        $redirect = (string) ($_SESSION[self::REDIRECT_KEY] ?? $request->basePath() . '/manage');
        unset($_SESSION[self::PENDING_KEY], $_SESSION[self::REDIRECT_KEY]);

        $this->session->setUserId($pendingUid);

        return Response::redirect($redirect);
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    /**
     * Render a standalone full-page view (login-flow, no admin shell).
     * @param array<string, mixed> $data
     */
    private function renderStandalone(string $view, array $data): Response
    {
        $helpersFile = dirname(__DIR__, 3) . '/themes/default/views/helpers.php';
        if (is_file($helpersFile)) {
            require_once $helpersFile;
        }

        $siteName = $this->siteName;
        extract($data, EXTR_SKIP);

        ob_start();
        include __DIR__ . '/../views/' . $view . '.php';
        return Response::html((string) ob_get_clean());
    }

    /**
     * Render an admin-panel page using the theme's manage/layout.php shell.
     * @param array<string, mixed> $data
     */
    private function renderPage(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';

        require_once $themeDir . '/helpers.php';

        $base     = $data['base'] ?? '';
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        // Fetch current user for the topbar
        $userId = $this->loginService->currentUserId();
        $user   = $userId ? $this->qb->table('users')->where('id', '=', $userId)->first() : null;

        // Defaults for layout variables not managed by this controller
        $notifList       = [];
        $notifUnread     = 0;
        $panelLangs      = [];
        $currentLangCode = 'en';

        extract($data, EXTR_SKIP);

        // ── Render inner view → $content ────────────────────────────────────
        ob_start();
        try {
            include __DIR__ . '/../views/' . $view . '.php';
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        // ── Wrap in manage layout ────────────────────────────────────────────
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
