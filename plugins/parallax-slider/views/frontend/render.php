<?php
/**
 * Parallax Slider — Frontend Renderer
 * Variables: $slider, $settings, $slidesData (array of slides, each with ['layers'])
 */
$psId      = 'ps-' . (int)$slider['id'] . '-' . substr(md5(uniqid()), 0, 6);
$height    = htmlspecialchars($settings['height'] ?? '560px', ENT_QUOTES);
$jsonSlides = json_encode(array_values($slidesData), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsonCfg    = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<style>#<?= $psId ?>{display:block;width:100%!important;min-width:0;max-width:100%!important}</style>
<div class="ps-slider-wrap" id="<?= $psId ?>" style="position:relative;width:100%;min-width:0;max-width:100%;height:<?= $height ?>;overflow:hidden;background:#1e293b">

    <!-- Slides container -->
    <div class="ps-slides" style="position:absolute;inset:0">
        <?php foreach ($slidesData as $i => $slide):
            $isFirst = ($i === 0);
        ?>
        <div class="ps-slide"
             data-index="<?= $i ?>"
             data-id="<?= (int)$slide['id'] ?>"
             style="position:absolute;inset:0;opacity:<?= $isFirst ? 1 : 0 ?>;transition:opacity .1s;visibility:<?= $isFirst ? 'visible' : 'hidden' ?>">

            <!-- Background -->
            <div class="ps-slide-bg" style="position:absolute;inset:0;will-change:transform;
                <?php if ($slide['bg_type'] === 'color'): ?>
                    background:<?= htmlspecialchars($slide['bg_value'], ENT_QUOTES) ?>;
                <?php elseif ($slide['bg_type'] === 'image'): ?>
                    background:url('<?= htmlspecialchars($slide['bg_value'], ENT_QUOTES) ?>') center/cover no-repeat;
                <?php elseif ($slide['bg_type'] === 'gradient'): ?>
                    background:<?= htmlspecialchars($slide['bg_value'], ENT_QUOTES) ?>;
                <?php endif ?>
            "></div>

            <?php if ($slide['bg_overlay'] > 0): ?>
            <div style="position:absolute;inset:0;background:<?= htmlspecialchars($slide['bg_overlay_color'], ENT_QUOTES) ?>;opacity:<?= (float)$slide['bg_overlay'] ?>;z-index:1;pointer-events:none"></div>
            <?php endif ?>

            <?php if ($slide['link']): ?>
            <a href="<?= htmlspecialchars($slide['link'], ENT_QUOTES) ?>"
               target="<?= $slide['link_target'] === '_blank' ? '_blank' : '_self' ?>"
               style="position:absolute;inset:0;z-index:2;display:block"
               aria-label="<?= htmlspecialchars($slide['title'] ?? '', ENT_QUOTES) ?>"></a>
            <?php endif ?>

            <!-- Layers -->
            <?php foreach ($slide['layers'] as $li => $layer):
                $ls    = is_array($layer['settings']) ? $layer['settings'] : [];
                $lx    = (float)$layer['x'];
                $ly    = (float)$layer['y'];
                $lw    = $layer['width']  !== 'auto' ? 'width:' . htmlspecialchars($layer['width'],  ENT_QUOTES) . ';' : '';
                $lh    = $layer['height'] !== 'auto' ? 'height:' . htmlspecialchars($layer['height'], ENT_QUOTES) . ';' : '';
                $depth = (float)($layer['depth'] ?? 0.5);
            ?>
            <div class="ps-layer"
                 data-layer-idx="<?= $li ?>"
                 data-anim-in="<?= htmlspecialchars($layer['anim_in'] ?? 'fadeIn',  ENT_QUOTES) ?>"
                 data-anim-out="<?= htmlspecialchars($layer['anim_out'] ?? 'fadeOut', ENT_QUOTES) ?>"
                 data-anim-delay="<?= (int)($layer['anim_delay'] ?? 300) ?>"
                 data-anim-duration="<?= (int)($layer['anim_duration'] ?? 700) ?>"
                 data-anim-out-delay="<?= (int)($layer['anim_out_delay'] ?? 0) ?>"
                 data-depth="<?= $depth ?>"
                 style="position:absolute;left:<?= $lx ?>%;top:<?= $ly ?>%;<?= $lw ?><?= $lh ?>z-index:<?= 10 + $li ?>;opacity:0;">
                <?php
                $content = $layer['content'] ?? '';
                switch ($layer['type']):
                    case 'heading':
                    case 'text':
                        $tag  = $layer['type'] === 'heading' ? 'h2' : 'p';
                        $css  = '';
                        if (!empty($ls['font_size']))      $css .= 'font-size:'      . $ls['font_size']     . ';';
                        if (!empty($ls['font_weight']))    $css .= 'font-weight:'    . $ls['font_weight']   . ';';
                        if (!empty($ls['color']))          $css .= 'color:'          . $ls['color']         . ';';
                        if (!empty($ls['text_align']))     $css .= 'text-align:'     . $ls['text_align']    . ';';
                        if (!empty($ls['letter_spacing'])) $css .= 'letter-spacing:' . $ls['letter_spacing']. ';';
                        if (!empty($ls['line_height']))    $css .= 'line-height:'    . $ls['line_height']   . ';';
                        if (!empty($ls['text_shadow']))    $css .= 'text-shadow:'    . $ls['text_shadow']   . ';';
                        echo "<{$tag} style=\"margin:0;{$css}\">" . htmlspecialchars($content, ENT_QUOTES) . "</{$tag}>";
                        break;
                    case 'image':
                        if ($content):
                            $iw = !empty($ls['width'])  ? 'width:'.$ls['width'].';' : 'max-width:100%;';
                            $ir = !empty($ls['radius']) ? 'border-radius:'.$ls['radius'].';' : '';
                            $io = !empty($ls['opacity'])? 'opacity:'.$ls['opacity'].';' : '';
                            echo '<img src="' . htmlspecialchars($content, ENT_QUOTES) . '" alt="" style="display:block;'.$iw.$ir.$io.'" loading="lazy">';
                        endif;
                        break;
                    case 'button':
                        $url    = htmlspecialchars($ls['url'] ?? '#', ENT_QUOTES);
                        $tgt    = ($ls['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';
                        $bcss   = 'display:inline-block;';
                        if (!empty($ls['bg_color']))   $bcss .= 'background:'    . $ls['bg_color']  . ';';
                        if (!empty($ls['color']))      $bcss .= 'color:'         . $ls['color']     . ';';
                        if (!empty($ls['font_size']))  $bcss .= 'font-size:'     . $ls['font_size'] . ';';
                        if (!empty($ls['font_weight']))$bcss .= 'font-weight:'   . $ls['font_weight'].';';
                        if (!empty($ls['padding']))    $bcss .= 'padding:'       . $ls['padding']   . ';';
                        if (!empty($ls['radius']))     $bcss .= 'border-radius:' . $ls['radius']    . ';';
                        if (!empty($ls['border']))     $bcss .= 'border:'        . $ls['border']    . ';';
                        $bcss .= 'text-decoration:none;cursor:pointer;';
                        echo '<a href="'.$url.'"'.$tgt.' style="'.$bcss.'">' . htmlspecialchars($content, ENT_QUOTES) . '</a>';
                        break;
                    case 'html':
                        echo '<div>' . $content . '</div>';
                        break;
                    case 'shape':
                        $scss = '';
                        $sw   = $ls['width']    ?? '120px';
                        $sh   = $ls['height']   ?? '60px';
                        $sbg  = $ls['bg_color'] ?? 'rgba(16,178,124,.7)';
                        $so   = $ls['opacity']  ?? '1';
                        $sr   = ($ls['shape'] ?? 'rect') === 'circle' ? '50%' : ($ls['radius'] ?? '0');
                        $scss = "width:{$sw};height:{$sh};background:{$sbg};opacity:{$so};border-radius:{$sr};";
                        echo '<div style="'.$scss.'"></div>';
                        break;
                endswitch;
                ?>
            </div>
            <?php endforeach ?>
        </div>
        <?php endforeach ?>
    </div>

    <!-- Navigation arrows -->
    <?php if (!empty($settings['show_arrows'])): ?>
    <button class="ps-arrow ps-arrow-prev" aria-label="Previous"
        style="position:absolute;left:16px;top:50%;transform:translateY(-50%);z-index:50;background:rgba(0,0,0,.45);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:18px;cursor:pointer;transition:background .15s;backdrop-filter:blur(4px)"
        onmouseover="this.style.background='rgba(0,0,0,.7)'" onmouseout="this.style.background='rgba(0,0,0,.45)'">
        ‹
    </button>
    <button class="ps-arrow ps-arrow-next" aria-label="Next"
        style="position:absolute;right:16px;top:50%;transform:translateY(-50%);z-index:50;background:rgba(0,0,0,.45);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:18px;cursor:pointer;transition:background .15s;backdrop-filter:blur(4px)"
        onmouseover="this.style.background='rgba(0,0,0,.7)'" onmouseout="this.style.background='rgba(0,0,0,.45)'">
        ›
    </button>
    <?php endif ?>

    <!-- Dots -->
    <?php if (!empty($settings['show_dots']) && count($slidesData) > 1): ?>
    <div class="ps-dots" style="position:absolute;bottom:16px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:50">
        <?php foreach ($slidesData as $i => $_): ?>
        <button class="ps-dot<?= $i===0?' ps-dot-active':'' ?>" data-idx="<?= $i ?>"
            style="width:<?= $i===0?'24px':'8px' ?>;height:8px;border-radius:4px;border:none;background:<?= $i===0?'#10B27C':'rgba(255,255,255,.5)' ?>;cursor:pointer;transition:all .3s;padding:0"></button>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Progress bar -->
    <div class="ps-progress" style="position:absolute;bottom:0;left:0;height:3px;background:var(--accent,#10B27C);width:0;z-index:50;transition:none"></div>
</div>

<style>
/* ── Animation keyframes ──────────────────────────────────────────────────── */
@keyframes psfe-fadeIn{from{opacity:0}to{opacity:1}}
@keyframes psfe-fadeOut{from{opacity:1}to{opacity:0}}
@keyframes psfe-fadeInLeft{from{opacity:0;transform:translateX(-60px)}to{opacity:1;transform:none}}
@keyframes psfe-fadeInRight{from{opacity:0;transform:translateX(60px)}to{opacity:1;transform:none}}
@keyframes psfe-fadeInUp{from{opacity:0;transform:translateY(60px)}to{opacity:1;transform:none}}
@keyframes psfe-fadeInDown{from{opacity:0;transform:translateY(-60px)}to{opacity:1;transform:none}}
@keyframes psfe-fadeOutLeft{from{opacity:1;transform:none}to{opacity:0;transform:translateX(-60px)}}
@keyframes psfe-fadeOutRight{from{opacity:1;transform:none}to{opacity:0;transform:translateX(60px)}}
@keyframes psfe-fadeOutUp{from{opacity:1;transform:none}to{opacity:0;transform:translateY(-60px)}}
@keyframes psfe-fadeOutDown{from{opacity:1;transform:none}to{opacity:0;transform:translateY(60px)}}
@keyframes psfe-zoomIn{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:none}}
@keyframes psfe-zoomOut{from{opacity:1;transform:none}to{opacity:0;transform:scale(.5)}}
@keyframes psfe-zoomInLeft{from{opacity:0;transform:translateX(-80px) scale(.6)}to{opacity:1;transform:none}}
@keyframes psfe-zoomInRight{from{opacity:0;transform:translateX(80px) scale(.6)}to{opacity:1;transform:none}}
@keyframes psfe-bounceIn{0%{opacity:0;transform:scale(.3)}50%{transform:scale(1.08)}70%{transform:scale(.97)}100%{opacity:1;transform:none}}
@keyframes psfe-bounceOut{0%{opacity:1;transform:none}40%{transform:scale(1.08)}100%{opacity:0;transform:scale(.3)}}
@keyframes psfe-bounceInLeft{0%{opacity:0;transform:translateX(-120px)}60%{transform:translateX(10px)}80%{transform:translateX(-5px)}100%{opacity:1;transform:none}}
@keyframes psfe-bounceInRight{0%{opacity:0;transform:translateX(120px)}60%{transform:translateX(-10px)}80%{transform:translateX(5px)}100%{opacity:1;transform:none}}
@keyframes psfe-bounceOutLeft{from{opacity:1;transform:none}to{opacity:0;transform:translateX(-120px)}}
@keyframes psfe-bounceOutRight{from{opacity:1;transform:none}to{opacity:0;transform:translateX(120px)}}
@keyframes psfe-slideInLeft{from{opacity:0;transform:translateX(-100%)}to{opacity:1;transform:none}}
@keyframes psfe-slideInRight{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:none}}
@keyframes psfe-slideInUp{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:none}}
@keyframes psfe-slideInDown{from{opacity:0;transform:translateY(-100%)}to{opacity:1;transform:none}}
@keyframes psfe-slideOutLeft{from{opacity:1;transform:none}to{opacity:0;transform:translateX(-100%)}}
@keyframes psfe-slideOutRight{from{opacity:1;transform:none}to{opacity:0;transform:translateX(100%)}}
@keyframes psfe-slideOutUp{from{opacity:1;transform:none}to{opacity:0;transform:translateY(-100%)}}
@keyframes psfe-slideOutDown{from{opacity:1;transform:none}to{opacity:0;transform:translateY(100%)}}
@keyframes psfe-flipInX{from{opacity:0;transform:rotateX(90deg)}to{opacity:1;transform:none}}
@keyframes psfe-flipInY{from{opacity:0;transform:rotateY(90deg)}to{opacity:1;transform:none}}
@keyframes psfe-flipOutX{from{opacity:1;transform:none}to{opacity:0;transform:rotateX(90deg)}}
@keyframes psfe-flipOutY{from{opacity:1;transform:none}to{opacity:0;transform:rotateY(90deg)}}
@keyframes psfe-rotateIn{from{opacity:0;transform:rotate(-180deg) scale(.5)}to{opacity:1;transform:none}}
@keyframes psfe-rotateOut{from{opacity:1;transform:none}to{opacity:0;transform:rotate(180deg) scale(.5)}}
@keyframes psfe-kenburns{from{transform:scale(1) translate(0,0)}to{transform:scale(1.12) translate(-2%,-1%)}}
</style>

