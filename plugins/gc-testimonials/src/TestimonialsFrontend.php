<?php

declare(strict_types=1);

namespace GCTestimonials;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\SessionManager;

/**
 * Front-end rendering for GC Testimonials.
 *
 * Three shortcodes (processed via the `the_content` filter):
 *   [gc_testimonials id="slug" limit="12"]   — responsive review grid
 *   [gc_testimonials_slider id="slug"]        — auto-playing slider
 *   [gc_testimonial_form id="slug"]           — moderated submission form
 *
 * Plus the AJAX endpoint POST /gc-testimonials/submit used by the form.
 */
final class TestimonialsFrontend
{
    private static bool $cssDone = false;

    public function __construct(
        private readonly TestimonialsService $service,
        private readonly SessionManager      $session,
    ) {}

    /** Replace every supported shortcode found in rendered content. */
    public function process(string $html): string
    {
        if ($html === '' || stripos($html, '[gc_testimonial') === false) {
            return $html;
        }
        $t = gc_plugin_translator(dirname(__DIR__));

        $html = (string) preg_replace_callback('/\[gc_testimonials_slider\b([^\]]*)\]/i',
            fn (array $m): string => $this->renderSlider($this->attrs($m[1]), $t), $html);

        $html = (string) preg_replace_callback('/\[gc_testimonial_form\b([^\]]*)\]/i',
            fn (array $m): string => $this->renderForm($this->attrs($m[1]), $t), $html);

        $html = (string) preg_replace_callback('/\[gc_testimonials\b([^\]]*)\]/i',
            fn (array $m): string => $this->renderList($this->attrs($m[1]), $t), $html);

        return $html;
    }

    // ── Renderers ───────────────────────────────────────────────────────────────

    /** @param array<string,string> $atts @param callable $t */
    private function renderList(array $atts, callable $t): string
    {
        $cid   = $this->resolveCampaign($atts, $found);
        if ($cid === null) {
            return $this->notice($t('front.none'));
        }
        $limit = isset($atts['limit']) ? (int) $atts['limit'] : 12;
        $items = $this->service->publicByCampaign($cid, $limit);
        if ($items === []) {
            return $this->notice($t('front.empty'));
        }

        $out  = $this->css();
        $out .= '<div class="gct-grid">';
        foreach ($items as $it) {
            $out .= $this->card($it);
        }
        $out .= '</div>';
        return $out;
    }

    /** @param array<string,string> $atts @param callable $t */
    private function renderSlider(array $atts, callable $t): string
    {
        $cid = $this->resolveCampaign($atts, $found);
        if ($cid === null) {
            return $this->notice($t('front.none'));
        }
        $items = $this->service->publicByCampaign($cid, 15);
        if ($items === []) {
            return $this->notice($t('front.empty'));
        }

        $id   = 'gct-slider-' . bin2hex(random_bytes(5));
        $out  = $this->css();
        $out .= '<div class="gct-slider" id="' . $id . '"><div class="gct-track">';
        foreach ($items as $it) {
            $out .= '<div class="gct-slide">' . $this->card($it, true) . '</div>';
        }
        $out .= '</div><div class="gct-nav">'
              . '<button type="button" class="gct-navbtn" data-dir="prev" aria-label="Previous">&#10094;</button>'
              . '<button type="button" class="gct-navbtn" data-dir="next" aria-label="Next">&#10095;</button>'
              . '</div></div>';
        $out .= $this->sliderJs($id);
        return $out;
    }

