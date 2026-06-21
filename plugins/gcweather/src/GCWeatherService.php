<?php
declare(strict_types=1);

namespace GCWeather;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

/**
 * GCWeather — weather display plugin powered by Open-Meteo (free, no API key).
 *
 * Weather data is fetched server-side and cached in the database.
 * Three display styles: card (default), full (with hourly+daily forecast), minimal.
 */
final class GCWeatherService
{
    private static ?self $instance      = null;
    private static bool  $assetsInjected = false;
    private ?array        $settingsCache  = null;

    // WMO weather codes → icon + label (Georgian)
    private const WMO = [
        0  => ['icon_d' => '☀️',  'icon_n' => '🌙',  'label' => 'ნათელი ცა'],
        1  => ['icon_d' => '🌤️', 'icon_n' => '🌤️',  'label' => 'ძირითადად ნათელი'],
        2  => ['icon_d' => '⛅',  'icon_n' => '⛅',   'label' => 'ნაწილობრივ მოღრუბლული'],
        3  => ['icon_d' => '☁️',  'icon_n' => '☁️',   'label' => 'მოღრუბლული'],
        45 => ['icon_d' => '🌫️', 'icon_n' => '🌫️',  'label' => 'ნისლი'],
        48 => ['icon_d' => '🌫️', 'icon_n' => '🌫️',  'label' => 'სეტყვიანი ნისლი'],
        51 => ['icon_d' => '🌦️', 'icon_n' => '🌧️',  'label' => 'მსუბუქი ნამი'],
        53 => ['icon_d' => '🌦️', 'icon_n' => '🌧️',  'label' => 'ნამი'],
        55 => ['icon_d' => '🌧️', 'icon_n' => '🌧️',  'label' => 'ძლიერი ნამი'],
        61 => ['icon_d' => '🌧️', 'icon_n' => '🌧️',  'label' => 'მსუბუქი წვიმა'],
        63 => ['icon_d' => '🌧️', 'icon_n' => '🌧️',  'label' => 'წვიმა'],
        65 => ['icon_d' => '🌧️', 'icon_n' => '🌧️',  'label' => 'ძლიერი წვიმა'],
        66 => ['icon_d' => '🌨️', 'icon_n' => '🌨️',  'label' => 'ყინვიანი წვიმა'],
        67 => ['icon_d' => '🌨️', 'icon_n' => '🌨️',  'label' => 'ძლიერი ყინვიანი წვიმა'],
        71 => ['icon_d' => '🌨️', 'icon_n' => '🌨️',  'label' => 'მსუბუქი თოვლი'],
        73 => ['icon_d' => '❄️',  'icon_n' => '❄️',   'label' => 'თოვლი'],
        75 => ['icon_d' => '❄️',  'icon_n' => '❄️',   'label' => 'ძლიერი თოვლი'],
        77 => ['icon_d' => '🌨️', 'icon_n' => '🌨️',  'label' => 'სეტყვა'],
        80 => ['icon_d' => '🌦️', 'icon_n' => '🌧️',  'label' => 'მსუბუქი ნალექი'],
        81 => ['icon_d' => '🌧️', 'icon_n' => '🌧️',  'label' => 'ნალექი'],
        82 => ['icon_d' => '🌧️', 'icon_n' => '🌧️',  'label' => 'ძლიერი ნალექი'],
        85 => ['icon_d' => '🌨️', 'icon_n' => '🌨️',  'label' => 'თოვლის ნალექი'],
        86 => ['icon_d' => '❄️',  'icon_n' => '❄️',   'label' => 'ძლიერი თოვლის ნალექი'],
        95 => ['icon_d' => '⛈️',  'icon_n' => '⛈️',   'label' => 'ჭექა-ქუხილი'],
        96 => ['icon_d' => '⛈️',  'icon_n' => '⛈️',   'label' => 'ჭექა-ქუხილი სეტყვით'],
        99 => ['icon_d' => '⛈️',  'icon_n' => '⛈️',   'label' => 'ჭექა-ქუხილი მძიმე სეტყვით'],
    ];

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    // ── Singleton ──────────────────────────────────────────────────────────────

