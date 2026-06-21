<?php

declare(strict_types=1);

namespace GoniCore\Modules\Language;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;
use GoniCore\Modules\Post\PostRepository;
use GoniCore\Modules\User\UserRepository;

final class LanguageController
{
    private readonly string $viewsDir;

    public function __construct(
        private readonly LanguageService     $langService,
        private readonly LanguageRepository  $langRepo,
        private readonly PostRepository      $posts,
        private readonly LoginService        $auth,
        private readonly NotificationService $notifications,
        private readonly UserRepository      $users,
        private readonly SessionManager      $sessionMgr,
        private readonly string              $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__, 3) . '/themes/default/views/manage';
    }

    private function flash(string $msg, string $icon = 'success'): void
    {
        $this->sessionMgr->flash('gc_msg',  $msg);
        $this->sessionMgr->flash('gc_icon', $icon);
    }

    /** Auth + CSRF guard for manage-panel actions. */
    private function guard(Request $request): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }
        if ($request->method() === 'POST'
            && !$this->sessionMgr->verifyCsrf((string) $request->post('_csrf', ''))) {
            $this->flash('Security token expired — please try again.', 'error');
            return Response::redirect($request->basePath() . '/manage/languages');
        }
        return null;
    }

    /** Language codes are file names — restrict to safe characters. */
    private function isValidCode(string $code): bool
    {
        return (bool) preg_match('/^[a-z]{2}([_-][a-z0-9]{2,4})?$/i', $code);
    }

    // ── Public: switch language ───────────────────────────────────────────────

    public function switchLang(Request $request): Response
    {
        $code     = (string) $request->getAttribute('code');
        $referer  = (string) $request->server('HTTP_REFERER', '');
        $basePath = $request->basePath();
        $this->langService->switchTo($code, $basePath);
        // Redirect back, but strip any previous ?lang= query param to avoid loops
        $target = $referer ?: $basePath . '/';
        $target = preg_replace('/([?&])lang=[^&]*/i', '', $target) ?? $target;
        $target = rtrim($target, '?&');
        return Response::redirect($target ?: $basePath . '/');
    }

    // ── Manage: languages list ────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $languages = $this->langRepo->all();
        return $this->render('languages', compact('languages'), $request);
    }

    public function store(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $code   = strtolower(trim((string) $request->post('code', '')));
        $name   = trim((string) $request->post('name', ''));
        $native = trim((string) $request->post('native', ''));
        $flag   = trim((string) $request->post('flag', '🌐'));

        if (!$this->isValidCode($code)) {
            $this->flash('Invalid language code. Use letters like "en" or "en-us".', 'error');
        } elseif ($code && $name && $native) {
            $this->langRepo->create($code, $name, $native, $flag);
            $this->flash('Language added.');
        } else {
            $this->flash('Code, name and native name are required.', 'error');
        }
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function editForm(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if (!$lang) return Response::redirect($request->basePath() . '/manage/languages');
        return $this->render('language_edit', compact('lang'), $request);
    }

    public function editSave(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $code = (string) $request->getAttribute('code');

        $this->langRepo->update($code, [
            'name'   => trim((string) $request->post('name', '')),
            'native' => trim((string) $request->post('native', '')),
            'flag'   => trim((string) $request->post('flag', '🌐')),
        ]);
        $this->flash('Language updated.');
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function setDefault(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->langRepo->setDefault((string) $request->getAttribute('code'));
        $this->flash('Default language changed.');
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    public function toggle(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->langRepo->toggleActive((string) $request->getAttribute('code'));
        $this->flash('Language status changed.');
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    public function delete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if ($lang && !$lang['is_default']) {
            $this->langRepo->delete($code);
            $this->flash('Language deleted.');
        } else {
            $this->flash('Default language cannot be deleted.', 'error');
        }
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    // ── Post translations ─────────────────────────────────────────────────────

    public function translateForm(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $postId = (int) $request->getAttribute('id');
        $code   = (string) $request->getAttribute('code');
        $post   = $this->posts->findById($postId);
        $lang   = $this->langRepo->findByCode($code);
        if (!$post || !$lang) return Response::redirect($request->basePath() . '/manage/posts');
        $translation = $this->langRepo->getTranslation($postId, $code);
        return $this->render('post_translate', compact('post', 'lang', 'translation'), $request);
    }

    public function translateSave(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $postId = (int) $request->getAttribute('id');
        $code   = (string) $request->getAttribute('code');
        $title  = trim((string) $request->post('title', ''));
        $slug   = trim((string) $request->post('slug', '')) ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? $title);
        $this->langRepo->saveTranslation($postId, $code, [
            'title'   => $title,
            'slug'    => $slug,
            'content' => trim((string) $request->post('content', '')),
            'status'  => (string) $request->post('status', 'draft'),
        ]);
        $this->flash('Translation saved.');
        return Response::redirect($request->basePath() . '/manage/posts/' . $postId);
    }

    // ── Lang file translation ─────────────────────────────────────────────────

    /**
     * Show the lang-file editor: all keys from en.php with current translations.
     */
    public function fileForm(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if (!$lang || !$this->isValidCode($code)) {
            return Response::redirect($request->basePath() . '/manage/languages');
        }

        $langDir   = dirname(__DIR__, 3) . '/lang';
        $enFile    = $langDir . '/en.php';
        $codeFile  = $langDir . '/' . $code . '.php';

        $enKeys       = is_file($enFile)   ? (require $enFile)   : [];
        $translations = is_file($codeFile) ? (require $codeFile) : [];

        if (!is_array($enKeys))       $enKeys       = [];
        if (!is_array($translations)) $translations = [];

        return $this->render(
            'language_file',
            compact('lang', 'code', 'enKeys', 'translations'),
            $request
        );
    }

    /**
     * Save submitted translations to lang/{code}.php.
     */
    public function fileSave(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if (!$lang || !$this->isValidCode($code)) {
            return Response::redirect($request->basePath() . '/manage/languages');
        }

        $langDir  = dirname(__DIR__, 3) . '/lang';
        $enFile   = $langDir . '/en.php';
        $enKeys   = is_file($enFile) ? (require $enFile) : [];
        if (!is_array($enKeys)) $enKeys = [];

        // Collect submitted values — only keys that exist in en.php
        $translations = [];
        $rawValues    = (array) ($request->post('translations', []) ?: []);
        foreach (array_keys($enKeys) as $key) {
            $translations[$key] = isset($rawValues[$key])
                ? (string) $rawValues[$key]
                : '';
        }

        // Render PHP array string
        $lines   = ["<?php", "", "declare(strict_types=1);", "", "return ["];
        $lastCat = null;
        foreach ($translations as $key => $value) {
            // Detect category comments (first segment before the dot)
            $cat = explode('.', $key)[0];
            if ($cat !== $lastCat) {
                $lines[] = "    // " . ucfirst($cat);
                $lastCat = $cat;
            }
            $escapedKey   = str_replace(['\\', "'"], ['\\\\', "\\'"], $key);
            $escapedValue = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
            $lines[] = sprintf("    %-28s => '%s',", "'{$escapedKey}'", $escapedValue);
        }
        $lines[] = "];";
        $php = implode("\n", $lines) . "\n";

        $targetFile = $langDir . '/' . $code . '.php';
        $written    = file_put_contents($targetFile, $php);

        if ($written === false) {
            $this->flash('Failed to write ' . $code . '.php — check directory permissions.', 'error');
        } else {
            $this->flash('Translations saved successfully.');
        }
        return Response::redirect($request->basePath() . '/manage/languages/' . $code . '/file');
    }

    // ── Renderer ──────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $data */
    private function render(string $template, array $data, Request $request): Response
    {
        require_once dirname(__DIR__, 3) . '/themes/default/views/helpers.php';

        // Load translations for the panel language so t() works in views.
        $this->langService->boot($request);
        $GLOBALS['langService'] = $this->langService;

        $viewFile = $this->viewsDir . '/' . $template . '.php';
        if (!is_file($viewFile)) return Response::error("View not found: {$template}", 500);

        $base     = $request->basePath();
        $siteName = $this->siteName;

        // Resolve current user and notifications (same as ManageController)
        $userId      = $this->auth->currentUserId();
        $user        = $userId ? $this->users->findById($userId) : null;
        $notifList   = $user ? $this->notifications->forUser((int) $user['id']) : [];
        $notifUnread = $user ? $this->notifications->unreadCount((int) $user['id']) : 0;

        // Active languages for panel switcher
        $panelLangs  = $this->langRepo->allActive();
        $currentLangCode = $this->langService->currentCode();

        // One-shot flash message → SweetAlert2 toast in layout
        $flashMsg  = $this->sessionMgr->getFlash('gc_msg');
        $flashIcon = $this->sessionMgr->getFlash('gc_icon') ?? 'success';

        // CSRF token — layout injects it into every POST form
        $csrfToken = $this->sessionMgr->csrfToken();

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        $content = (string) ob_get_clean();

        ob_start();
        include $this->viewsDir . '/layout.php';
        return Response::html((string) ob_get_clean());
    }
}
