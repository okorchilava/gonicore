<?php

declare(strict_types=1);

namespace GoniCore\Modules\Language;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;
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
        private readonly string              $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__, 3) . '/themes/default/views/manage';
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
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
        $languages = $this->langRepo->all();
        return $this->render('languages', compact('languages'), $request);
    }

    public function store(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');

        $code   = strtolower(trim((string) $request->post('code', '')));
        $name   = trim((string) $request->post('name', ''));
        $native = trim((string) $request->post('native', ''));
        $flag   = trim((string) $request->post('flag', '🌐'));

        if ($code && $name && $native) {
            $this->langRepo->create($code, $name, $native, $flag);
        }
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function editForm(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if (!$lang) return Response::redirect($request->basePath() . '/manage/languages');
        return $this->render('language_edit', compact('lang'), $request);
    }

    public function editSave(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
        $code = (string) $request->getAttribute('code');

        $this->langRepo->update($code, [
            'name'   => trim((string) $request->post('name', '')),
            'native' => trim((string) $request->post('native', '')),
            'flag'   => trim((string) $request->post('flag', '🌐')),
        ]);
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function setDefault(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
        $this->langRepo->setDefault((string) $request->getAttribute('code'));
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    public function toggle(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
        $this->langRepo->toggleActive((string) $request->getAttribute('code'));
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    public function delete(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if ($lang && !$lang['is_default']) $this->langRepo->delete($code);
        return Response::redirect($request->basePath() . '/manage/languages');
    }

    // ── Post translations ─────────────────────────────────────────────────────

    public function translateForm(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
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
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');
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
        return Response::redirect($request->basePath() . '/manage/posts/' . $postId);
    }

    // ── Lang file translation ─────────────────────────────────────────────────

    /**
     * Show the lang-file editor: all keys from en.php with current translations.
     */
    public function fileForm(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');

        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if (!$lang) return Response::redirect($request->basePath() . '/manage/languages');

        $langDir   = dirname(__DIR__, 3) . '/lang';
        $enFile    = $langDir . '/en.php';
        $codeFile  = $langDir . '/' . $code . '.php';

        $enKeys       = is_file($enFile)   ? (require $enFile)   : [];
        $translations = is_file($codeFile) ? (require $codeFile) : [];

        if (!is_array($enKeys))       $enKeys       = [];
        if (!is_array($translations)) $translations = [];

        $success = $request->query('success');
        $error   = $request->query('error');

        return $this->render(
            'language_file',
            compact('lang', 'code', 'enKeys', 'translations', 'success', 'error'),
            $request
        );
    }

    /**
     * Save submitted translations to lang/{code}.php.
     */
    public function fileSave(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) return Response::redirect($request->basePath() . '/login');

        $code = (string) $request->getAttribute('code');
        $lang = $this->langRepo->findByCode($code);
        if (!$lang) return Response::redirect($request->basePath() . '/manage/languages');

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
            $escapedKey   = str_replace("'", "\\'", $key);
            $escapedValue = str_replace("'", "\\'", $value);
            $lines[] = sprintf("    %-28s => '%s',", "'{$escapedKey}'", $escapedValue);
        }
        $lines[] = "];";
        $php = implode("\n", $lines) . "\n";

        $targetFile = $langDir . '/' . $code . '.php';
        $written    = file_put_contents($targetFile, $php);

        if ($written === false) {
            $error = urlencode('Failed to write ' . $code . '.php — check directory permissions.');
            return Response::redirect($request->basePath() . '/manage/languages/' . $code . '/file?error=' . $error);
        }

        $success = urlencode('Translations saved successfully.');
        return Response::redirect($request->basePath() . '/manage/languages/' . $code . '/file?success=' . $success);
    }

    // ── Renderer ──────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $data */
    private function render(string $template, array $data, Request $request): Response
    {
        require_once dirname(__DIR__, 3) . '/themes/default/views/helpers.php';

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

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        $content = (string) ob_get_clean();

        ob_start();
        include $this->viewsDir . '/layout.php';
        return Response::html((string) ob_get_clean());
    }
}
