<?php
declare(strict_types=1);

namespace GoniSeo;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

final class GoniSeoFrontController
{
    public function __construct(
        private readonly GoniSeoService $svc,
        private readonly string         $appUrl = '',
    ) {}

    // ── GET /sitemap.xml ───────────────────────────────────────────────────────

    public function sitemap(Request $r): Response
    {
        $xml = $this->svc->generateSitemap($this->appUrl);
        return Response::html($xml, 200, [
            'Content-Type'  => 'text/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    // ── GET /robots.txt ────────────────────────────────────────────────────────

    public function robots(Request $r): Response
    {
        if ($this->svc->getSetting('manage_robots', '1') !== '1') {
            // Fallback if manage_robots was turned off
            return Response::html("User-agent: *\nAllow: /\n", 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $content = $this->svc->getSetting('robots_txt', "User-agent: *\nAllow: /");

        return Response::html($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
