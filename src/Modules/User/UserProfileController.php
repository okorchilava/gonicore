<?php

declare(strict_types=1);

namespace GoniCore\Modules\User;

use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;

/**
 * Front-end self-service user panel (separate from the admin /manage panel).
 *
 * Routes (web, see bootstrap):
 *   GET  /users/profile                       → profile()
 *   POST /users/profile                       → updateProfile()
 *   POST /users/profile/password              → updatePassword()
 *   GET  /users/notifications                 → notifications()
 *   POST /users/notifications/{id}/read       → notificationRead()
 *   POST /users/notifications/read-all        → notificationReadAll()
 *
 * The panel is intentionally extensible: every page calls render() with the
 * current section key, and the layout builds its sidebar from a single
 * $navItems array plus a `user.panel.nav` hook so plugins can add sections
 * without touching core.
 */
final class UserProfileController
{
    private readonly string $viewsDir;

    public function __construct(
        private readonly LoginService        $auth,
        private readonly UserRepository       $users,
        private readonly SessionManager       $session,
        private readonly NotificationService  $notifications,
        private readonly LanguageService      $langService,
        private readonly HookManager          $hooks,
        private readonly string               $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__, 3) . '/themes/default/views/user';
    }

    // ── Guard ───────────────────────────────────────────────────────────────

    private function guard(Request $request): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect(
                $request->basePath() . '/login?redirect=' . urlencode($request->path())
            );
        }
        if ($request->method() === 'POST'
            && !$this->session->verifyCsrf((string) $request->post('_csrf', ''))) {
            $this->flash('Security token expired — please try again.', 'error');
            return Response::redirect($request->basePath() . '/users/profile');
        }
        return null;
    }

    private function currentUser(): ?array
    {
        $id = $this->auth->currentUserId();
        return $id ? $this->users->findById($id) : null;
    }

    private function flash(string $msg, string $icon = 'success'): void
    {
        $this->session->flash('gc_msg',  $msg);
        $this->session->flash('gc_icon', $icon);
    }

    // ── Profile ─────────────────────────────────────────────────────────────

    public function profile(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect($request->basePath() . '/login');
        }
        return $this->render('profile', 'profile', ['user' => $user], $request);
    }

    public function updateProfile(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect($request->basePath() . '/login');
        }

        $name  = trim((string) $request->post('name', ''));
        $email = trim((string) $request->post('email', ''));

        if ($name === '' || $email === '') {
            $this->flash('Name and email are required.', 'error');
            return Response::redirect($request->basePath() . '/users/profile');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('Please enter a valid email address.', 'error');
            return Response::redirect($request->basePath() . '/users/profile');
        }

        // Email must stay unique across other accounts.
        $existing = $this->users->findByEmail($email);
        if ($existing !== null && (int) $existing['id'] !== (int) $user['id']) {
            $this->flash('That email is already in use.', 'error');
            return Response::redirect($request->basePath() . '/users/profile');
        }

        // save() never touches the password and ignores unknown columns.
        $this->users->save([
            'id'    => (int) $user['id'],
            'name'  => $name,
            'email' => $email,
        ]);

        $this->hooks->emit('user.profile.updated', (int) $user['id'], ['name' => $name, 'email' => $email]);
        $this->flash('Profile updated.');
        return Response::redirect($request->basePath() . '/users/profile');
    }

    public function updatePassword(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect($request->basePath() . '/login');
        }

        $current = (string) $request->post('current_password', '');
        $new     = (string) $request->post('new_password', '');
        $confirm = (string) $request->post('confirm_password', '');

        if (!password_verify($current, (string) ($user['password'] ?? ''))) {
            $this->flash('Your current password is incorrect.', 'error');
            return Response::redirect($request->basePath() . '/users/profile');
        }
        if (strlen($new) < 8) {
            $this->flash('New password must be at least 8 characters.', 'error');
            return Response::redirect($request->basePath() . '/users/profile');
        }
        if ($new !== $confirm) {
            $this->flash('New passwords do not match.', 'error');
            return Response::redirect($request->basePath() . '/users/profile');
        }

        $this->users->updatePassword((int) $user['id'], password_hash($new, PASSWORD_BCRYPT));
        $this->hooks->emit('user.password.changed', (int) $user['id']);
        $this->flash('Password changed.');
        return Response::redirect($request->basePath() . '/users/profile');
    }

    // ── Notifications ───────────────────────────────────────────────────────

    public function notifications(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect($request->basePath() . '/login');
        }
        $items = $this->notifications->forUser((int) $user['id'], 50);
        return $this->render('notifications', 'notifications', [
            'user'        => $user,
            'notifList'   => $items,
        ], $request);
    }

    public function notificationRead(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        if ($user !== null) {
            $this->notifications->markRead((int) $request->getAttribute('id'), (int) $user['id']);
        }
        return Response::redirect($request->basePath() . '/users/notifications');
    }

    public function notificationReadAll(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        if ($user !== null) {
            $this->notifications->markAllRead((int) $user['id']);
        }
        return Response::redirect($request->basePath() . '/users/notifications');
    }

    // ── Renderer ────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $data
     */
    private function render(string $template, string $active, array $data, Request $request): Response
    {
        require_once dirname(__DIR__, 3) . '/themes/default/views/helpers.php';

        $this->langService->boot($request);
        $GLOBALS['langService'] = $this->langService;

        $viewFile = $this->viewsDir . '/' . $template . '.php';
        if (!is_file($viewFile)) {
            return Response::error("User view not found: {$template}", 500);
        }

        $base      = $request->basePath();
        $siteName  = $this->siteName;
        $hooks     = $this->hooks;
        $user      = $data['user'] ?? $this->currentUser();
        $activeNav = $active;

        // Sidebar items — core sections; plugins may append via the hook in the layout.
        $navItems = [
            ['key' => 'profile',       'label' => 'Profile',       'icon' => '👤', 'href' => $base . '/users/profile'],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => '🔔', 'href' => $base . '/users/notifications'],
        ];

        $unread    = $user ? $this->notifications->unreadCount((int) $user['id']) : 0;
        $flashMsg  = $this->session->getFlash('gc_msg');
        $flashIcon = $this->session->getFlash('gc_icon') ?? 'success';
        $csrfToken = $this->session->csrfToken();

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $viewFile;
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $this->viewsDir . '/layout.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
