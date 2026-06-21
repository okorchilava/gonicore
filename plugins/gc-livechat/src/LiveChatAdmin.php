<?php

declare(strict_types=1);

namespace GCLiveChat;

use GCLiveChat\Ai\AiResponder;
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
 * Admin side of GC Live Chat: the operator inbox (live console) and the settings
 * page (AI provider/key/model, prompts, appearance).
 */
final class LiveChatAdmin
{
    public function __construct(
        private readonly LoginService        $auth,
        private readonly SessionManager      $session,
        private readonly ChatService         $chat,
        private readonly AiResponder         $responder,
        private readonly SettingsService     $settings,
        private readonly LanguageService     $langService,
        private readonly LanguageRepository  $langRepo,
        private readonly NotificationService $notifications,
        private readonly QueryBuilder        $qb,
        private readonly HookManager         $hooks,
        private readonly string              $siteName = 'GoniCore',
    ) {}

    private function flash(string $msg, string $icon = 'success'): void
    {
        $this->session->flash('gc_msg',  $msg);
        $this->session->flash('gc_icon', $icon);
    }

    private function guard(Request $request): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login?redirect=' . urlencode($request->path()));
        }
        if ($request->method() === 'POST'
            && !$this->session->verifyCsrf((string) $request->post('_csrf', ''))) {
            $this->flash('Your session has expired. Please sign in again.', 'warning');
            $this->session->flash('gc_action', 'logout');
            return Response::redirect($request->basePath() . '/manage/livechat');
        }
        return null;
    }

    // ── Operator inbox ──────────────────────────────────────────────────────────

    public function inbox(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $cid      = (int) $request->query('cid', '0');
        $active   = $cid > 0 ? $this->chat->find($cid) : null;
        $messages = [];
        if ($active !== null) {
            $messages = $this->chat->messagesAfter((int) $active['id'], 0);
            $this->chat->markSeenByOperator((int) $active['id']);
        }

        return $this->renderPage($request, 'admin/inbox', [
            'conversations' => $this->chat->inbox(),
            'active'        => $active,
            'messages'      => $messages,
        ]);
    }

    /** Live polling for the console: conversation list + new messages for a cid. */
    public function poll(Request $request): Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::json(['ok' => false], 403);
        }
        $cid   = (int) $request->query('cid', '0');
        $after = max(0, (int) $request->query('after', '0'));

        $messages = [];
        $status   = '';
        if ($cid > 0) {
            $conv = $this->chat->find($cid);
            if ($conv !== null) {
                $status   = (string) $conv['status'];
                $messages = $this->chat->messagesAfter($cid, $after);
                if ($messages !== []) {
                    $this->chat->markSeenByOperator($cid);
                }
            }
        }

        return Response::json([
            'ok'      => true,
            'waiting' => $this->chat->waitingCount(),
            'status'  => $status,
            'messages'=> array_map(static fn (array $m): array => [
                'id' => (int) $m['id'], 'sender' => (string) $m['sender'], 'body' => (string) $m['body'],
            ], $messages),
            'inbox'   => array_map(static fn (array $c): array => [
                'id'     => (int) $c['id'],
                'name'   => (string) ($c['visitor_name'] ?: ('#' . $c['id'])),
                'status' => (string) $c['status'],
                'last'   => mb_substr((string) ($c['last_body'] ?? ''), 0, 60),
                'unread' => (int) ($c['unread'] ?? 0),
            ], $this->chat->inbox()),
        ]);
    }

    public function reply(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $xhr  = strcasecmp((string) ($request->header('X-Requested-With') ?? ''), 'XMLHttpRequest') === 0;
        $cid  = (int) $request->post('cid', '0');
        $body = trim((string) $request->post('body', ''));
        $conv = $cid > 0 ? $this->chat->find($cid) : null;
        if ($conv === null || $body === '') {
            return $xhr ? Response::json(['ok' => false], 422)
                        : Response::redirect($request->basePath() . '/manage/livechat?cid=' . $cid);
        }
        $opId = (int) $this->auth->currentUserId();
        if ((string) $conv['status'] !== 'operator') {
            $this->chat->setStatus($cid, 'operator', $opId);
        }
        $this->chat->addMessage($cid, 'operator', mb_substr($body, 0, 4000), $opId);

        return $xhr ? Response::json(['ok' => true])
                    : Response::redirect($request->basePath() . '/manage/livechat?cid=' . $cid);
    }

    public function takeover(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $cid  = (int) $request->post('cid', '0');
        $conv = $cid > 0 ? $this->chat->find($cid) : null;
        if ($conv !== null) {
            $opId = (int) $this->auth->currentUserId();
            $this->chat->setStatus($cid, 'operator', $opId);
            $name = (string) ($this->currentUser()['name'] ?? 'Operator');
            $this->chat->addMessage($cid, 'system', ($this->t())('admin.op_joined', ['name' => $name]), $opId);
            $this->flash(($this->t())('admin.taken_over'));
        }
        return Response::redirect($request->basePath() . '/manage/livechat?cid=' . $cid);
    }

    public function close(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $cid = (int) $request->post('cid', '0');
        if ($cid > 0) {
            $this->chat->close($cid);
            $this->flash(($this->t())('admin.closed'));
        }
        return Response::redirect($request->basePath() . '/manage/livechat');
    }

    // ── Settings ────────────────────────────────────────────────────────────────

    public function settings(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        return $this->renderPage($request, 'admin/settings', [
            'values'      => $this->currentValues(),
            'providers'   => AiResponder::PROVIDERS,
            'defModels'   => AiResponder::DEFAULT_MODELS,
            'hasKey'      => trim((string) $this->settings->get('livechat_api_key', '')) !== '',
            'configured'  => $this->responder->isConfigured(),
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $this->settings->set('livechat_enabled', $request->post('livechat_enabled') !== null ? '1' : '0');

        $provider = (string) $request->post('livechat_provider', 'claude');
        $this->settings->set('livechat_provider', isset(AiResponder::PROVIDERS[$provider]) ? $provider : 'claude');

        // Only overwrite the API key when a new value is actually entered.
        $key = trim((string) $request->post('livechat_api_key', ''));
        if ($key !== '') {
            $this->settings->set('livechat_api_key', $key);
        }

        $this->settings->set('livechat_model', mb_substr(trim((string) $request->post('livechat_model', '')), 0, 100));
        $this->settings->set('livechat_title', mb_substr(trim((string) $request->post('livechat_title', '')), 0, 80));
        $this->settings->set('livechat_greeting', mb_substr(trim((string) $request->post('livechat_greeting', '')), 0, 500));
        $this->settings->set('livechat_system_prompt', mb_substr((string) $request->post('livechat_system_prompt', ''), 0, 4000));
        $this->settings->set('livechat_faq', mb_substr((string) $request->post('livechat_faq', ''), 0, 6000));
        $this->settings->set('livechat_use_site_content', $request->post('livechat_use_site_content') !== null ? '1' : '0');

        $color = (string) $request->post('livechat_color', '#4f46e5');
        $this->settings->set('livechat_color', preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : '#4f46e5');

        $this->flash(($this->t())('admin.saved'));
        return Response::redirect($request->basePath() . '/manage/livechat/settings');
    }

    /** @return array<string,mixed> */
    private function currentValues(): array
    {
        $g = fn (string $k, string $d = '') => (string) $this->settings->get($k, $d);
        return [
            'livechat_enabled'          => $g('livechat_enabled', '0') === '1',
            'livechat_provider'         => $g('livechat_provider', 'claude'),
            'livechat_model'            => $g('livechat_model', ''),
            'livechat_title'            => $g('livechat_title', ''),
            'livechat_greeting'         => $g('livechat_greeting', ''),
            'livechat_system_prompt'    => $g('livechat_system_prompt', ''),
            'livechat_faq'              => $g('livechat_faq', ''),
            'livechat_use_site_content' => $g('livechat_use_site_content', '1') === '1',
            'livechat_color'            => $g('livechat_color', '#4f46e5'),
        ];
    }

    // ── Internal ──────────────────────────────────────────────────────────────────

    private function t(): callable
    {
        return gc_plugin_translator(dirname(__DIR__));
    }

    /** @return array<string,mixed>|null */
    private function currentUser(): ?array
    {
        $id = $this->auth->currentUserId();
        return $id ? $this->qb->table('users')->where('id', '=', $id)->first() : null;
    }

    /** @param array<string,mixed> $data */
    private function renderPage(Request $request, string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $t = gc_plugin_translator(dirname(__DIR__));

        $base     = $request->basePath();
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $user            = $this->currentUser();
        $notifList       = $user ? $this->notifications->forUser((int) $user['id']) : [];
        $notifUnread     = $user ? $this->notifications->unreadCount((int) $user['id']) : 0;
        $panelLangs      = $this->langRepo->allActive();
        $currentLangCode = $this->langService->currentCode();

        $flashMsg    = $this->session->getFlash('gc_msg');
        $flashIcon   = $this->session->getFlash('gc_icon') ?? 'success';
        $flashAction = $this->session->getFlash('gc_action');
        $csrfToken   = $this->session->csrfToken();

        $activeNav = 'livechat';

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
