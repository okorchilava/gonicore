<?php

declare(strict_types=1);

/**
 * GoniCore Default Theme — Template Helpers
 *
 * Included ONCE by ThemeController before any view is rendered,
 * so all helper functions are available in both inner views and layout.
 */

if (!function_exists('e')) {
    /**
     * Escape a string for safe HTML output.
     */
    function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('excerpt')) {
    /**
     * Strip tags and truncate to $len characters.
     */
    function excerpt(string $html, int $len = 140): string
    {
        $plain = strip_tags($html);
        if (mb_strlen($plain) <= $len) {
            return $plain;
        }
        return rtrim(mb_substr($plain, 0, $len)) . '…';
    }
}

if (!function_exists('flag_img')) {
    /**
     * Return an <img> tag for a country flag using flagcdn.com.
     * Maps language codes to ISO 3166-1 alpha-2 country codes.
     */
    function flag_img(string $langCode, int $w = 20, int $h = 15): string
    {
        static $map = [
            'en' => 'gb', 'ka' => 'ge', 'ru' => 'ru', 'fr' => 'fr',
            'de' => 'de', 'es' => 'es', 'it' => 'it', 'tr' => 'tr',
            'ar' => 'sa', 'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr',
            'pt' => 'pt', 'nl' => 'nl', 'pl' => 'pl', 'uk' => 'ua',
            'he' => 'il', 'hi' => 'in', 'sv' => 'se', 'no' => 'no',
            'da' => 'dk', 'fi' => 'fi', 'cs' => 'cz', 'sk' => 'sk',
            'ro' => 'ro', 'hu' => 'hu', 'bg' => 'bg', 'hr' => 'hr',
        ];
        $country = $map[strtolower($langCode)] ?? strtolower($langCode);
        $url = "https://flagcdn.com/{$w}x{$h}/{$country}.png";
        return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
             . 'width="' . $w . '" height="' . $h . '" '
             . 'alt="' . htmlspecialchars($langCode, ENT_QUOTES, 'UTF-8') . '" '
             . 'style="border-radius:2px;vertical-align:middle;object-fit:cover">';
    }
}

if (!function_exists('t')) {
    /**
     * Translate a key via the active LanguageService.
     * Falls back to the key itself if no translation is found.
     *
     * @param array<string,string> $replace  Named replacements, e.g. ['name' => 'John']
     */
    function t(string $key, array $replace = []): string
    {
        static $service = null;
        if ($service === null) {
            // Lazily resolved — LanguageService stores translations statically
            $service = \GoniCore\Modules\Language\LanguageService::class;
        }
        // Access the static helper via a globally available instance bound at boot time
        global $langService;
        if ($langService instanceof \GoniCore\Modules\Language\LanguageService) {
            return $langService->t($key, $replace);
        }
        // Fallback: return key
        return $key;
    }
}

if (!function_exists('fmt_date')) {
    /**
     * Format an ISO-8601 datetime using the site's configured date format.
     * Falls back to 'M j, Y' if no format is available.
     */
    function fmt_date(string $iso, ?string $format = null): string
    {
        global $dateFormat;
        $fmt = $format ?? (is_string($dateFormat) ? $dateFormat : 'M j, Y');
        try {
            return (new DateTimeImmutable($iso))->format($fmt);
        } catch (Throwable) {
            return $iso;
        }
    }
}