    /** @param array<string,string> $atts @param callable $t */
    private function renderForm(array $atts, callable $t): string
    {
        $cid = $this->resolveCampaign($atts, $found, allowGeneral: true);
        // For the form, an unknown slug just posts to the general bucket.
        $campaignId = $cid ?? 0;

        $action = $this->actionUrl();
        $csrf   = $this->session->csrfToken();
        $e      = self::class . '::esc';

        $out  = $this->css();
        $out .= '<form class="gct-form" action="' . self::esc($action) . '" method="post">';
        $out .= '<div class="gct-form-head">' . self::esc($t('front.form_intro')) . '</div>';
        $out .= '<input type="hidden" name="_csrf" value="' . self::esc($csrf) . '">';
        $out .= '<input type="hidden" name="campaign_id" value="' . (int) $campaignId . '">';
        // Honeypot — bots fill it, humans never see it.
        $out .= '<input type="text" name="website" class="gct-hp" tabindex="-1" autocomplete="off" aria-hidden="true">';

        $out .= '<div class="gct-form-row">';
        $out .= '<input type="text" name="name" class="gct-input" placeholder="' . self::esc($t('front.name_ph')) . '" required maxlength="120">';
        $out .= '<div class="gct-stars-input" role="radiogroup" aria-label="rating">';
        for ($i = 5; $i >= 1; $i--) {
            $out .= '<input type="radio" name="rating" id="gct-r' . $i . '" value="' . $i . '"' . ($i === 5 ? ' checked' : '') . '>'
                  . '<label for="gct-r' . $i . '" title="' . $i . '">&#9733;</label>';
        }
        $out .= '</div></div>';

        $out .= '<textarea name="text" class="gct-input gct-textarea" placeholder="' . self::esc($t('front.text_ph')) . '" required minlength="10" maxlength="2000"></textarea>';
        $out .= '<button type="submit" class="gct-submit">' . self::esc($t('front.submit')) . '</button>';
        $out .= '<div class="gct-success" hidden>' . self::esc($t('front.thanks')) . '</div>';
        $out .= '</form>';
        $out .= $this->formJs($t('front.error'));
        return $out;
    }

    // ── AJAX submit ─────────────────────────────────────────────────────────────

    public function submit(Request $request): Response
    {
        $t = gc_plugin_translator(dirname(__DIR__));

        // CSRF — anonymous visitors still have a session, so this works for all.
        if (!$this->session->verifyCsrf((string) $request->post('_csrf', ''))) {
            return Response::json(['ok' => false, 'error' => $t('front.error')], 403);
        }

        // Honeypot: a filled "website" field means a bot — pretend success.
        if (trim((string) $request->post('website', '')) !== '') {
            return Response::json(['ok' => true]);
        }

        $name   = trim(strip_tags((string) $request->post('name', '')));
        $text   = trim(strip_tags((string) $request->post('text', '')));
        $rating = (int) $request->post('rating', '5');
        $campId = max(0, (int) $request->post('campaign_id', '0'));

        if ($name === '' || mb_strlen($text) < 10 || $rating < 1 || $rating > 5) {
            return Response::json(['ok' => false, 'error' => $t('front.invalid')], 422);
        }

        $name = mb_substr($name, 0, 120);
        $text = mb_substr($text, 0, 2000);

        $this->service->submitPublic($campId, $name, $text, $rating);

        // Let the panel surface the pending review (best-effort).
        try {
            gc_emit('testimonial.submitted', $name, $campId);
        } catch (\Throwable) {
        }

        return Response::json(['ok' => true]);
    }

    // ── Internal ──────────────────────────────────────────────────────────────────

    /**
     * Resolve the campaign id from the shortcode's id="slug" attribute.
     * Returns null when a slug was given but no campaign matched (so list/slider
     * can show the "unknown" notice). With $allowGeneral, a missing slug → 0.
     *
     * @param array<string,string> $atts
     */
    private function resolveCampaign(array $atts, ?bool &$found = null, bool $allowGeneral = false): ?int
    {
        $slug  = trim((string) ($atts['id'] ?? ''));
        $found = false;
        if ($slug === '') {
            $found = true;
            return 0; // the "general" bucket
        }
        $c = $this->service->campaignBySlug($slug);
        if ($c === null) {
            return $allowGeneral ? 0 : null;
        }
        $found = true;
        return (int) $c['id'];
    }

