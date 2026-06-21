<?php

declare(strict_types=1);

namespace GoniCore\Modules\Login;

use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Mail\MailService;
use GoniCore\Modules\Category\CategoryRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\User\UserRepository;

/**
 * Handles the web login / logout flow.
 *
 * Routes:
 *   GET  /login   → showLogin()
 *   POST /login   → processLogin()
 *   GET  /logout  → logout()
 */
final class LoginController
{
    private readonly string $viewsDir;

    public function __construct(
        private readonly LoginService       $loginService,
        private readonly SessionManager     $session,
        private readonly CategoryRepository $categories,
        private readonly HookManager        $hooks,
        private readonly UserRepository     $users,
        private readonly MailService        $mailer,
        private readonly LanguageService    $langService,
        private readonly string             $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__, 3) . '/themes/default/views';
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function showLogin(Request $request): Response
    {
        if ($this->loginService->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/');
        }

        $error    = $this->session->getFlash('login_error');
        $oldInput = $this->session->getFlash('login_old') ?? '';
        $csrf     = $this->session->csrfToken();

        return $this->render($request, compact('error', 'oldInput', 'csrf'));
    }

    public function processLogin(Request $request): Response
    {
        $identifier = trim((string) $request->post('identifier', ''));
        $password   = (string) $request->post('password', '');
        $remember   = (bool)   $request->post('remember_me', false);

        if (!$this->session->verifyCsrf((string) $request->post('_csrf', ''))) {
            $this->session->flash('login_error', 'Session expired — please try again.');
            $this->session->flash('login_old',   $identifier);
            return Response::redirect($request->basePath() . '/login');
        }

        if ($identifier === '' || $password === '') {
            $this->session->flash('login_error', 'Please fill in all fields.');
            $this->session->flash('login_old',   $identifier);
            return Response::redirect($request->basePath() . '/login');
        }

        $result = $this->loginService->attempt($identifier, $password, $remember);

        if (!$result['success']) {
            $this->session->flash('login_error', $result['error'] ?? 'Login failed.');
            $this->session->flash('login_old',   $identifier);
            return Response::redirect($request->basePath() . '/login');
        }

        $userId   = (int) ($result['user_id'] ?? $this->session->getUserId());

        // Only allow same-site relative redirects — blocks open-redirect abuse.
        $redirect = (string) $request->post('redirect', '');
        if ($redirect === '' || $redirect[0] !== '/' || str_starts_with($redirect, '//') || str_contains($redirect, '\\')) {
            $redirect = $request->basePath() . '/manage';
        }

        // Send security notification email to the user
        $this->sendLoginNotification($userId);

        // Allow plugins to hook into a successful login (e.g. 2FA intercept)
        $this->hooks->emit('login.success', $userId, $this->session, $request);

        // Allow plugins to change the post-login redirect (e.g. 2FA verify page)
        $redirect = (string) $this->hooks->apply('login.redirect', $redirect, $userId, $request->basePath());

        return Response::redirect($redirect);
    }

    public function logout(Request $request): Response
    {
        $this->loginService->logout();
        return Response::redirect($request->basePath() . '/login');
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /**
     * Send a sign-in notification email to the user.
     * Silently skipped if the user has no email or mail is not configured.
     */
    private function sendLoginNotification(int $userId): void
    {
        try {
            $user  = $this->users->findById($userId);
            $email = (string) ($user['email'] ?? '');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            // Respect user's notification preference (default on if column missing)
            if (isset($user['email_notifications']) && !(int) $user['email_notifications']) {
                return;
            }

            $name = htmlspecialchars(
                (string) ($user['name'] ?? $user['username'] ?? 'User'),
                ENT_QUOTES
            );
            $ip   = htmlspecialchars(
                (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
                ENT_QUOTES
            );
            $ua   = htmlspecialchars(
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 0, 120),
                ENT_QUOTES
            );
            $time = date('Y-m-d H:i:s');

            $body = "
                <p>Hello <strong>{$name}</strong>,</p>
                <p>A new sign-in to your <strong>admin panel</strong> was detected.</p>
                <table style=\"width:100%;border-collapse:collapse;margin:20px 0;font-size:14px\">
                  <tr style=\"border-bottom:1px solid #e2e8f0\">
                    <td style=\"padding:10px 0;color:#64748b;width:120px\">Time</td>
                    <td style=\"padding:10px 0;font-weight:600\">{$time}</td>
                  </tr>
                  <tr style=\"border-bottom:1px solid #e2e8f0\">
                    <td style=\"padding:10px 0;color:#64748b\">IP address</td>
                    <td style=\"padding:10px 0;font-weight:600\">{$ip}</td>
                  </tr>
                  <tr>
                    <td style=\"padding:10px 0;color:#64748b\">Device</td>
                    <td style=\"padding:10px 0;color:#475569;font-size:13px\">{$ua}</td>
                  </tr>
                </table>
                <p style=\"font-size:13.5px;color:#64748b\">
                  If this wasn&#39;t you, change your password immediately.
                </p>";

            $html = $this->mailer->template('New sign-in to your account', $body);
            $this->mailer->send($email, 'New sign-in to your account', $html);

        } catch (\Throwable) {
            // Mail errors must never break the login flow
        }
    }

    // ── View renderer ─────────────────────────────────────────────────────────

    /** @param array<string, mixed> $data */
    private function render(Request $request, array $data = []): Response
    {
        $viewFile    = $this->viewsDir . '/login.php';
        $helpersFile = $this->viewsDir . '/helpers.php';

        if (!is_file($viewFile)) {
            return Response::error('Login view not found.', 500);
        }

        require_once $helpersFile;

        // Load translations so t() works on the login page.
        $this->langService->boot($request);
        $GLOBALS['langService'] = $this->langService;

        $base       = $request->basePath();
        $siteName   = $this->siteName;
        $hooks      = $this->hooks;
        $categories = $this->categories->findAll();

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $viewFile;
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
