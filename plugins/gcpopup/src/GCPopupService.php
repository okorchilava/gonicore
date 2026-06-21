<?php
declare(strict_types=1);

namespace GCPopup;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

final class GCPopupService
{
    private static ?self $instance = null;
    private static bool  $assetsInjected = false;

    /** IDs already rendered this request (prevents double-render). */
    private static array $rendered = [];

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    public static function register(self $s): void { self::$instance = $s; }
    public static function getInstance(): ?self     { return self::$instance; }
    public static function resetAssets(): void      { self::$assetsInjected = false; self::$rendered = []; }

    // ── Popup CRUD ──────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function allPopups(): array
    {
        return $this->qb->table('gcpopup_popups')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'DESC')
            ->get();
    }

    /** @return list<array<string,mixed>> */
    public function activePopups(): array
    {
        return $this->qb->table('gcpopup_popups')
            ->where('active', '=', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();
    }

    public function popup(int $id): ?array
    {
        return $this->qb->table('gcpopup_popups')->where('id', '=', $id)->first() ?: null;
    }

    public function savePopup(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('gcpopup_popups')->where('id', '=', $id)->update($data);
            return $id;
        }
        $this->qb->table('gcpopup_popups')->insert($data);
        return (int) $this->conn->pdo()->lastInsertId();
    }

    public function deletePopup(int $id): void
    {
        $this->qb->table('gcpopup_popups')->where('id', '=', $id)->delete();
    }

    public function togglePopup(int $id): void
    {
        $row = $this->popup($id);
        if (!$row) return;
        $this->qb->table('gcpopup_popups')
            ->where('id', '=', $id)
            ->update(['active' => (int)$row['active'] === 1 ? 0 : 1]);
    }

    // ── Feature items CRUD ──────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function itemsByPopup(int $popupId): array
    {
        return $this->qb->table('gcpopup_items')
            ->where('popup_id', '=', $popupId)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Replace all items for a popup.
     * @param list<array{icon:string,text:string}> $items
     */
    public function replaceItems(int $popupId, array $items): void
    {
        $this->qb->table('gcpopup_items')->where('popup_id', '=', $popupId)->delete();
        foreach ($items as $i => $item) {
            $icon = trim((string)($item['icon'] ?? ''));
            $text = trim((string)($item['text'] ?? ''));
            if ($text === '') continue;
            $this->qb->table('gcpopup_items')->insert([
                'popup_id'   => $popupId,
                'icon'       => $icon,
                'text'       => $text,
                'sort_order' => $i,
            ]);
        }
    }

    // ── Rendering ───────────────────────────────────────────────────────────────

    /**
     * Render a popup's full modal HTML.
     * Returns empty string if the popup was already rendered in this request.
     */
    public function renderPopup(int $id): string
    {
        if (in_array($id, self::$rendered, true)) return '';
        $popup = $this->popup($id);
        if (!$popup) return '';
        self::$rendered[] = $id;

        $items = $this->itemsByPopup($id);
        return $this->buildModal($popup, $items);
    }

    /**
     * Render shortcode: popup modal + trigger button.
     * The btn attribute overrides the button text.
     */
    public function renderShortcode(int $id, array $attrs = []): string
    {
        $popup = $this->popup($id);
        if (!$popup || !(int)$popup['active']) return '';

        $modalHtml = $this->renderPopup($id);
        $assets    = $this->renderAssets();

        $btnLabel = isset($attrs['btn']) ? htmlspecialchars((string)$attrs['btn'], ENT_QUOTES)
                  : (htmlspecialchars((string)($popup['btn_text'] ?: 'გახსნა'), ENT_QUOTES));
        $btnClass = htmlspecialchars((string)($attrs['class'] ?? ''), ENT_QUOTES);

        $btn = '<button type="button" class="gcp-trigger-btn ' . $btnClass . '" '
             . 'onclick="gcpShow(' . $id . ')" '
             . 'style="background:' . htmlspecialchars((string)$popup['btn_color'], ENT_QUOTES) . ';'
             . 'color:' . htmlspecialchars((string)$popup['btn_text_color'], ENT_QUOTES) . '">'
             . $btnLabel
             . '</button>';

        return $modalHtml . $btn . $assets;
    }

    /**
     * Render all active popups for auto-injection before </body>.
     * Skips popups already rendered (e.g. via shortcode).
     */
    public function renderAll(): string
    {
        $popups = $this->activePopups();
        if (empty($popups)) return '';

        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        // Strip query string for matching
        $uriPath = strtok($currentUri, '?') ?: $currentUri;

        $html = '';
        foreach ($popups as $p) {
            $targetPages = trim((string)$p['target_pages']);
            if ($targetPages !== '') {
                $patterns = array_filter(array_map('trim', explode("\n", $targetPages)));
                $match    = false;
                foreach ($patterns as $pat) {
                    if (!$pat) continue;
                    if (fnmatch($pat, $uriPath) || str_contains($uriPath, $pat)) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) continue;
            }

            $html .= $this->renderPopup((int)$p['id']);
        }

        if ($html || !self::$assetsInjected) {
            $html .= $this->renderAssets();
        }

        return $html;
    }

    // ── Private builders ────────────────────────────────────────────────────────

    /** @param list<array<string,mixed>> $items */
    private function buildModal(array $p, array $items): string
    {
        $id      = (int)$p['id'];
        $width   = max(300, min(700, (int)$p['popup_width']));
        $opacity = max(0, min(90, (int)$p['overlay_opacity']));
        $anim    = in_array($p['animation'], ['slide','fade','zoom'], true) ? $p['animation'] : 'slide';
        $trigger = $p['trigger_type'];
        $delay   = (int)$p['trigger_delay'];
        $scroll  = (int)$p['trigger_scroll'];
        $freq    = $p['show_frequency'];
        $imgBg   = htmlspecialchars((string)$p['image_bg_color'],  ENT_QUOTES);
        $btnColor    = htmlspecialchars((string)$p['btn_color'],       ENT_QUOTES);
        $btnTxtColor = htmlspecialchars((string)$p['btn_text_color'],  ENT_QUOTES);
        $bdgColor    = htmlspecialchars((string)$p['badge_color'],     ENT_QUOTES);
        $bdgTxt      = htmlspecialchars((string)$p['badge_text_color'],ENT_QUOTES);
        $oa          = round($opacity / 100, 2);
        $ovClose     = (int)$p['close_on_overlay'] ? " data-gcp-ov-close=\"{$id}\"" : '';
        $xBtn        = '';

        if ((int)$p['show_close_btn']) {
            $xBtn = '<button class="gcp-x" onclick="gcpClose(' . $id . ')" aria-label="დახურვა">&#10005;</button>';
        }

        // Image section
        $imgSection = '';
        $imgUrl = trim((string)$p['image_url']);
        if ($imgUrl) {
            $imgAlt = htmlspecialchars((string)$p['image_alt'], ENT_QUOTES);
            $imgSrc = htmlspecialchars($imgUrl, ENT_QUOTES);
            $imgSection = '
            <div class="gcp-img-wrap" style="background:' . $imgBg . '">
                <span class="gcp-d1"></span>
                <span class="gcp-d2"></span>
                <span class="gcp-d3"></span>
                <span class="gcp-d4"></span>
                <img class="gcp-img" src="' . $imgSrc . '" alt="' . $imgAlt . '" loading="lazy">
            </div>';
        } else {
            // Show decorative circle only
            $imgSection = '
            <div class="gcp-img-wrap gcp-no-img" style="background:' . $imgBg . '">
                <span class="gcp-d1"></span>
                <span class="gcp-d2"></span>
                <span class="gcp-d3"></span>
                <span class="gcp-d4"></span>
            </div>';
        }

        // Badge
        $badgeHtml = '';
        $badgeText = trim((string)$p['badge_text']);
        if ($badgeText) {
            $badgeHtml = '
                <div class="gcp-badge" style="background:' . $bdgColor . ';color:' . $bdgTxt . '">'
                . htmlspecialchars($badgeText, ENT_QUOTES | ENT_HTML5)
                . '</div>';
        }

        // Title
        $titleHtml = '';
        $titleText = trim((string)$p['title']);
        if ($titleText) {
            $titleHtml = '<h3 class="gcp-title">' . htmlspecialchars($titleText, ENT_QUOTES | ENT_HTML5) . '</h3>';
        }

        // Subtitle
        $subtitleHtml = '';
        $subtitleText = trim((string)$p['subtitle']);
        if ($subtitleText) {
            $subtitleHtml = '<p class="gcp-subtitle">' . htmlspecialchars($subtitleText, ENT_QUOTES | ENT_HTML5) . '</p>';
        }

        // Feature items
        $itemsHtml = '';
        if (!empty($items)) {
            $lis = '';
            foreach ($items as $item) {
                $icon = trim((string)$item['icon']);
                $text = trim((string)$item['text']);
                if (!$text) continue;
                $iconHtml = $icon ? '<span class="gcp-fi">' . htmlspecialchars($icon, ENT_QUOTES | ENT_HTML5) . '</span>' : '';
                $lis .= '<li>' . $iconHtml . '<span>' . htmlspecialchars($text, ENT_QUOTES | ENT_HTML5) . '</span></li>';
            }
            if ($lis) {
                $itemsHtml = '<ul class="gcp-features">' . $lis . '</ul>';
            }
        }

        // CTA button
        $btnHtml = '';
        $btnText = trim((string)$p['btn_text']);
        if ($btnText) {
            $btnUrl  = htmlspecialchars(trim((string)$p['btn_url']), ENT_QUOTES);
            $btnHtml = '<a href="' . $btnUrl . '" class="gcp-btn" style="background:' . $btnColor . ';color:' . $btnTxtColor . '">'
                     . htmlspecialchars($btnText, ENT_QUOTES | ENT_HTML5)
                     . '</a>';
        }

        // Footer
        $footerHtml = '';
        $footerText = trim((string)$p['footer_text']);
        if ($footerText) {
            $fLinkText = trim((string)$p['footer_link_text']);
            $fLinkUrl  = trim((string)$p['footer_link_url']);
            $fLink     = '';
            if ($fLinkText && $fLinkUrl) {
                $fLink = ' <a href="' . htmlspecialchars($fLinkUrl, ENT_QUOTES) . '">'
                       . htmlspecialchars($fLinkText, ENT_QUOTES | ENT_HTML5)
                       . '</a>';
            }
            $footerHtml = '<p class="gcp-footer">'
                        . htmlspecialchars($footerText, ENT_QUOTES | ENT_HTML5)
                        . $fLink
                        . '</p>';
        }

        return '
<div class="gcp-wrap gcp-anim-' . $anim . '" id="gcp-' . $id . '"
     data-trigger="' . htmlspecialchars($trigger, ENT_QUOTES) . '"
     data-delay="' . $delay . '"
     data-scroll="' . $scroll . '"
     data-freq="' . htmlspecialchars($freq, ENT_QUOTES) . '"
     role="dialog" aria-modal="true">
  <div class="gcp-overlay" style="background:rgba(0,0,0,' . $oa . ')"' . $ovClose . '></div>
  <div class="gcp-box" style="max-width:' . $width . 'px">
    ' . $xBtn . '
    ' . $imgSection . '
    <div class="gcp-body">
      ' . $titleHtml . '
      ' . $subtitleHtml . '
      ' . $badgeHtml . '
      ' . $itemsHtml . '
      ' . $btnHtml . '
      ' . $footerHtml . '
    </div>
  </div>
</div>
';
    }

    private function renderAssets(): string
    {
        if (self::$assetsInjected) return '';
        self::$assetsInjected = true;

        return '
<style id="gcp-styles">
.gcp-wrap{position:fixed;inset:0;z-index:9990;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .25s ease}
.gcp-wrap.gcp-open{opacity:1;pointer-events:all}
.gcp-overlay{position:absolute;inset:0;cursor:pointer}
.gcp-box{position:relative;background:#fff;border-radius:22px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden;transition:transform .35s cubic-bezier(.34,1.4,.64,1),opacity .25s ease}

/* Animations */
.gcp-anim-slide .gcp-box{transform:translateY(36px) scale(.98)}
.gcp-anim-slide.gcp-open .gcp-box{transform:translateY(0) scale(1)}
.gcp-anim-zoom .gcp-box{transform:scale(.78);opacity:0}
.gcp-anim-zoom.gcp-open .gcp-box{transform:scale(1);opacity:1}
.gcp-anim-fade .gcp-box{transform:none;transition:opacity .25s ease}
.gcp-anim-fade .gcp-box{opacity:0}
.gcp-anim-fade.gcp-open .gcp-box{opacity:1}

/* Close button */
.gcp-x{position:absolute;top:14px;right:14px;z-index:2;width:30px;height:30px;border:none;border-radius:50%;background:rgba(0,0,0,.06);color:#555;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s,transform .15s}
.gcp-x:hover{background:rgba(0,0,0,.13);transform:scale(1.1)}

/* Image area */
.gcp-img-wrap{position:relative;width:100%;height:192px;display:flex;align-items:center;justify-content:center;overflow:hidden}
.gcp-no-img{height:60px}
.gcp-img-wrap::before{content:"";position:absolute;width:144px;height:144px;border-radius:50%;background:rgba(255,255,255,.45);top:50%;left:50%;transform:translate(-50%,-50%)}
.gcp-no-img::before{display:none}
.gcp-img{position:relative;z-index:1;max-height:160px;max-width:75%;object-fit:contain;filter:drop-shadow(0 8px 16px rgba(0,0,0,.12))}

/* Decorative dots */
.gcp-d1,.gcp-d2,.gcp-d3,.gcp-d4{position:absolute;border-radius:50%;pointer-events:none}
.gcp-d1{width:10px;height:10px;top:16%;left:13%;background:#ef4444;opacity:.85}
.gcp-d2{width:8px;height:8px;top:12%;right:16%;background:#8b5cf6;opacity:.85}
.gcp-d3{width:7px;height:7px;bottom:18%;left:18%;background:#f97316;opacity:.85}
.gcp-d4{width:9px;height:9px;bottom:14%;right:12%;background:transparent;border:2.5px solid #ec4899;opacity:.85}

/* Body */
.gcp-body{padding:20px 24px 26px;text-align:center}
.gcp-title{font-size:19px;font-weight:800;color:#111827;margin:0 0 10px;line-height:1.35}
.gcp-subtitle{font-size:13.5px;color:#6b7280;margin:0 0 12px;line-height:1.5}

/* Badge */
.gcp-badge{display:inline-block;border-radius:8px;padding:9px 16px;font-size:13.5px;font-weight:600;margin-bottom:14px;width:100%;box-sizing:border-box}

/* Feature list */
.gcp-features{list-style:none;margin:0 0 18px;padding:0;text-align:left}
.gcp-features li{display:flex;align-items:flex-start;gap:10px;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:13.5px;color:#374151;line-height:1.45}
.gcp-features li:last-child{border-bottom:none}
.gcp-fi{font-size:1.1rem;line-height:1.2;flex-shrink:0;margin-top:1px}

/* CTA button */
.gcp-btn{display:block;width:100%;padding:14px 20px;border-radius:12px;font-size:15px;font-weight:700;text-align:center;text-decoration:none;margin-bottom:12px;box-sizing:border-box;transition:opacity .15s,transform .12s}
.gcp-btn:hover{opacity:.88;transform:translateY(-1px)}

/* Footer */
.gcp-footer{font-size:13px;color:#9ca3af;margin:0}
.gcp-footer a{color:#6b7280;font-weight:600;text-decoration:underline}
.gcp-footer a:hover{color:#374151}

/* Shortcode trigger button */
.gcp-trigger-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 22px;border-radius:10px;border:none;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .15s}
.gcp-trigger-btn:hover{opacity:.85}

@media(max-width:480px){
  .gcp-body{padding:16px 18px 22px}
  .gcp-title{font-size:17px}
  .gcp-img-wrap{height:160px}
  .gcp-img{max-height:130px}
}
</style>
<script id="gcp-js">
(function(){
  window.gcpShow=function(id){
    var el=document.getElementById("gcp-"+id);
    if(!el)return;
    el.classList.add("gcp-open");
    document.body.style.overflow="hidden";
  };
  window.gcpClose=function(id){
    var el=document.getElementById("gcp-"+id);
    if(!el)return;
    el.classList.remove("gcp-open");
    document.body.style.overflow="";
  };
  // Overlay click handlers
  document.addEventListener("click",function(e){
    var ov=e.target.closest("[data-gcp-ov-close]");
    if(ov)gcpClose(ov.getAttribute("data-gcp-ov-close"));
  });
  // ESC key
  document.addEventListener("keydown",function(e){
    if(e.key!=="Escape")return;
    document.querySelectorAll(".gcp-wrap.gcp-open").forEach(function(el){
      el.classList.remove("gcp-open");
      document.body.style.overflow="";
    });
  });
  // Auto-triggers
  function initTriggers(){
    document.querySelectorAll(".gcp-wrap[data-trigger]").forEach(function(el){
      var rawId  =el.id.replace("gcp-","");
      var id     =parseInt(rawId,10);
      var trigger=el.dataset.trigger;
      var delay  =parseInt(el.dataset.delay||"0",10);
      var scroll =parseInt(el.dataset.scroll||"50",10);
      var freq   =el.dataset.freq||"once_session";
      var key    ="gcp_"+el.id;

      function canShow(){
        if(freq==="always")return true;
        if(freq==="once_session")return!sessionStorage.getItem(key);
        if(freq==="once_day"){var ts=localStorage.getItem(key);return!ts||(Date.now()-Number(ts))>86400000;}
        if(freq==="once_ever")return!localStorage.getItem(key);
        return true;
      }
      function markShown(){
        if(freq==="once_session")sessionStorage.setItem(key,"1");
        else if(freq==="once_day")localStorage.setItem(key,String(Date.now()));
        else if(freq==="once_ever")localStorage.setItem(key,"1");
      }
      function doShow(){
        if(!canShow())return;
        gcpShow(id);
        markShown();
      }

      if(trigger==="manual")return;

      if(trigger==="load"){
        if(delay<=0)doShow();
        else setTimeout(doShow,delay*1000);
      }else if(trigger==="scroll"){
        var scrollDone=false;
        window.addEventListener("scroll",function(){
          if(scrollDone)return;
          var h=document.documentElement.scrollHeight-window.innerHeight;
          if(h<=0)return;
          if((window.scrollY/h*100)>=scroll){scrollDone=true;doShow();}
        },{passive:true});
      }else if(trigger==="exit"){
        var exitDone=false;
        document.addEventListener("mouseleave",function(e){
          if(exitDone||e.clientY>10)return;
          exitDone=true;doShow();
        });
      }
    });
  }
  if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",initTriggers);
  else initTriggers();
})();
</script>';
    }
}
