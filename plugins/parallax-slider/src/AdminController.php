<?php
declare(strict_types=1);

namespace ParallaxSlider;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

final class AdminController
{
    private string $viewsDir;

    public function __construct(
        private readonly SliderService $service,
        private readonly LoginService  $auth,
        private readonly HookManager   $hooks,
        private readonly string        $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__) . '/views/admin';
    }

    // ── Guard ─────────────────────────────────────────────────────────────────

    private function guard(Request $request): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }
        return null;
    }

    // ── Sliders list ──────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        require_once dirname(__DIR__, 3) . '/themes/default/views/helpers.php';
        $sliders = $this->service->allSliders();
        $base    = $request->basePath();
        $success = $request->query('success');
        $error   = $request->query('error');
        return $this->render('sliders', compact('sliders', 'base', 'success', 'error'), $request);
    }

    public function create(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $name = trim((string) $request->post('name', 'New Slider'));
        $id   = $this->service->createSlider($name ?: 'New Slider');
        return Response::redirect($request->basePath() . '/manage/sliders/' . $id . '/edit?success=' . urlencode('Slider created.'));
    }

    public function delete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->service->deleteSlider((int) $request->getAttribute('id'));
        return Response::redirect($request->basePath() . '/manage/sliders?success=' . urlencode('Slider deleted.'));
    }

    // ── Slider editor ─────────────────────────────────────────────────────────

    public function edit(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        require_once dirname(__DIR__, 3) . '/themes/default/views/helpers.php';
        $id      = (int) $request->getAttribute('id');
        $slider  = $this->service->getSlider($id);
        if (!$slider) return Response::redirect($request->basePath() . '/manage/sliders');

        $slides   = $this->service->getSlides($id);
        $settings = $this->service->decodeSettings((string)$slider['settings']);
        $base     = $request->basePath();
        $success  = $request->query('success');
        $animsIn  = SliderService::animationsIn();
        $animsOut = SliderService::animationsOut();
        return $this->render('editor', compact(
            'slider','slides','settings','base','success','animsIn','animsOut'
        ), $request);
    }

    public function updateSettings(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id   = (int) $request->getAttribute('id');
        $json = $request->json() ?: [];
        $name = trim((string)($json['name'] ?? ''));
        $raw  = is_array($json['settings'] ?? null) ? $json['settings'] : [];
        $settings = [
            'height'           => trim((string)($raw['height']           ?? '560px')),
            'autoplay'         => !empty($raw['autoplay']),
            'autoplay_speed'   => max(500, (int)($raw['autoplay_speed']  ?? 6000)),
            'transition'       => (string)($raw['transition']           ?? 'fade'),
            'transition_speed' => max(100, (int)($raw['transition_speed'] ?? 900)),
            'mouse_parallax'   => !empty($raw['mouse_parallax']),
            'mouse_strength'   => max(0, (int)($raw['mouse_strength']   ?? 25)),
            'show_arrows'      => !empty($raw['show_arrows']),
            'show_dots'        => !empty($raw['show_dots']),
            'loop'             => !empty($raw['loop']),
            'pause_on_hover'   => !empty($raw['pause_on_hover']),
        ];
        $this->service->updateSlider($id, $name ?: 'Untitled Slider', $settings);
        return Response::json(['ok' => true, 'name' => $name]);
    }

    // ── Slides ────────────────────────────────────────────────────────────────

    public function addSlide(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $sliderId = (int) $request->getAttribute('id');
        $slideId  = $this->service->addSlide($sliderId);
        return Response::json(['ok' => true, 'slide_id' => $slideId]);
    }

    public function updateSlide(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id   = (int) $request->getAttribute('slide_id');
        $data = $request->json() ?: [];
        $this->service->updateSlide($id, $data);
        return Response::json(['ok' => true]);
    }

    public function deleteSlide(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id = (int) $request->getAttribute('slide_id');
        $this->service->deleteSlide($id);
        return Response::json(['ok' => true]);
    }

    public function reorderSlides(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $ids = (array) ($request->json()['ids'] ?? []);
        $this->service->reorderSlides(array_map('intval', $ids));
        return Response::json(['ok' => true]);
    }

    // ── Layers ────────────────────────────────────────────────────────────────

    public function getLayers(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $slideId = (int) $request->getAttribute('slide_id');
        $layers  = $this->service->getLayers($slideId);
        // Decode settings JSON for each layer
        foreach ($layers as &$l) {
            $l['settings'] = $this->service->decodeLayerSettings((string)$l['settings']);
        }
        return Response::json(['layers' => $layers]);
    }

    public function saveLayers(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $slideId = (int) $request->getAttribute('slide_id');
        $layers  = (array) ($request->json()['layers'] ?? []);
        $this->service->saveLayers($slideId, $layers);
        return Response::json(['ok' => true]);
    }

    // ── Frontend data ─────────────────────────────────────────────────────────

    /** GET /api/v1/sliders/{id} — public JSON for frontend hydration */
    public function apiGet(Request $request): Response
    {
        $id     = (int) $request->getAttribute('id');
        $slider = $this->service->getSlider($id);
        if (!$slider || !$slider['active']) {
            if ($request->query('preview')) {
                return Response::html('<p style="font-family:sans-serif;padding:40px;color:#ef4444">Slider #' . $id . ' not found or inactive.</p>', 404);
            }
            return Response::json(['error' => 'Not found'], 404);
        }

        $settings   = $this->service->decodeSettings((string)$slider['settings']);
        $slides     = $this->service->getSlides($id);
        $slidesData = [];
        foreach ($slides as $slide) {
            if (!$slide['active']) continue;
            $layers = $this->service->getLayers((int)$slide['id']);
            foreach ($layers as &$l) {
                $l['settings'] = $this->service->decodeLayerSettings((string)$l['settings']);
            }
            unset($l);
            $slide['layers'] = $layers;
            $slidesData[]    = $slide;
        }

        // ?preview=1 → standalone HTML preview page (used in the editor iframe)
        if ($request->query('preview')) {
            $pluginDir = dirname(__DIR__, 2);
            ob_start();
            include $pluginDir . '/views/frontend/render.php';
            $sliderHtml = (string) ob_get_clean();

            $title = htmlspecialchars((string)($slider['name'] ?? 'Slider Preview'), ENT_QUOTES);
            $html  = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} — Preview</title>
<style>*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}body{background:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh}</style>
</head>
<body>
{$sliderHtml}
</body>
</html>
HTML;
            return Response::html($html);
        }

        // Default: JSON API response
        $result = [];
        foreach ($slidesData as $slide) {
            $result[] = $slide;
        }
        return Response::json(['slider' => $slider, 'settings' => $settings, 'slides' => $result]);
    }

    // ── Renderer ─────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $data */
    private function render(string $tpl, array $data, Request $request): Response
    {
        $viewFile = $this->viewsDir . '/' . $tpl . '.php';
        if (!is_file($viewFile)) return Response::error("View not found: $tpl", 500);

        $base     = $request->basePath();
        $siteName = $this->siteName;
        $hooks    = $this->hooks;
        $activeNav = 'sliders';
        $pageTitle = 'Parallax Sliders';
        $user      = null; // not used in layout but avoids undefined var warning

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $viewFile;
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        // Wrap in manage layout
        $viewsDir = dirname(__DIR__, 3) . '/themes/default/views/manage';
        ob_start();
        try {
            include $viewsDir . '/layout.php';
            return Response::html((string) ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