    public static function register(self $s): void    { self::$instance = $s; }
    public static function getInstance(): ?self        { return self::$instance; }
    public static function resetAssets(): void         { self::$assetsInjected = false; }

    // ── Settings ───────────────────────────────────────────────────────────────

    public function getSettings(): array
    {
        if ($this->settingsCache !== null) return $this->settingsCache;
        $rows = $this->conn->query("SELECT `key`, `value` FROM `gcweather_settings`");
        $this->settingsCache = [];
        foreach ($rows as $r) $this->settingsCache[(string)$r['key']] = (string)$r['value'];
        return $this->settingsCache;
    }

    public function getSetting(string $key, string $default = ''): string
    {
        return $this->getSettings()[$key] ?? $default;
    }

    public function saveSetting(string $key, string $value): void
    {
        $this->conn->execute(
            "INSERT INTO `gcweather_settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [$key, $value]
        );
        $this->settingsCache = null;
    }

    // ── Locations CRUD ─────────────────────────────────────────────────────────

    public function allLocations(): array
    {
        return $this->qb->table('gcweather_locations')->orderBy('sort_order','ASC')->orderBy('id','ASC')->get();
    }

    public function activeLocations(): array
    {
        return $this->qb->table('gcweather_locations')->where('active','=',1)->orderBy('sort_order','ASC')->get();
    }

    public function location(int $id): ?array
    {
        return $this->qb->table('gcweather_locations')->where('id','=',$id)->first() ?: null;
    }