<script>
(function(){
var root     = document.getElementById('<?= $psId ?>');
if(!root) return;
var slides   = <?= $jsonSlides ?>;
var cfg      = <?= $jsonCfg ?>;
var total    = slides.length;
if(!total) return;

var current      = 0;
var transitioning= false;
var timer        = null;
var mouseX       = 0, mouseY = 0;
var slideEls = root.querySelectorAll('.ps-slide');
var dots     = root.querySelectorAll('.ps-dot');
var progress = root.querySelector('.ps-progress');

// ── Transition definitions ────────────────────────────────────────────────────
var transitions = {
    fade: function(outEl, inEl, speed, cb){
        outEl.style.transition = 'opacity '+speed+'ms ease';
        inEl.style.transition  = 'opacity '+speed+'ms ease';
        inEl.style.opacity = '0'; inEl.style.visibility = 'visible';
        requestAnimationFrame(function(){
            outEl.style.opacity = '0';
            inEl.style.opacity  = '1';
            setTimeout(function(){ outEl.style.visibility='hidden'; cb(); }, speed);
        });
    },
    slide: function(outEl, inEl, speed, cb, dir){
        var d = (dir >= 0) ? 1 : -1;
        inEl.style.transform = 'translateX('+(d*100)+'%)';
        inEl.style.visibility = 'visible'; inEl.style.opacity = '1';
        inEl.style.transition = 'transform '+speed+'ms cubic-bezier(.4,0,.2,1)';
        outEl.style.transition= 'transform '+speed+'ms cubic-bezier(.4,0,.2,1)';
        requestAnimationFrame(function(){
            inEl.style.transform  = 'translateX(0)';
            outEl.style.transform = 'translateX('+(-d*100)+'%)';
            setTimeout(function(){ outEl.style.visibility='hidden'; outEl.style.transform=''; inEl.style.transform=''; cb(); }, speed);
        });
    },
    zoom: function(outEl, inEl, speed, cb){
        inEl.style.transform = 'scale(1.15)'; inEl.style.opacity = '0';
        inEl.style.transition = 'opacity '+speed+'ms ease,transform '+speed+'ms ease';
        outEl.style.transition= 'opacity '+speed+'ms ease,transform '+speed+'ms ease';
        inEl.style.visibility = 'visible';
        requestAnimationFrame(function(){
            inEl.style.opacity = '1'; inEl.style.transform = 'scale(1)';
            outEl.style.opacity = '0'; outEl.style.transform = 'scale(.88)';
            setTimeout(function(){ outEl.style.visibility='hidden'; outEl.style.transform=''; cb(); }, speed);
        });
    },
    flip: function(outEl, inEl, speed, cb){
        var half = speed/2;
        outEl.style.transition = 'transform '+half+'ms ease';
        outEl.style.transform  = 'rotateY(90deg)';
        setTimeout(function(){
            outEl.style.visibility = 'hidden'; outEl.style.transform = '';
            inEl.style.transform = 'rotateY(-90deg)';
            inEl.style.visibility = 'visible'; inEl.style.opacity = '1';
            inEl.style.transition = 'transform '+half+'ms ease';
            requestAnimationFrame(function(){ inEl.style.transform = 'rotateY(0)'; });
            setTimeout(cb, half);
        }, half);
    },
    cube: function(outEl, inEl, speed, cb){
        // Simplified cube — rotate out/in
        var half = speed/2;
        outEl.style.transition = 'transform '+half+'ms ease,opacity '+half+'ms ease';
        outEl.style.transform  = 'rotateY(-90deg) scale(.9)';
        outEl.style.opacity    = '0';
        setTimeout(function(){
            outEl.style.visibility = 'hidden'; outEl.style.transform = ''; outEl.style.opacity = '';
            inEl.style.transform = 'rotateY(90deg) scale(.9)';
            inEl.style.opacity   = '0';
            inEl.style.visibility= 'visible';
            inEl.style.transition= 'transform '+half+'ms ease,opacity '+half+'ms ease';
            requestAnimationFrame(function(){ inEl.style.transform='rotateY(0) scale(1)'; inEl.style.opacity='1'; });
            setTimeout(cb, half);
        }, half);
    },
};

// ── Go to slide ───────────────────────────────────────────────────────────────
function goTo(idx, force) {
    if(transitioning && !force) return;
    if(idx === current && !force) return;
    var prevIdx = current;
    var rawDir  = idx - prevIdx;          // direction before clamping
    if(idx < 0) idx = cfg.loop ? total-1 : 0;
    if(idx >= total) idx = cfg.loop ? 0 : total-1;

    var outEl = slideEls[current];
    var inEl  = slideEls[idx];
    var speed = cfg.transition_speed || 900;
    var fn    = transitions[cfg.transition] || transitions.fade;

    transitioning = true;
    animLayersOut(outEl);
    outEl.style.transition = '';
    inEl.style.transition  = '';

    current = idx;
    fn(outEl, inEl, speed, function(){
        transitioning = false;
        outEl.style.opacity    = '0';
        outEl.style.visibility = 'hidden';
        outEl.style.transition = '';
        outEl.style.transform  = '';
        inEl.style.transition  = '';
        inEl.style.transform   = '';
        var bg = inEl.querySelector('.ps-slide-bg');
        var slideData = slides[idx];
        if(bg && slideData && slideData.kenburns) {
            bg.style.animation = 'none';
            requestAnimationFrame(function(){
                bg.style.animation = 'psfe-kenburns '+(slideData.duration||6000)+'ms ease forwards';
            });
        } else if(bg) {
            bg.style.animation = 'none';
        }
        animLayersIn(inEl);
        updateDots();
    }, rawDir);

    resetProgress(slides[idx]?.duration || cfg.autoplay_speed);
}

// ── Layer animation ───────────────────────────────────────────────────────────
function animLayersIn(slideEl) {
    slideEl.querySelectorAll('.ps-layer').forEach(function(el){
        var animIn   = el.dataset.animIn    || 'fadeIn';
        var delay    = parseInt(el.dataset.animDelay    || 300);
        var duration = parseInt(el.dataset.animDuration || 700);
        el.style.opacity   = '0';
        el.style.animation = 'none';
        if(animIn === 'none') {
            setTimeout(function(){ el.style.opacity='1'; el.style.transform=''; }, delay);
            return;
        }
        setTimeout(function(){
            el.style.animation = 'psfe-'+animIn+' '+duration+'ms ease forwards';
            // After animation completes, clear it so parallax can freely update transform
            setTimeout(function(){
                el.style.animation = 'none';
                el.style.opacity   = '1';
                el.style.transform = '';
            }, duration + 50);
        }, delay);
    });
}

function animLayersOut(slideEl) {
    slideEl.querySelectorAll('.ps-layer').forEach(function(el){
        var animOut   = el.dataset.animOut    || 'fadeOut';
        var delay     = parseInt(el.dataset.animOutDelay || 0);
        var duration  = parseInt(el.dataset.animDuration || 700);
        if(animOut === 'none') { el.style.opacity='0'; return; }
        setTimeout(function(){
            el.style.animation = 'psfe-'+animOut+' '+duration+'ms ease forwards';
        }, delay);
    });
}

// ── Mouse parallax ────────────────────────────────────────────────────────────
if(cfg.mouse_parallax) {
    root.addEventListener('mousemove', function(e){
        var rect = root.getBoundingClientRect();
        mouseX = (e.clientX - rect.left - rect.width/2)  / rect.width;
        mouseY = (e.clientY - rect.top  - rect.height/2) / rect.height;
        applyParallax();
    });
    root.addEventListener('mouseleave', function(){
        mouseX = 0; mouseY = 0;
        applyParallax();
    });
}

function applyParallax() {
    var strength = cfg.mouse_strength || 25;
    var curSlide = slideEls[current];
    if(!curSlide) return;

    // Parallax on bg
    var bg = curSlide.querySelector('.ps-slide-bg');
    if(bg) {
        var bx = mouseX * strength * 0.3;
        var by = mouseY * strength * 0.3;
        bg.style.transform = 'translate('+bx+'px,'+by+'px) scale(1.05)';
    }

    // Parallax on layers (only when not mid-animation)
    curSlide.querySelectorAll('.ps-layer').forEach(function(el){
        if(el.style.animation && el.style.animation !== 'none') return;
        var depth = parseFloat(el.dataset.depth || 0.5);
        var dx = -mouseX * strength * depth;
        var dy = -mouseY * strength * depth;
        el.style.transform = 'translate('+dx+'px,'+dy+'px)';
    });
}

// ── Dots ──────────────────────────────────────────────────────────────────────
function updateDots() {
    dots.forEach(function(d, i){
        d.classList.toggle('ps-dot-active', i===current);
        d.style.background = i===current ? '#10B27C' : 'rgba(255,255,255,.5)';
        d.style.width      = i===current ? '24px' : '8px';
    });
}

dots.forEach(function(d){
    d.addEventListener('click', function(){
        goTo(parseInt(this.dataset.idx));
        resetAutoplay();
    });
});

// ── Progress ──────────────────────────────────────────────────────────────────
function resetProgress(duration) {
    if(!progress) return;
    progress.style.transition = 'none';
    progress.style.width = '0';
    requestAnimationFrame(function(){
        progress.style.transition = 'width '+duration+'ms linear';
        progress.style.width = '100%';
    });
}

// ── Autoplay ──────────────────────────────────────────────────────────────────
function startAutoplay() {
    if(!cfg.autoplay || total < 2) return;
    clearTimeout(timer);
    var duration = slides[current]?.duration || cfg.autoplay_speed;
    timer = setTimeout(function(){ goTo(current+1); startAutoplay(); }, duration);
}

function resetAutoplay() {
    clearTimeout(timer);
    startAutoplay();
}

if(cfg.pause_on_hover) {
    root.addEventListener('mouseenter', function(){ clearTimeout(timer); });
    root.addEventListener('mouseleave', function(){ startAutoplay(); });
}

// ── Arrows ────────────────────────────────────────────────────────────────────
var prev = root.querySelector('.ps-arrow-prev');
var next = root.querySelector('.ps-arrow-next');
if(prev) prev.addEventListener('click', function(){ goTo(current-1); resetAutoplay(); });
if(next) next.addEventListener('click', function(){ goTo(current+1); resetAutoplay(); });

// ── Touch / swipe ─────────────────────────────────────────────────────────────
var touchStartX = 0;
root.addEventListener('touchstart', function(e){ touchStartX = e.touches[0].clientX; },{passive:true});
root.addEventListener('touchend', function(e){
    var dx = e.changedTouches[0].clientX - touchStartX;
    if(Math.abs(dx) > 50) { goTo(current + (dx<0?1:-1)); resetAutoplay(); }
},{passive:true});

// ── Keyboard ──────────────────────────────────────────────────────────────────
root.setAttribute('tabindex','0');
root.addEventListener('keydown', function(e){
    if(e.key==='ArrowLeft')  { goTo(current-1); resetAutoplay(); }
    if(e.key==='ArrowRight') { goTo(current+1); resetAutoplay(); }
});

// ── Init ──────────────────────────────────────────────────────────────────────
(function init(){
    if(total === 0) return;
    // Show first slide layers
    animLayersIn(slideEls[0]);
    // Ken Burns on first slide if enabled
    var firstBg = slideEls[0].querySelector('.ps-slide-bg');
    if(firstBg && slides[0] && slides[0].kenburns) {
        firstBg.style.animation = 'psfe-kenburns '+(slides[0].duration||6000)+'ms ease forwards';
    }
    startAutoplay();
    resetProgress(slides[0]?.duration || cfg.autoplay_speed);
    updateDots();
})();

})();
</script>
