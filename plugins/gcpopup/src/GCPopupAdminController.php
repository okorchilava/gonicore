<?php
declare(strict_types=1);

namespace GCPopup;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GCPopupAdminController
{
    public function __construct(
        private readonly GCPopupService $svc,
        private readonly QueryBuilder   $qb,
        private readonly LoginService   $auth,
        private readonly HookManager    $hooks,
        private readonly string         $siteName = 'GoniCore',
    ) {}

    // ── Popup list ─────────────────────────────────────────────────────────────

    public function popups(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        return $this->renderPage('popups', [
            'base'    => $r->basePath(),
            'popups'  => $this->svc->allPopups(),
            'saved'   => ($r->query('saved')    ?? '') === '1',
            'deleted' => ($r->query('deleted')  ?? '') === '1',
        ]);
    }

    // ── Add / Edit form ────────────────────────────────────────────────────────

    public function form(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id    = ($r->query('id') ?? '') !== '' ? (int)$r->query('id') : null;
        $popup = $id ? $this->svc->popup($id) : null;
        $items = $id ? $this->svc->itemsByPopup($id) : [];

        return $this->renderPage('popup_form', [
            'base'   => $r->basePath(),
            'popup'  => $popup,
            'items'  => $items,
            'isEdit' => $popup !== null,
            'error'  => $r->query('error') ?? '',
        ]);
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id = ($r->post('id') ?? '') !== '' ? (int)$r->post('id') : null;

        $name = trim((string)($r->post('name') ?? ''));
        if (!$name) {
            return Response::redirect($r->basePath() . '/manage/gcpopup/form' . ($id ? '?id='.$id.'&' : '?') . 'error=name');
        }

        $data = [
            'name'            => $name,
            'title'           => trim((string)($r->post('title')             ?? '')),
            'subtitle'        => trim((string)($r->post('subtitle')          ?? '')),
            'image_url'       => trim((string)($r->post('image_url')         ?? '')),
            'image_alt'       => trim((string)($r->post('image_alt')         ?? '')),
            'image_bg_color'  => $this->safeColor($r->post('image_bg_color'), '#e0fdf4'),
            'badge_text'      => trim((string)($r->post('badge_text')        ?? '')),
            'badge_color'     => $this->safeColor($r->post('badge_color'),     '#d1fae5'),
            'badge_text_color'=> $this->safeColor($r->post('badge_text_color'),'#065f46'),
            'btn_text'        => trim((string)($r->post('btn_text')          ?? '')),
            'btn_url'         => trim((string)($r->post('btn_url')           ?? '')),
            'btn_color'       => $this->safeColor($r->post('btn_color'),       '#2563eb'),
            'btn_text_color'  => $this->safeColor($r->post('btn_text_color'),  '#ffffff'),
            'footer_text'     => trim((string)($r->post('footer_text')       ?? '')),
            'footer_link_text'=> trim((string)($r->post('footer_link_text')  ?? '')),
            'footer_link_url' => trim((string)($r->post('footer_link_url')   ?? '')),
            'trigger_type'    => in_array($r->post('trigger_type'), ['load','scroll','exit','manual'], true)
                                  ? $r->post('trigger_type') : 'load',
            'trigger_delay'   => max(0, (int)($r->post('trigger_delay')   ?? 3)),
            'trigger_scroll'  => max(1, min(99, (int)($r->post('trigger_scroll') ?? 50))),
            'show_frequency'  => in_array($r->post('show_frequency'), ['always','once_session','once_day','once_ever'], true)
                                  ? $r->post('show_frequency') : 'once_session',
            'target_pages'    => trim((string)($r->post('target_pages')      ?? '')),
            'popup_width'     => max(300, min(700, (int)($r->post('popup_width') ?? 420))),
            'overlay_opacity' => max(0, min(90, (int)($r->post('overlay_opacity') ?? 60))),
            'animation'       => in_array($r->post('animation'), ['slide','fade','zoom'], true)
                                  ? $r->post('animation') : 'slide',
            'close_on_overlay'=> $r->post('close_on_overlay') ? 1 : 0,
            'show_close_btn'  => $r->post('show_close_btn') ? 1 : 0,
            'active'          => $r->post('active') ? 1 : 0,
            'sort_order'      => max(0, (int)($r->post('sort_order') ?? 0)),
        ];

        $newId = $this->svc->savePopup($data, $id);

        // Process items
        $rawItems = (array)($r->post('items') ?? []);
        $items = [];
        foreach ($rawItems as $row) {
            if (!is_array($row)) continue;
            $items[] = [
                'icon' => trim((string)($row['icon'] ?? '')),
                'text' => trim((string)($row['text'] ?? '')),
            ];
        }
        $this->svc->replaceItems($newId, $items);

        return Response::redirect($r->basePath() . '/manage/gcpopup?saved=1');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id = (int)($r->post('id') ?? 0);
        if ($id > 0) $this->svc->deletePopup($id);

        return Response::redirect($r->basePath() . '/manage/gcpopup?deleted=1');
    }

    // ── Toggle active ─────────────────────────────────────────────────────────

    public function toggle(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id = (int)($r->post('id') ?? 0);
        if ($id > 0) $this->svc->togglePopup($id);

        return Response::redirect($r->basePath() . '/manage/gcpopup');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function safeColor(?string $val, string $default): string
    {
        $val = trim((string)$val);
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) return $val;
        if (preg_match('/^rgba?\(/', $val)) return $val;
        return $default;
    }

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        return null;
    }

    /** @param array<string,mixed> $data */
    private function renderPage(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $base     = $data['base'] ?? '';
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        $notifList       = [];
        $notifUnread     = 0;
        $panelLangs      = [];
        $currentLangCode = 'en';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/admin/' . $view . '.php';
            $content = (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $themeDir . '/manage/layout.php';
            $html = (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
