<?php
declare(strict_types=1);

namespace GsAds;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GsAdsAdminController
{
    public function __construct(
        private readonly GsAdsService $svc,
        private readonly QueryBuilder $qb,
        private readonly LoginService $auth,
        private readonly HookManager  $hooks,
        private readonly string       $siteName = 'GoniCore',
    ) {}

    // ── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('dashboard', [
            'base'      => $r->basePath(),
            'stats'     => $this->svc->stats(),
            'zoneStats' => $this->svc->zoneStats(),
        ]);
    }

    // ── Zones ──────────────────────────────────────────────────────────────────

    public function zones(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('zones', [
            'base'  => $r->basePath(),
            'zones' => $this->svc->zoneStats(),
            'saved' => $r->query('saved') === '1',
        ]);
    }

    public function zoneForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = $r->query('id') ? (int) $r->query('id') : null;
        $zone = $id ? $this->svc->zone($id) : null;
        return $this->renderPage('zone_form', [
            'base'   => $r->basePath(),
            'zone'   => $zone,
            'isEdit' => $id !== null,
        ]);
    }

    public function zoneSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $r->post('id') ? (int) $r->post('id') : null;

        $name   = trim((string) $r->post('name',   ''));
        $slug   = $this->slugify(trim((string) $r->post('slug', '')));
        if ($slug === '') $slug = $this->slugify($name);

        $w = (string) $r->post('width',  '');
        $h = (string) $r->post('height', '');

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => trim((string) $r->post('description', '')),
            'width'       => is_numeric($w) && (int)$w > 0 ? (int)$w : null,
            'height'      => is_numeric($h) && (int)$h > 0 ? (int)$h : null,
            'active'      => (string) $r->post('active', '0') === '1' ? 1 : 0,
        ];

        $this->svc->saveZone($data, $id);
        return Response::redirect($r->basePath() . '/manage/gsads/zones?saved=1');
    }

    public function zoneDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->deleteZone($id);
        return Response::redirect($r->basePath() . '/manage/gsads/zones');
    }

    public function zoneToggle(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = (int) $r->post('id', '0');
        $val  = (int) $r->post('active', '0');
        if ($id > 0) $this->svc->saveZone(['active' => $val ? 0 : 1], $id);
        return Response::redirect($r->basePath() . '/manage/gsads/zones');
    }

    // ── Ads ────────────────────────────────────────────────────────────────────

    public function ads(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $zoneId = $r->query('zone_id') ? (int) $r->query('zone_id') : null;
        $page   = max(1, (int) ($r->query('page') ?? 1));
        $result = $this->svc->ads($zoneId, $page);
        // Build zone map for display (zone_id → zone name)
        $zoneMap = [];
        foreach ($this->svc->zones() as $z) {
            $zoneMap[(int)$z['id']] = (string)$z['name'];
        }
        return $this->renderPage('ads', [
            'base'    => $r->basePath(),
            'items'   => $result['items'],
            'total'   => $result['total'],
            'pages'   => $result['pages'],
            'page'    => $page,
            'zones'   => $this->svc->zones(),
            'zoneMap' => $zoneMap,
            'zoneId'  => $zoneId,
            'saved'   => $r->query('saved') === '1',
        ]);
    }

    public function adForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id      = $r->query('id')      ? (int) $r->query('id')      : null;
        $zoneId  = $r->query('zone_id') ? (int) $r->query('zone_id') : null;
        $ad      = $id ? $this->svc->ad($id) : null;
        return $this->renderPage('ad_form', [
            'base'      => $r->basePath(),
            'ad'        => $ad,
            'isEdit'    => $id !== null,
            'zones'     => $this->svc->zones(),
            'defZoneId' => $zoneId,
        ]);
    }

    public function adSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $r->post('id') ? (int) $r->post('id') : null;

        $type = in_array($r->post('type'), ['image', 'html', 'text'], true)
            ? (string) $r->post('type') : 'image';

        $startsAt = trim((string) $r->post('starts_at', ''));
        $endsAt   = trim((string) $r->post('ends_at',   ''));

        $data = [
            'zone_id'     => (int) $r->post('zone_id', '0'),
            'name'        => trim((string) $r->post('name',      '')),
            'type'        => $type,
            'image_url'   => trim((string) $r->post('image_url', '')),
            'link_url'    => trim((string) $r->post('link_url',  '')),
            'html_code'   => (string) $r->post('html_code', ''),
            'ad_title'    => trim((string) $r->post('ad_title',  '')),
            'ad_body'     => trim((string) $r->post('ad_body',   '')),
            'opens_blank' => (string) $r->post('opens_blank', '0') === '1' ? 1 : 0,
            'weight'      => max(1, min(255, (int) $r->post('weight', '10'))),
            'starts_at'   => $startsAt !== '' ? $startsAt : null,
            'ends_at'     => $endsAt   !== '' ? $endsAt   : null,
            'active'      => (string) $r->post('active', '0') === '1' ? 1 : 0,
        ];

        $this->svc->saveAd($data, $id);
        $back = $r->basePath() . '/manage/gsads/ads?saved=1';
        if ($data['zone_id']) $back .= '&zone_id=' . $data['zone_id'];
        return Response::redirect($back);
    }

    public function adDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->deleteAd($id);
        return Response::redirect($r->basePath() . '/manage/gsads/ads');
    }

    public function adToggle(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id  = (int) $r->post('id',     '0');
        $val = (int) $r->post('active', '0');
        $zid = (int) $r->post('zone_id','0');
        if ($id > 0) $this->svc->saveAd(['active' => $val ? 0 : 1], $id);
        $back = $r->basePath() . '/manage/gsads/ads';
        if ($zid) $back .= '?zone_id=' . $zid;
        return Response::redirect($back);
    }

    // ── Public: click-tracking redirect ───────────────────────────────────────
    // NO auth guard — this is a public endpoint

    public function click(Request $r): Response
    {
        $id = (int) ($r->query('id') ?? 0);
        if ($id > 0) {
            $ad = $this->svc->ad($id);
            if ($ad && $ad['active']) {
                $this->svc->recordClick($id);
                $url = trim((string) $ad['link_url']);
                if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                    return Response::redirect($url, 302);
                }
            }
        }
        // Fallback — redirect to site root
        return Response::redirect($r->basePath() . '/', 302);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function slugify(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
        return trim($s, '-');
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