    public function saveLocation(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('gcweather_locations')->where('id','=',$id)->update($data);
            return $id;
        }
        $this->qb->table('gcweather_locations')->insert($data);
        return (int)$this->conn->lastInsertId();
    }

    public function deleteLocation(int $id): void
    {
        // Cache is deleted via ON DELETE CASCADE
        $this->qb->table('gcweather_locations')->where('id','=',$id)->delete();
    }

    public function toggleLocation(int $id): void
    {
        $loc = $this->location($id);
        if ($loc) {
            $this->qb->table('gcweather_locations')
                ->where('id','=',$id)
                ->update(['active' => ((int)$loc['active'] === 1) ? 0 : 1]);
        }
    }

    // ── Weather data ───────────────────────────────────────────────────────────

    /**
     * Get weather for a location, using DB cache if still valid.
     * Falls back to stale cache if the API call fails.
     *
     * @return array<string,mixed>|null
     */
    public function fetchWeather(int $locationId): ?array
    {
        $loc = $this->location($locationId);
        if (!$loc) return null;

        // Try valid cache first
        $cached = $this->conn->queryOne(
            "SELECT `weather_json`, `fetched_at` FROM `gcweather_cache`
             WHERE `location_id` = ? AND `expires_at` > NOW() LIMIT 1",
            [$locationId]
        );
        if ($cached) {
            $data = json_decode((string)$cached['weather_json'], true);
            if ($data) return $data;
        }

        // Fetch fresh from API
        $fresh = $this->callApi($loc);

        if ($fresh) {
            $minutes = max(5, (int)$this->getSetting('cache_minutes', '30'));
            $now     = date('Y-m-d H:i:s');
            $expires = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
            $json    = json_encode($fresh, JSON_UNESCAPED_UNICODE);

            $this->conn->execute(
                "INSERT INTO `gcweather_cache` (`location_id`, `weather_json`, `fetched_at`, `expires_at`)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     `weather_json` = VALUES(`weather_json`),
                     `fetched_at`   = VALUES(`fetched_at`),
                     `expires_at`   = VALUES(`expires_at`)",
                [$locationId, $json, $now, $expires]
            );
            return $fresh;
        }

        // API failed — return stale cache if available
        $stale = $this->conn->queryOne(
            "SELECT `weather_json` FROM `gcweather_cache` WHERE `location_id` = ? LIMIT 1",
            [$locationId]
        );
        if ($stale) {
            $data = json_decode((string)$stale['weather_json'], true);
            if ($data) {
                $data['_stale'] = true;
                return $data;
            }
        }

        return null;
    }

    /**
     * Force-fetch fresh data from API, bypassing cache.
     * @return array<string,mixed>|null
     */
    public function refreshWeather(int $locationId): ?array
    {
        // Delete cached entry to force re-fetch on next call
        $this->conn->execute(
            "DELETE FROM `gcweather_cache` WHERE `location_id` = ?",
            [$locationId]
        );
        return $this->fetchWeather($locationId);
    }

    /**
     * Call Open-Meteo forecast API and return normalised data.
     * @return array<string,mixed>|null
     */
    private function callApi(array $loc): ?array
    {
        $s = $this->getSettings();
        $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude'           => $loc['latitude'],
            'longitude'          => $loc['longitude'],
            'current'            => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weathercode,surface_pressure,windspeed_10m,winddirection_10m,is_day',
            'hourly'             => 'temperature_2m,precipitation_probability,weathercode,windspeed_10m',
            'daily'              => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,windspeed_10m_max,sunrise,sunset',
            'timezone'           => $loc['timezone'] ?: 'auto',
            'forecast_days'      => min(7, max(1, (int)($s['forecast_days'] ?? 7))),
            'temperature_unit'   => $s['temperature_unit']   ?? 'celsius',
            'windspeed_unit'     => $s['windspeed_unit']     ?? 'kmh',
            'precipitation_unit' => $s['precipitation_unit'] ?? 'mm',
        ]);

        $raw = $this->httpGet($url);
        if (!$raw) return null;

        $decoded = json_decode($raw, true);
        if (!$decoded || empty($decoded['current']['temperature_2m'])) return null;

        return $this->parseResponse($decoded, (string)($s['temperature_unit'] ?? 'celsius'), (string)($s['windspeed_unit'] ?? 'kmh'), (string)($s['precipitation_unit'] ?? 'mm'));
    }

    /**
     * Normalise column-oriented API response into row-oriented arrays.
     * @return array<string,mixed>
     */
    private function parseResponse(array $raw, string $tempUnit, string $windUnit, string $precipUnit): array
    {
        $c = $raw['current'];
        $current = [
            'temp'       => round((float)($c['temperature_2m']       ?? 0), 1),
            'feels_like' => round((float)($c['apparent_temperature']  ?? 0), 1),
            'humidity'   => (int)($c['relative_humidity_2m']          ?? 0),
            'precip'     => round((float)($c['precipitation']         ?? 0), 1),
            'pressure'   => (int)round((float)($c['surface_pressure'] ?? 0)),
            'wind_speed' => round((float)($c['windspeed_10m']         ?? 0), 1),
            'wind_dir'   => (int)($c['winddirection_10m']             ?? 0),
            'code'       => (int)($c['weathercode']                   ?? 0),
            'is_day'     => (bool)($c['is_day']                       ?? 1),
            'time'       => (string)($c['time']                       ?? ''),
        ];

        // Hourly — find current-hour index, take next 24
        $hRaw = $raw['hourly'] ?? [];
        $hTimes = $hRaw['time'] ?? [];
        $curHour = substr((string)$current['time'], 0, 14) . '00'; // e.g. "2024-01-01T15:00"
        $startIdx = 0;
        foreach ($hTimes as $i => $t) {
            if ($t >= $curHour) { $startIdx = $i; break; }
        }
        $hourly = [];
        for ($i = $startIdx, $max = min($startIdx + 24, count($hTimes)); $i < $max; $i++) {
            $hourly[] = [
                'time'        => substr((string)($hTimes[$i] ?? ''), 11, 5),
                'temp'        => round((float)($hRaw['temperature_2m'][$i] ?? 0), 1),
                'precip_prob' => (int)($hRaw['precipitation_probability'][$i] ?? 0),
                'code'        => (int)($hRaw['weathercode'][$i] ?? 0),
                'wind'        => round((float)($hRaw['windspeed_10m'][$i] ?? 0), 1),
            ];
        }

        // Daily — row-oriented
        $dRaw    = $raw['daily'] ?? [];
        $dTimes  = $dRaw['time'] ?? [];
        $today   = date('Y-m-d');
        $dayNamesGe = ['კვი','ორშ','სამ','ოთხ','ხუთ','პარ','შაბ'];
        $daily = [];
        foreach ($dTimes as $i => $date) {
            $dow     = (int)date('w', strtotime((string)$date));
            $daily[] = [
                'date'        => $date,
                'name'        => ($date === $today) ? 'დღეს' : $dayNamesGe[$dow],
                'code'        => (int)($dRaw['weathercode'][$i]                    ?? 0),
                'max'         => round((float)($dRaw['temperature_2m_max'][$i]     ?? 0), 1),
                'min'         => round((float)($dRaw['temperature_2m_min'][$i]     ?? 0), 1),
                'precip_prob' => (int)($dRaw['precipitation_probability_max'][$i]  ?? 0),
                'precip'      => round((float)($dRaw['precipitation_sum'][$i]      ?? 0), 1),
                'wind'        => round((float)($dRaw['windspeed_10m_max'][$i]      ?? 0), 1),
                'sunrise'     => substr((string)($dRaw['sunrise'][$i] ?? ''), 11, 5),
                'sunset'      => substr((string)($dRaw['sunset'][$i]  ?? ''), 11, 5),
            ];
        }

        return [
            'current'      => $current,
            'hourly'       => $hourly,
            'daily'        => $daily,
            'temp_unit'    => $tempUnit,
            'wind_unit'    => $windUnit,
            'precip_unit'  => $precipUnit,
            'fetched_at'   => date('Y-m-d H:i:s'),
        ];
    }

    // ── Rendering ──────────────────────────────────────────────────────────────

    /**
     * Render a weather widget for the given location.
     * Style: 'card' | 'full' | 'minimal'
     */
    public function render(int $locationId, string $style = ''): string
    {
        $loc = $this->location($locationId);
        if (!$loc) return '';

        $style = $style ?: $this->getSetting('default_style', 'card');
        if (!in_array($style, ['card', 'full', 'minimal'], true)) $style = 'card';

        $w = $this->fetchWeather($locationId);
        if (!$w) {
            return $this->renderError($loc['display_name'] ?: $loc['name']);
        }

        $html = match ($style) {
            'full'    => $this->renderFull($w, $loc),
            'minimal' => $this->renderMinimal($w, $loc),
            default   => $this->renderCard($w, $loc),
        };

        return $html . $this->renderAssets();
    }

    // ── Private render helpers ─────────────────────────────────────────────────

    private function renderCard(array $w, array $loc): string
    {
        $h    = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $c    = $w['current'];
        $s    = $this->getSettings();
        $icon = self::wmoIcon($c['code'], $c['is_day']);
        $cc   = self::wmoCondClass($c['code'], $c['is_day']);
        $unit = $w['temp_unit'] === 'fahrenheit' ? '°F' : '°C';
        $wU   = match($w['wind_unit']) { 'mph' => 'mph', 'ms' => 'm/s', default => 'km/h' };
        $name = $h($loc['display_name'] ?: $loc['name']);
        $flag = !empty($loc['country_code']) ? ' <small style="opacity:.65">' . $h($loc['country_code']) . '</small>' : '';
        $time = $c['time'] ? date('H:i', strtotime($c['time'])) : '';
        $updMinutes = max(0, (int)((time() - strtotime($w['fetched_at'])) / 60));
        $updStr  = $updMinutes < 2 ? 'ახლახანს' : $updMinutes . ' წთ წინ';
        $stale   = !empty($w['_stale']) ? ' ⚠️' : '';

        $out  = '<div class="gcw-widget">';
        $out .= '<div class="gcw-main ' . $cc . '">';
        $out .= '<div class="gcw-top">';
        $out .= '<div class="gcw-location-name">' . $name . $flag . '</div>';
        if ($time) $out .= '<div class="gcw-time">' . $h($time) . '</div>';
        $out .= '</div>';
        $out .= '<div class="gcw-center">';
        $out .= '<div class="gcw-icon-big">' . $icon . '</div>';
        $out .= '<div>';
        $out .= '<div class="gcw-temp-big">' . round($c['temp']) . $h($unit) . '</div>';
        $out .= '<div class="gcw-condition-label">' . $h(self::wmoLabel($c['code'])) . '</div>';
        if (($s['show_feels_like'] ?? '1') === '1') {
            $out .= '<div class="gcw-feels">Feels like ' . round($c['feels_like']) . $h($unit) . '</div>';
        }
        $out .= '</div></div>'; // center

        $meta = [];
        if (($s['show_humidity'] ?? '1') === '1') $meta[] = '💧 ' . $c['humidity'] . '%';
        if (($s['show_wind']     ?? '1') === '1') $meta[] = '💨 ' . round($c['wind_speed']) . ' ' . $wU;
        if (($s['show_pressure'] ?? '0') === '1') $meta[] = '📊 ' . $c['pressure'] . ' hPa';
        if ($c['precip'] > 0)                      $meta[] = '☔ ' . $c['precip'] . ' ' . ($w['precip_unit'] === 'inch' ? 'in' : 'mm');

        if ($meta) {
            $out .= '<div class="gcw-meta">';
            foreach ($meta as $m) $out .= '<span class="gcw-meta-item">' . $h($m) . '</span>';
            $out .= '</div>';
        }

        if (($s['show_sunrise_sunset'] ?? '1') === '1' && !empty($w['daily'][0]['sunrise'])) {
            $d = $w['daily'][0];
            $out .= '<div class="gcw-sun-row">';
            $out .= '<span>🌅 ' . $h($d['sunrise']) . '</span>';
            $out .= '<span>🌇 ' . $h($d['sunset'])  . '</span>';
            $out .= '</div>';
        }

        $out .= '</div>'; // gcw-main
        $out .= '<div class="gcw-updated">' . $h($updStr . $stale) . '</div>';
        $out .= '</div>'; // gcw-widget

        return $out;
    }

    private function renderFull(array $w, array $loc): string
    {
        $h    = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $unit = $w['temp_unit'] === 'fahrenheit' ? '°F' : '°C';
        $s    = $this->getSettings();

        // Start with card (but keep widget div open to add sections)
        $cardHtml = $this->renderCard($w, $loc);
        // renderCard already closes the widget. Let's rebuild:
        // Re-render without closing outer div

        $c   = $w['current'];
        $cc  = self::wmoCondClass($c['code'], $c['is_day']);
        $wU  = match($w['wind_unit']) { 'mph' => 'mph', 'ms' => 'm/s', default => 'km/h' };
        $name = $h($loc['display_name'] ?: $loc['name']);
        $flag = !empty($loc['country_code']) ? ' <small style="opacity:.65">' . $h($loc['country_code']) . '</small>' : '';
        $time = $c['time'] ? date('H:i', strtotime($c['time'])) : '';
        $updMinutes = max(0, (int)((time() - strtotime($w['fetched_at'])) / 60));
        $updStr = $updMinutes < 2 ? 'ახლახანს' : $updMinutes . ' წთ წინ';

        $out  = '<div class="gcw-widget gcw-full">';
        $out .= '<div class="gcw-main ' . $cc . '">';
        $out .= '<div class="gcw-top">';
        $out .= '<div class="gcw-location-name">' . $name . $flag . '</div>';
        if ($time) $out .= '<div class="gcw-time">' . $h($time) . '</div>';
        $out .= '</div>';
        $out .= '<div class="gcw-center">';
        $out .= '<div class="gcw-icon-big">' . self::wmoIcon($c['code'], $c['is_day']) . '</div>';
        $out .= '<div><div class="gcw-temp-big">' . round($c['temp']) . $h($unit) . '</div>';
        $out .= '<div class="gcw-condition-label">' . $h(self::wmoLabel($c['code'])) . '</div>';
        if (($s['show_feels_like'] ?? '1') === '1') {
            $out .= '<div class="gcw-feels">Feels like ' . round($c['feels_like']) . $h($unit) . '</div>';
        }
        $out .= '</div></div>';

        $meta = [];
        if (($s['show_humidity'] ?? '1') === '1') $meta[] = '💧 ' . $c['humidity'] . '%';
        if (($s['show_wind']     ?? '1') === '1') $meta[] = '💨 ' . round($c['wind_speed']) . ' ' . $wU;
        if (($s['show_pressure'] ?? '0') === '1') $meta[] = '📊 ' . $c['pressure'] . ' hPa';
        if ($c['precip'] > 0)                      $meta[] = '☔ ' . $c['precip'] . ' ' . ($w['precip_unit'] === 'inch' ? 'in' : 'mm');
        if ($meta) {
            $out .= '<div class="gcw-meta">';
            foreach ($meta as $m) $out .= '<span class="gcw-meta-item">' . $h($m) . '</span>';
            $out .= '</div>';
        }
        if (($s['show_sunrise_sunset'] ?? '1') === '1' && !empty($w['daily'][0]['sunrise'])) {
            $d = $w['daily'][0];
            $out .= '<div class="gcw-sun-row"><span>🌅 ' . $h($d['sunrise']) . '</span><span>🌇 ' . $h($d['sunset']) . '</span></div>';
        }
        $out .= '</div>'; // gcw-main

        // Hourly
        if (($s['show_hourly'] ?? '1') === '1' && !empty($w['hourly'])) {
            $out .= '<div class="gcw-section-title">🕐 საათობრივი პროგნოზი</div>';
            $out .= '<div class="gcw-hourly-wrap"><div class="gcw-hourly">';
            foreach ($w['hourly'] as $hr) {
                $isDay = (int)date('H', strtotime('today ' . $hr['time'])) >= 6 && (int)date('H', strtotime('today ' . $hr['time'])) < 20;
                $out .= '<div class="gcw-hour">';
                $out .= '<div class="gcw-hour-time">' . $h($hr['time']) . '</div>';
                $out .= '<div class="gcw-hour-icon">' . self::wmoIcon((int)$hr['code'], $isDay) . '</div>';
                $out .= '<div class="gcw-hour-temp">' . round($hr['temp']) . '°</div>';
                if ($hr['precip_prob'] > 0) {
                    $out .= '<div class="gcw-hour-precip">' . (int)$hr['precip_prob'] . '%</div>';
                }
                $out .= '</div>';
            }
            $out .= '</div></div>';
        }

        // Daily
        if (($s['show_daily'] ?? '1') === '1' && !empty($w['daily'])) {
            $out .= '<div class="gcw-section-title">📅 7-დღიანი პროგნოზი</div>';
            $out .= '<div class="gcw-daily">';
            foreach ($w['daily'] as $day) {
                $out .= '<div class="gcw-day">';
                $out .= '<span class="gcw-day-name">' . $h($day['name']) . '</span>';
                $out .= '<span class="gcw-day-icon">' . self::wmoIcon((int)$day['code'], true) . '</span>';
                $out .= '<span class="gcw-day-precip">';
                if ($day['precip_prob'] > 0) $out .= '💧 ' . (int)$day['precip_prob'] . '%';
                $out .= '</span>';
                $out .= '<div class="gcw-day-temps">';
                $out .= '<span class="gcw-day-max">' . round($day['max']) . '°</span>';
                $out .= '<span class="gcw-day-min">' . round($day['min']) . '°</span>';
                $out .= '</div>';
                $out .= '</div>';
            }
            $out .= '</div>';
        }

        $out .= '<div class="gcw-updated">' . $h($updStr) . '</div>';
        $out .= '</div>'; // gcw-widget

        return $out;
    }

    private function renderMinimal(array $w, array $loc): string
    {
        $h    = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $c    = $w['current'];
        $unit = $w['temp_unit'] === 'fahrenheit' ? '°F' : '°C';
        $name = $h($loc['display_name'] ?: $loc['name']);
        $icon = self::wmoIcon($c['code'], $c['is_day']);

        return '<span class="gcw-minimal">'
             . '<span class="gcw-minimal-icon">' . $icon . '</span>'
             . '<span class="gcw-minimal-temp">' . round($c['temp']) . $h($unit) . '</span>'
             . '<span class="gcw-minimal-name">' . $name . '</span>'
             . '</span>';
    }

    private function renderError(string $name): string
    {
        $h = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return '<div class="gcw-widget"><div class="gcw-main gcw-overcast" style="text-align:center;padding:32px 20px">'
             . '<div style="font-size:2rem;margin-bottom:8px">⚠️</div>'
             . '<div style="font-weight:700;margin-bottom:4px">' . $h . '</div>'
             . '<div style="opacity:.8;font-size:.85rem">ამინდის მონაცემი მიუწვდომელია</div>'
             . '</div></div>';
    }

    private function renderAssets(): string
    {
        if (self::$assetsInjected) return '';
        self::$assetsInjected = true;

        return <<<'CSS'
<style id="gcw-style">
.gcw-widget{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.15);max-width:340px}
.gcw-full{max-width:380px}
.gcw-main{padding:22px 20px 18px;position:relative}
/* Condition themes — css vars for text color */
.gcw-clear-day{background:linear-gradient(135deg,#f6d365,#fda085);--gcw-t:#7c2d12;--gcw-s:rgba(124,45,18,.75);--gcw-b:rgba(124,45,18,.2)}
.gcw-clear-night{background:linear-gradient(135deg,#2c3e50,#0a3d62);--gcw-t:#fff;--gcw-s:rgba(255,255,255,.75);--gcw-b:rgba(255,255,255,.2)}
.gcw-few-clouds-day{background:linear-gradient(135deg,#ffecd2,#a8edea);--gcw-t:#1e3a5f;--gcw-s:rgba(30,58,95,.7);--gcw-b:rgba(30,58,95,.2)}
.gcw-few-clouds-night{background:linear-gradient(135deg,#373b44,#4286f4);--gcw-t:#fff;--gcw-s:rgba(255,255,255,.75);--gcw-b:rgba(255,255,255,.2)}
.gcw-overcast{background:linear-gradient(135deg,#636e72,#2d3436);--gcw-t:#fff;--gcw-s:rgba(255,255,255,.75);--gcw-b:rgba(255,255,255,.2)}
.gcw-fog{background:linear-gradient(135deg,#d7d2cc,#304352);--gcw-t:#fff;--gcw-s:rgba(255,255,255,.75);--gcw-b:rgba(255,255,255,.2)}
.gcw-rain{background:linear-gradient(135deg,#667eea,#764ba2);--gcw-t:#fff;--gcw-s:rgba(255,255,255,.75);--gcw-b:rgba(255,255,255,.2)}
.gcw-showers{background:linear-gradient(135deg,#4facfe,#00f2fe);--gcw-t:#0c4a6e;--gcw-s:rgba(12,74,110,.75);--gcw-b:rgba(12,74,110,.2)}
.gcw-snow{background:linear-gradient(135deg,#e0f7fa,#b3e5fc);--gcw-t:#1e3a5f;--gcw-s:rgba(30,58,95,.7);--gcw-b:rgba(30,58,95,.2)}
.gcw-thunder{background:linear-gradient(135deg,#1a1a2e,#4a148c);--gcw-t:#fff;--gcw-s:rgba(255,255,255,.75);--gcw-b:rgba(255,255,255,.2)}
/* Text using vars */
.gcw-main{color:var(--gcw-t,#fff)}
.gcw-time,.gcw-feels,.gcw-condition-label{color:var(--gcw-s,rgba(255,255,255,.8))}
.gcw-meta{border-top:1px solid var(--gcw-b,rgba(255,255,255,.2))}
.gcw-sun-row{border-top:1px solid var(--gcw-b,rgba(255,255,255,.2))}
/* Structure */
.gcw-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px}
.gcw-location-name{font-size:1rem;font-weight:700}
.gcw-time{font-size:.8rem}
.gcw-center{display:flex;align-items:center;gap:14px;margin-bottom:14px}
.gcw-icon-big{font-size:3.2rem;line-height:1;flex-shrink:0}
.gcw-temp-big{font-size:3rem;font-weight:900;line-height:1}
.gcw-condition-label{font-size:.9rem;margin-top:4px}
.gcw-feels{font-size:.8rem;margin-top:2px}
.gcw-meta{display:flex;flex-wrap:wrap;gap:6px 14px;font-size:.8rem;padding-top:11px;margin-top:4px}
.gcw-meta-item{white-space:nowrap}
.gcw-sun-row{display:flex;justify-content:space-between;font-size:.8rem;padding-top:10px;margin-top:8px}
.gcw-updated{font-size:.7rem;color:#94a3b8;text-align:right;padding:4px 12px 8px;background:#fff}
/* Hourly */
.gcw-section-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;padding:10px 14px 4px;background:#fff}
.gcw-hourly-wrap{overflow-x:auto;background:#fff}
.gcw-hourly{display:flex;padding:4px 8px 12px;gap:2px}
.gcw-hour{flex:0 0 52px;text-align:center;padding:6px 2px}
.gcw-hour-time{font-size:.72rem;font-weight:600;color:#64748b}
.gcw-hour-icon{font-size:1.3rem;margin:4px 0}
.gcw-hour-temp{font-weight:700;color:#1e293b;font-size:.85rem}
.gcw-hour-precip{color:#3b82f6;font-size:.7rem;margin-top:2px}
/* Daily */
.gcw-daily{background:#fff;padding:4px 0 8px}
.gcw-day{display:flex;align-items:center;padding:7px 14px;gap:10px}
.gcw-day:not(:last-child){border-bottom:1px solid #f1f5f9}
.gcw-day-name{width:34px;font-size:.82rem;font-weight:700;color:#475569;flex-shrink:0}
.gcw-day-icon{font-size:1.3rem;flex-shrink:0}
.gcw-day-precip{flex:1;font-size:.75rem;color:#3b82f6;white-space:nowrap}
.gcw-day-temps{display:flex;gap:6px;font-size:.85rem;white-space:nowrap}
.gcw-day-max{font-weight:800;color:#1e293b}
.gcw-day-min{color:#94a3b8}
/* Minimal */
.gcw-minimal{display:inline-flex;align-items:center;gap:6px;font-family:-apple-system,sans-serif;font-size:1rem}
.gcw-minimal-icon{font-size:1.3rem}
.gcw-minimal-temp{font-weight:700;color:#1e293b}
.gcw-minimal-name{color:#64748b;font-size:.85em}
</style>
CSS;
    }

    // ── Static helpers ─────────────────────────────────────────────────────────

    public static function wmoIcon(int $code, bool $isDay = true): string
    {
        $entry = self::WMO[$code] ?? self::WMO[3];
        return $isDay ? $entry['icon_d'] : $entry['icon_n'];
    }

    public static function wmoLabel(int $code): string
    {
        return self::WMO[$code]['label'] ?? 'უცნობი';
    }

    /** CSS class name encoding weather condition + day/night */
    public static function wmoCondClass(int $code, bool $isDay): string
    {
        if ($code === 0)          return $isDay ? 'gcw-clear-day'        : 'gcw-clear-night';
        if ($code <= 2)           return $isDay ? 'gcw-few-clouds-day'   : 'gcw-few-clouds-night';
        if ($code === 3)          return 'gcw-overcast';
        if ($code <= 48)          return 'gcw-fog';
        if ($code <= 67)          return 'gcw-rain';
        if ($code <= 77)          return 'gcw-snow';
        if ($code <= 82)          return 'gcw-showers';
        if ($code <= 86)          return 'gcw-snow';
        return 'gcw-thunder';
    }

    public static function windDirection(int $deg): string
    {
        $dirs = ['N','NE','E','SE','S','SW','W','NW'];
        return $dirs[(int)round($deg / 45) % 8];
    }

    // ── HTTP helpers ───────────────────────────────────────────────────────────

    private function httpGet(string $url): string|false
    {
        // Try file_get_contents first (simpler)
        if (ini_get('allow_url_fopen')) {
            $ctx  = stream_context_create(['http' => [
                'timeout' => 10,
                'method'  => 'GET',
                'header'  => "Accept: application/json\r\nUser-Agent: GCWeather/1.0\r\n",
            ]]);
            $res = @file_get_contents($url, false, $ctx);
            if ($res !== false) return $res;
        }

        // Curl fallback
        if (!function_exists('curl_init')) return false;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'GCWeather/1.0',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $res = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        return ($err === 0 && is_string($res)) ? $res : false;
    }
}
