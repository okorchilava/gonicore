<?php
declare(strict_types=1);

namespace GCWeather;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GCWeatherAdminController
{
    public function __construct(
        private readonly GCWeatherService $svc,
        private readonly QueryBuilder     $qb,
        private readonly LoginService     $auth,
        private readonly HookManager      $hooks,
        private readonly string           $siteName = 'GoniCore',
    ) {}

    // ── Location list ──────────────────────────────────────────────────────────

    public function locations(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $locs = $this->svc->allLocations();
        // Attach cached weather summary to each location
        foreach ($locs as &$loc) {
            $w = $this->svc->fetchWeather((int)$loc['id']);
            $loc['_weather'] = $w;
        }
        unset($loc);

        return $this->renderPage('locations', [
            'base'    => $r->basePath(),
            'locs'    => $locs,
            'saved'   => ($r->query('saved')    ?? '') === '1',
            'deleted' => ($r->query('deleted')  ?? '') === '1',
            'refreshed'=>($r->query('refreshed') ?? '') === '1',
        ]);
    }

    // ── Location form (add / edit) ─────────────────────────────────────────────

    public function locationForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id  = ($r->query('id') ?? '') !== '' ? (int)$r->query('id') : null;
        $loc = $id ? $this->svc->location($id) : null;

        return $this->renderPage('location_form', [
            'base'   => $r->basePath(),
            'loc'    => $loc,
            'isEdit' => $loc !== null,
        ]);
    }

    // ── Save location ──────────────────────────────────────────────────────────

    public function locationSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id   = ($r->post('id') ?? '') !== '' ? (int)$r->post('id') : null;
        $name = trim((string)($r->post('name') ?? ''));
        $lat  = (float)($r->post('latitude')  ?? 0);
        $lng  = (float)($r->post('longitude') ?? 0);

        if (!$name || ($lat === 0.0 && $lng === 0.0)) {
            return Response::redirect($r->basePath() . '/manage/gcweather/form' . ($id ? '?id=' . $id : ''));
        }

        $displayName = trim((string)($r->post('display_name') ?? '')) ?: $name;

        $data = [
            'name'         => $name,
            'display_name' => $displayName,
            'country_code' => strtoupper(trim((string)($r->post('country_code') ?? ''))),
            'timezone'     => trim((string)($r->post('timezone') ?? 'UTC')) ?: 'UTC',
            'latitude'     => round($lat, 6),
            'longitude'    => round($lng, 6),
            'active'       => $r->post('active') ? 1 : 0,
            'sort_order'   => max(0, (int)($r->post('sort_order') ?? 0)),
        ];

        $newId = $this->svc->saveLocation($data, $id);

        // Pre-fetch weather for new locations
        if (!$id) {
            $this->svc->refreshWeather($newId);
        }

        return Response::redirect($r->basePath() . '/manage/gcweather?saved=1');
    }

    // ── Delete location ────────────────────────────────────────────────────────

    public function locationDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id = (int)($r->post('id') ?? 0);
        if ($id > 0) $this->svc->deleteLocation($id);

        return Response::redirect($r->basePath() . '/manage/gcweather?deleted=1');
    }

    // ── Toggle active ──────────────────────────────────────────────────────────

    public function locationToggle(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id = (int)($r->post('id') ?? 0);
        if ($id > 0) $this->svc->toggleLocation($id);

        return Response::redirect($r->basePath() . '/manage/gcweather');
    }

    // ── Force-refresh weather ──────────────────────────────────────────────────

    public function locationRefresh(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id = (int)($r->post('id') ?? 0);
        if ($id > 0) $this->svc->refreshWeather($id);

        return Response::redirect($r->basePath() . '/manage/gcweather?refreshed=1');
    }

    // ── Settings ───────────────────────────────────────────────────────────────

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        return $this->renderPage('settings', [
            'base'     => $r->basePath(),
            'settings' => $this->svc->getSettings(),
            'saved'    => ($r->query('saved') ?? '') === '1',
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        // Validated selects
        $enumOpts = [
            'temperature_unit'    => ['celsius',    'fahrenheit'],
            'windspeed_unit'      => ['kmh',         'mph',   'ms'],
            'precipitation_unit'  => ['mm',          'inch'],
            'default_style'       => ['card',        'full',  'minimal'],
            'cache_minutes'       => ['5', '10', '15', '30', '60', '120'],
            'forecast_days'       => ['1', '3', '5', '7'],
        ];
        foreach ($enumOpts as $key => $allowed) {
            $val = (string)($r->post($key) ?? '');
            if (in_array($val, $allowed, true)) {
                $this->svc->saveSetting($key, $val);
            }
        }

        $toggles = ['show_feels_like','show_humidity','show_wind','show_pressure','show_sunrise_sunset','show_hourly','show_daily'];
        foreach ($toggles as $k) {
            $this->svc->saveSetting($k, $r->post($k) ? '1' : '0');
        }

        // If unit settings changed, invalidate all caches
        // (new units require fresh API calls with different params)
        $r->post('temperature_unit') && $this->invalidateAllCaches();

        return Response::redirect($r->basePath() . '/manage/gcweather/settings?saved=1');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function invalidateAllCaches(): void
    {
        try {
            $this->qb->table('gcweather_cache')
                ->where('id', '>', '0')
                ->update(['expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))]);
        } catch (\Throwable) {}
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
