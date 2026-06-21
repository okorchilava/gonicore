<?php

declare(strict_types=1);

namespace GoniCore\Modules\Settings;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

/**
 * Public read-only REST API for site settings.
 *
 * GET /api/v1/settings
 *   Returns only the keys safe for public consumption (no internals/secrets).
 */
final class SettingsController
{
    /** Keys exposed to the public API. Extend this list as needed. */
    private const PUBLIC_KEYS = [
        'site_name',
        'site_tagline',
        'site_url',
        'site_language',
        'homepage_type',
        'homepage_page_id',
        'posts_per_page',
        'date_format',
        'time_format',
        'timezone',
    ];

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /** GET /api/v1/settings */
    public function index(Request $request): Response
    {
        $data = [];
        foreach (self::PUBLIC_KEYS as $key) {
            $data[$key] = $this->settings->get($key);
        }

        return Response::json(['data' => $data]);
    }
}
