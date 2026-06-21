<?php
declare(strict_types=1);

namespace GCRating;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GCRatingAdminController
{
    public function __construct(
        private readonly GCRatingService $svc,
        private readonly QueryBuilder    $qb,
        private readonly LoginService    $auth,
        private readonly HookManager     $hooks,
        private readonly string          $siteName = 'GoniCore',
    ) {}

    // ── Public tracking API (no auth) ──────────────────────────────────────────

    /**
     * POST /gcrating/track
     * Called by the frontend JS on every page load.
     * Body: JSON { sid, vid, url, title, ref, ua, w, touch, utm_* }
     */
    public function apiTrack(Request $r): Response
    {
        $raw  = (string)file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true) ?? [];

        // Bot detection
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ((bool)(int)$this->svc->getSetting('exclude_bots', '1') && GCRatingService::isBot($ua)) {
            return Response::json(['ok' => false, 'reason' => 'bot']);
        }

        // IP exclusion list
        $excludeIps = array_filter(
            array_map('trim', explode("\n", $this->svc->getSetting('exclude_ips', '')))
        );
        if ($excludeIps) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            $clientIp = trim(explode(',', $clientIp)[0]);
            if (in_array($clientIp, $excludeIps, true)) {
                return Response::json(['ok' => false, 'reason' => 'excluded']);
            }
        }

        $data['ua'] = $ua;
        $result = $this->svc->trackPageview($data);

        if (empty($result)) {
            return Response::json(['ok' => false]);
        }

        return Response::json([
            'ok'    => true,
            'pv_id' => $result['pv_id'],
            'sid'   => $result['session_id'],
        ]);
    }

    /**
     * POST /gcrating/duration
     * Called by sendBeacon when the user leaves a page.
     * Body: JSON { pv_id, sid, seconds }
     */
    public function apiDuration(Request $r): Response
    {
        $raw  = (string)file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true) ?? [];

        $this->svc->updateDuration(
            pvId:      (int)($data['pv_id']  ?? 0),
            sessionId: (int)($data['sid']    ?? 0),
            seconds:   (int)($data['seconds'] ?? 0),
        );

        return Response::json(['ok' => true]);
    }

    // ── Admin pages ────────────────────────────────────────────────────────────

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $period = $this->safePeriod($r->query('period') ?? '30d');

        return $this->renderPage('dashboard', [
            'base'     => $r->basePath(),
            'period'   => $period,
            'overview' => $this->svc->overview($period),
            'today'    => $this->svc->todayStats(),
            'daily'    => $this->svc->dailyStats(30),
            'topPages' => $this->svc->topPages(5, $period),
            'sources'  => $this->svc->sourceStats($period),
            'devices'  => $this->svc->deviceStats($period),
            'browsers' => $this->svc->browserStats($period),
            'topRefs'  => $this->svc->topReferrers(5, $period),
            'totals'   => $this->svc->totalRows(),
        ]);
    }

    public function pages(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $period = $this->safePeriod($r->query('period') ?? '30d');

        return $this->renderPage('pages', [
            'base'   => $r->basePath(),
            'period' => $period,
            'items'  => $this->svc->topPages(100, $period),
        ]);
    }

    public function referrers(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $period = $this->safePeriod($r->query('period') ?? '30d');

        return $this->renderPage('referrers', [
            'base'    => $r->basePath(),
            'period'  => $period,
            'items'   => $this->svc->topReferrers(100, $period),
            'sources' => $this->svc->sourceStats($period),
        ]);
    }

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        return $this->renderPage('settings', [
            'base'     => $r->basePath(),
            'settings' => $this->svc->getSettings(),
            'totals'   => $this->svc->totalRows(),
            'saved'    => ($r->query('saved') ?? '') === '1',
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        foreach (['enabled', 'exclude_admin', 'anonymize_ip', 'exclude_bots'] as $k) {
            $this->svc->saveSetting($k, $r->post($k) ? '1' : '0');
        }
        $this->svc->saveSetting(
            'retention_days',
            in_array($r->post('retention_days'), ['30','90','180','365','730','0'], true)
                ? (string)$r->post('retention_days')
                : '365'
        );
        $this->svc->saveSetting('exclude_ips', trim((string)($r->post('exclude_ips') ?? '')));

        return Response::redirect($r->basePath() . '/manage/gcrating/settings?saved=1');
    }

    public function clearData(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        if (($r->post('confirm_clear') ?? '') === 'DELETE') {
            $this->svc->truncate();
        }

        return Response::redirect($r->basePath() . '/manage/gcrating/settings?saved=1');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        return null;
    }

    private function safePeriod(?string $raw): string
    {
        return in_array($raw, ['today', '7d', '30d', '90d', 'all'], true) ? $raw : '30d';
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