    /** @param array<string,mixed> $it */
    private function card(array $it, bool $clamp = false): string
    {
        $text = (string) $it['testimonial_text'];
        if ($clamp && mb_strlen($text) > 220) {
            $text = mb_substr($text, 0, 220) . '…';
        }
        return '<div class="gct-card">'
            . '<div class="gct-stars">' . self::esc(TestimonialsService::stars((int) $it['rating'])) . '</div>'
            . '<div class="gct-text">' . nl2br(self::esc($text)) . '</div>'
            . '<div class="gct-user">'
            . TestimonialsService::avatar((string) $it['client_name'])
            . '<div class="gct-meta"><div class="gct-name">' . self::esc((string) $it['client_name']) . '</div>'
            . ((string) ($it['client_role'] ?? '') !== '' ? '<div class="gct-role">' . self::esc((string) $it['client_role']) . '</div>' : '')
            . '<div class="gct-date">' . self::esc(TestimonialsService::formatDate((string) $it['created_at'])) . '</div></div>'
            . '</div></div>';
    }

    private function notice(string $msg): string
    {
        return $this->css() . '<div class="gct-notice">' . self::esc($msg) . '</div>';
    }

    /** @param string $raw @return array<string,string> */
    private function attrs(string $raw): array
    {
        $out = [];
        if (preg_match_all('/(\w[\w-]*)\s*=\s*"([^"]*)"/', $raw, $m, PREG_SET_ORDER)) {
            foreach ($m as $a) {
                $out[strtolower($a[1])] = $a[2];
            }
        }
        return $out;
    }

    private function actionUrl(): string
    {
        $base = rtrim((string) gc_setting('site_url', ''), '/');
        return ($base !== '' ? $base : '') . '/gc-testimonials/submit';
    }

