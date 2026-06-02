<?php

declare(strict_types=1);

namespace GoniCore\Modules\Login;

use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Category\CategoryRepository;

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
        private readonly string             $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__, 3) . '/themes/default/views';
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function showLogin(Request $request): Response
    {
        // Already logged in → redirect home
        if ($this->loginService->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/');
        }

        $error   = $this->session->getFlash('login_error');
        $oldInput = $this->session->getFlash('login_old') ?? '';

        return $this->render($request, compact('error', 'oldInput'));
    }

    public function processLogin(Request $request): Response
    {
        $identifier = trim((string) $request->post('identifier', ''));
        $password   = (string) $request->post('password', '');
        $remember   = (bool)   $request->post('remember_me', false);

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

        // Redirect to intended page or manage panel
        $redirect = (string) ($request->post('redirect', '') ?: $request->basePath() . '/manage');
        return Response::redirect($redirect);
    }

    public function logout(Request $request): Response
    {
        $this->loginService->logout();
        return Response::redirect($request->basePath() . '/login');
    }

    // ── View renderer ─────────────────────────────────────────────────────────

    /** @param array<string, mixed> $data */
    private function render(Request $request, array $data = []): Response
    {
        $viewFile   = $this->viewsDir . '/login.php';
        $helpersFile = $this->viewsDir . '/helpers.php';

        if (!is_file($viewFile)) {
            return Response::error('Login view not found.', 500);
        }

        require_once $helpersFile;

        $base       = $request->basePath();
        $siteName   = $this->siteName;
        $hooks      = $this->hooks;
        $categories = $this->categories->findAll();

        extract($data, EXTR_SKIP);

        // Render login page (standalone — uses its own layout, not the theme shell)
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