    public static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /** Scoped CSS — emitted once per request no matter how many shortcodes. */
    private function css(): string
    {
        if (self::$cssDone) {
            return '';
        }
        self::$cssDone = true;
        return <<<'CSS'
<style id="gct-css">
.gct-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:20px;margin:28px 0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Georgian",sans-serif}
.gct-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;display:flex;flex-direction:column;justify-content:space-between;box-sizing:border-box}
.gct-stars{color:#f59e0b;font-size:17px;letter-spacing:2px;margin-bottom:12px}
.gct-text{color:#374151;font-size:15px;line-height:1.65;margin-bottom:20px;white-space:pre-line}
.gct-user{display:flex;align-items:center;gap:12px;border-top:1px solid #f3f4f6;padding-top:15px}
.gct-avatar{border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px}
.gct-meta{line-height:1.3}
.gct-name{font-weight:700;color:#111827;font-size:14px}
.gct-role{color:#6b7280;font-size:12px}
.gct-date{color:#9ca3af;font-size:12px}
.gct-notice{text-align:center;padding:38px 20px;color:#6b7280;background:#f9fafb;border:1px dashed #e5e7eb;border-radius:16px;margin:28px 0;font-family:system-ui,sans-serif;font-size:15px}
/* slider */
.gct-slider{position:relative;margin:28px 0;font-family:system-ui,-apple-system,"Noto Sans Georgian",sans-serif}
.gct-track{display:flex;gap:20px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;scrollbar-width:none;-ms-overflow-style:none;padding-bottom:6px}
.gct-track::-webkit-scrollbar{display:none}
.gct-slide{flex:0 0 100%;scroll-snap-align:start}
@media(min-width:650px){.gct-slide{flex:0 0 calc(50% - 10px)}}
@media(min-width:1024px){.gct-slide{flex:0 0 calc(33.333% - 14px)}}
.gct-slide .gct-card{height:100%}
.gct-slide .gct-text{min-height:4.6em}
.gct-nav{display:flex;justify-content:center;gap:12px;margin-top:18px}
.gct-navbtn{width:44px;height:44px;border-radius:50%;border:1px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;transition:.2s}
.gct-navbtn:hover{border-color:#4f46e5;color:#4f46e5}
/* form */
.gct-form{max-width:640px;margin:32px auto;background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:34px;box-sizing:border-box;font-family:system-ui,-apple-system,"Noto Sans Georgian",sans-serif}
.gct-form-head{text-align:center;color:#6b7280;font-size:14px;margin-bottom:22px}
.gct-form-row{display:flex;gap:16px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.gct-form-row .gct-input{flex:1;min-width:200px;margin:0}
.gct-input{width:100%;padding:14px 16px;border:2px solid #f1f3f5;border-radius:12px;font-size:15px;font-family:inherit;color:#333;background:#f9fafb;box-sizing:border-box;transition:.2s;margin-bottom:16px}
.gct-input:focus{border-color:#4f46e5;background:#fff;outline:none;box-shadow:0 0 0 4px rgba(79,70,229,.1)}
.gct-textarea{min-height:120px;resize:vertical}
.gct-stars-input{display:inline-flex;flex-direction:row-reverse;background:#f9fafb;border:2px solid #f1f3f5;border-radius:12px;padding:8px 14px;height:52px;align-items:center}
.gct-stars-input input{display:none}
.gct-stars-input label{font-size:28px;color:#e5e7eb;cursor:pointer;transition:.15s;padding:0 2px;line-height:1}
.gct-stars-input input:checked~label,.gct-stars-input label:hover,.gct-stars-input label:hover~label{color:#f59e0b}
.gct-hp{position:absolute;left:-9999px;width:1px;height:1px;opacity:0}
.gct-submit{background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;padding:16px;width:100%;border-radius:12px;font-weight:700;font-size:16px;cursor:pointer;transition:.2s;box-shadow:0 6px 18px rgba(79,70,229,.25)}
.gct-submit:hover{filter:brightness(1.05)}
.gct-submit:disabled{opacity:.6;cursor:default}
.gct-success{margin-top:18px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:12px;padding:15px;text-align:center;font-weight:600}
@media(max-width:600px){.gct-form{padding:22px}.gct-form-row{flex-direction:column;align-items:stretch}}
</style>
CSS;
    }

    private function sliderJs(string $id): string
    {
        $j = json_encode($id);
        return <<<JS
<script>(function(){var c=document.getElementById({$j});if(!c)return;var tr=c.querySelector('.gct-track');var step=function(){var s=c.querySelector('.gct-slide');return s?s.offsetWidth+20:300;};var nx=function(){if(tr.scrollLeft+tr.offsetWidth>=tr.scrollWidth-10)tr.scrollTo({left:0,behavior:'smooth'});else tr.scrollBy({left:step(),behavior:'smooth'});};var pv=function(){tr.scrollBy({left:-step(),behavior:'smooth'});};c.querySelectorAll('.gct-navbtn').forEach(function(b){b.addEventListener('click',function(){clearInterval(timer);(b.dataset.dir==='next'?nx:pv)();});});var timer=setInterval(nx,5000);c.addEventListener('mouseenter',function(){clearInterval(timer);});c.addEventListener('mouseleave',function(){timer=setInterval(nx,5000);});})();</script>
JS;
    }

    private function formJs(string $errMsg): string
    {
        $err = json_encode($errMsg);
        return <<<JS
<script>(function(){var f=document.currentScript.previousElementSibling;while(f&&!f.classList.contains('gct-form'))f=f.previousElementSibling;if(!f)return;f.addEventListener('submit',function(e){e.preventDefault();var btn=f.querySelector('.gct-submit');var ok=f.querySelector('.gct-success');var orig=btn.textContent;btn.disabled=true;btn.textContent='…';fetch(f.action,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(f)}).then(function(r){return r.json();}).then(function(d){if(d&&d.ok){f.querySelectorAll('.gct-input,.gct-submit,.gct-form-row,.gct-form-head').forEach(function(el){el.style.display='none';});ok.hidden=false;}else{throw new Error((d&&d.error)||'err');}}).catch(function(){btn.disabled=false;btn.textContent=orig;alert({$err});});});})();</script>
JS;
    }
}
