<?php
/**
 * Parallax Slider — Full-screen Editor
 * Variables: $slider, $slides, $settings, $base, $success, $animsIn, $animsOut
 */
$pageTitle = 'Edit: ' . e($slider['name']);
$activeNav = 'sliders';
$topbarActions = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Slider Editor — <?= e($slider['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --panel:#1e293b;--panel-d:#0f172a;--panel-b:rgba(255,255,255,.07);
  --accent:#10B27C;--accent-d:#0e9c6c;--danger:#ef4444;
  --text:#0f172a;--muted:#64748b;--border:#e2e8f0;--bg:#f1f5f9;--surface:#fff;
  --top:52px;--left:260px;--right:320px;
}
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:var(--bg);overflow:hidden;height:100vh;display:flex;flex-direction:column}
a{text-decoration:none}
input,select,textarea{font-family:inherit}

/* Topbar */
#ps-topbar{height:var(--top);background:var(--panel-d);border-bottom:1px solid var(--panel-b);display:flex;align-items:center;padding:0 16px;gap:12px;flex-shrink:0;z-index:200}
#ps-topbar .logo{color:#fff;font-weight:800;font-size:15px;letter-spacing:-.3px}
#ps-topbar .logo span{color:var(--accent)}
#ps-slider-name{background:transparent;border:1px solid transparent;color:#fff;font-size:14px;font-weight:600;padding:5px 8px;border-radius:6px;outline:none;cursor:pointer;transition:border-color .15s;min-width:160px}
#ps-slider-name:hover,#ps-slider-name:focus{border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.06)}
.ps-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s}
.ps-btn-primary{background:var(--accent);color:#fff}.ps-btn-primary:hover{background:var(--accent-d)}
.ps-btn-ghost{background:rgba(255,255,255,.08);color:#cbd5e1;border:1px solid rgba(255,255,255,.12)}.ps-btn-ghost:hover{background:rgba(255,255,255,.14);color:#fff}
.ps-btn-danger{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.2)}
#ps-save-status{font-size:12px;color:#64748b}
.spacer{flex:1}

/* Main */
#ps-main{flex:1;display:flex;overflow:hidden}

/* Left — slide list */
#ps-left{width:var(--left);background:var(--panel);border-right:1px solid var(--panel-b);display:flex;flex-direction:column;overflow:hidden;flex-shrink:0}
#ps-left-header{padding:12px 14px;border-bottom:1px solid var(--panel-b);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
#ps-left-header span{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b}
#ps-left-tabs{display:flex;border-bottom:1px solid var(--panel-b)}
.ps-ltab{flex:1;padding:9px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b;cursor:pointer;border-bottom:2px solid transparent}
.ps-ltab.active{color:var(--accent);border-color:var(--accent)}
#ps-slides-list{flex:1;overflow-y:auto;padding:10px}
#ps-slides-list::-webkit-scrollbar{width:4px}#ps-slides-list::-webkit-scrollbar-thumb{background:#2d3748}
.ps-slide-thumb{
  border:2px solid rgba(255,255,255,.08);border-radius:8px;overflow:hidden;cursor:pointer;
  transition:border-color .15s;margin-bottom:8px;position:relative;
}
.ps-slide-thumb:hover{border-color:rgba(16,178,124,.4)}
.ps-slide-thumb.active{border-color:var(--accent)}
.ps-slide-thumb-inner{height:80px;display:flex;align-items:center;justify-content:center;font-size:24px;background:#1e293b;position:relative;overflow:hidden}
.ps-slide-thumb-inner img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.ps-slide-num{position:absolute;top:5px;left:6px;background:rgba(0,0,0,.6);color:#fff;font-size:10px;padding:2px 6px;border-radius:10px}
.ps-slide-title{font-size:11.5px;color:#94a3b8;padding:6px 8px;background:#141e2e;border-top:1px solid rgba(255,255,255,.05);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ps-slide-actions{position:absolute;top:4px;right:4px;display:none;gap:2px}
.ps-slide-thumb:hover .ps-slide-actions{display:flex}
.ps-slide-act{background:rgba(0,0,0,.65);border:none;color:#fff;cursor:pointer;font-size:11px;padding:3px 6px;border-radius:4px}
#ps-add-slide{width:100%;padding:10px;background:rgba(16,178,124,.12);border:1.5px dashed rgba(16,178,124,.3);border-radius:8px;color:var(--accent);font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;margin-top:4px}
#ps-add-slide:hover{background:rgba(16,178,124,.2);border-color:var(--accent)}

/* Slider settings tab */
#ps-left-settings{flex:1;overflow-y:auto;padding:14px;display:none}

/* Canvas */
#ps-canvas-wrap{flex:1;background:#e2e8f0;overflow:auto;display:flex;align-items:flex-start;justify-content:center;padding:24px;position:relative}
#ps-canvas{background:#1e293b;position:relative;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.3);border-radius:4px;cursor:crosshair}
#ps-canvas-empty{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#475569;text-align:center;pointer-events:none}

/* Layer */
.ps-layer{position:absolute;cursor:move;user-select:none;box-sizing:border-box}
.ps-layer:hover .ps-layer-outline{opacity:1}
.ps-layer.selected .ps-layer-outline{opacity:1;border-color:var(--accent)}
.ps-layer-outline{position:absolute;inset:-2px;border:2px dashed rgba(16,178,124,.4);border-radius:3px;opacity:0;pointer-events:none;transition:opacity .15s}
.ps-layer.selected .ps-layer-outline{border-style:solid}
.ps-resize-handle{position:absolute;bottom:-5px;right:-5px;width:10px;height:10px;background:var(--accent);border-radius:2px;cursor:se-resize}
.ps-layer-toolbar{position:absolute;top:-26px;left:0;display:none;background:var(--accent);border-radius:4px 4px 0 0;padding:2px 6px;gap:4px;white-space:nowrap;z-index:20}
.ps-layer.selected .ps-layer-toolbar{display:flex}
.ps-layer-tbtn{background:none;border:none;color:#fff;cursor:pointer;font-size:11px;padding:2px 4px;border-radius:3px}
.ps-layer-tbtn:hover{background:rgba(0,0,0,.2)}

/* Right panel */
#ps-right{width:var(--right);background:var(--surface);border-left:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;flex-shrink:0}
#ps-right-header{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
#ps-right-title{font-size:13px;font-weight:700}
#ps-right-body{flex:1;overflow-y:auto;padding:14px}
#ps-right-body::-webkit-scrollbar{width:4px}

.ps-field{margin-bottom:12px}
.ps-field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:4px}
.ps-field input,.ps-field select,.ps-field textarea{width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;color:var(--text);outline:none;transition:border-color .15s;background:#fff}
.ps-field input:focus,.ps-field select:focus,.ps-field textarea:focus{border-color:var(--accent)}
.ps-field textarea{min-height:72px;resize:vertical}
.ps-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.ps-sep{height:1px;background:var(--border);margin:12px -14px}
.ps-section-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:10px;padding-top:4px}
.ps-toggle-row{display:flex;align-items:center;justify-content:space-between;padding:5px 0;font-size:13px}
.ps-toggle{position:relative;width:34px;height:19px;cursor:pointer;flex-shrink:0}
.ps-toggle input{opacity:0;width:0;height:0;position:absolute}
.ps-toggle-track{position:absolute;inset:0;background:#cbd5e1;border-radius:20px;transition:background .2s}
.ps-toggle-thumb{position:absolute;top:2px;left:2px;width:15px;height:15px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.ps-toggle input:checked~.ps-toggle-track{background:var(--accent)}
.ps-toggle input:checked~.ps-toggle-thumb{transform:translateX(15px)}

/* Add layer buttons */
#ps-add-layer-bar{padding:10px 12px;border-top:1px solid var(--border);display:flex;flex-wrap:wrap;gap:5px;flex-shrink:0}
.ps-add-layer-btn{padding:5px 10px;background:var(--bg);border:1.5px solid var(--border);border-radius:6px;font-size:12px;font-weight:600;color:var(--muted);cursor:pointer;transition:all .15s}
.ps-add-layer-btn:hover{background:rgba(16,178,124,.08);border-color:var(--accent);color:var(--accent)}

#ps-right-empty{padding:40px 20px;text-align:center;color:var(--muted)}
#ps-right-empty .icon{font-size:32px;margin-bottom:12px}
</style>
</head>
<body>

<!-- TOPBAR -->
<div id="ps-topbar">
    <div class="logo">Parallax<span>Slider</span></div>
    <input id="ps-slider-name" value="<?= e($slider['name']) ?>" onchange="saveName(this.value)">
    <span id="ps-save-status"></span>
    <div class="spacer"></div>
    <a href="<?= e($base) ?>/manage/sliders" class="ps-btn ps-btn-ghost">← Sliders</a>
    <button onclick="previewSlider()" class="ps-btn ps-btn-ghost">↗ Preview</button>
</div>

<!-- MAIN -->
<div id="ps-main">

<!-- LEFT: slide list + slider settings -->
<div id="ps-left">
    <div id="ps-left-tabs">
        <div class="ps-ltab active" onclick="showLeftTab('slides',this)">Slides</div>
        <div class="ps-ltab" onclick="showLeftTab('settings',this)">Slider Settings</div>
    </div>

    <!-- Slides tab -->
    <div id="ps-slides-list">
        <?php foreach ($slides as $i => $sl): ?>
        <div class="ps-slide-thumb <?= $i === 0 ? 'active' : '' ?>"
             id="st-<?= (int)$sl['id'] ?>"
             data-id="<?= (int)$sl['id'] ?>"
             onclick="selectSlide(<?= (int)$sl['id'] ?>)">
            <div class="ps-slide-thumb-inner">
                <?php if ($sl['bg_type'] === 'image' && $sl['bg_value']): ?>
                <img src="<?= e($sl['bg_value']) ?>" alt="">
                <?php elseif ($sl['bg_type'] === 'color'): ?>
                <span style="color:rgba(255,255,255,.2);font-size:20px">◈</span>
                <div style="position:absolute;inset:0;background:<?= e($sl['bg_value']) ?>;opacity:.7"></div>
                <?php else: ?>
                <span style="color:rgba(255,255,255,.2);font-size:20px">◈</span>
                <?php endif ?>
                <span class="ps-slide-num"><?= $i + 1 ?></span>
            </div>
            <div class="ps-slide-title"><?= e($sl['title'] ?: 'Slide ' . ($i + 1)) ?></div>
            <div class="ps-slide-actions">
                <button class="ps-slide-act" onclick="event.stopPropagation();dupSlide(<?= (int)$sl['id'] ?>)" title="Duplicate">⧉</button>
                <button class="ps-slide-act" onclick="event.stopPropagation();deleteSlide(<?= (int)$sl['id'] ?>)" title="Delete" style="color:#fca5a5">✕</button>
            </div>
        </div>
        <?php endforeach ?>
        <button id="ps-add-slide" onclick="addSlide()">+ Add Slide</button>
    </div>

    <!-- Settings tab -->
    <div id="ps-left-settings">
        <div class="ps-section-title">Slider Settings</div>

        <?php $s = $settings; ?>
        <div class="ps-field">
            <label>Height</label>
            <input type="text" id="cfg-height" value="<?= e($s['height']) ?>" oninput="saveSettings()" placeholder="e.g. 100vh, 600px, 80vh">
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:5px">
                <?php foreach(['100vh','90vh','80vh','70vh','600px','500px','400px'] as $preset): ?>
                <button type="button" onclick="document.getElementById('cfg-height').value='<?= $preset ?>';saveSettings()"
                    style="padding:2px 7px;font-size:11px;border:1px solid var(--panel-b);border-radius:4px;background:var(--panel);color:#94a3b8;cursor:pointer"><?= $preset ?></button>
                <?php endforeach ?>
            </div>
        </div>
        <div class="ps-field">
            <label>Transition Effect</label>
            <select id="cfg-transition" onchange="saveSettings()">
                <?php foreach(['fade'=>'Fade','slide'=>'Slide','zoom'=>'Zoom','flip'=>'Flip','cube'=>'Cube'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $s['transition']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="ps-field">
            <label>Transition Speed (ms)</label>
            <input type="number" id="cfg-transition-speed" value="<?= (int)$s['transition_speed'] ?>" step="100" oninput="saveSettings()">
        </div>
        <div class="ps-field">
            <label>Autoplay Speed (ms)</label>
            <input type="number" id="cfg-autoplay-speed" value="<?= (int)$s['autoplay_speed'] ?>" step="500" oninput="saveSettings()">
        </div>
        <div class="ps-field">
            <label>Mouse Parallax Strength</label>
            <input type="range" id="cfg-mouse-strength" value="<?= (int)$s['mouse_strength'] ?>" min="0" max="80" oninput="this.nextElementSibling.textContent=this.value;saveSettings()">
            <span style="font-size:12px;color:var(--muted)"><?= (int)$s['mouse_strength'] ?>px</span>
        </div>
        <div class="ps-sep"></div>
        <?php foreach([
            ['autoplay','Autoplay'],['mouse_parallax','Mouse Parallax'],
            ['show_arrows','Show Arrows'],['show_dots','Show Dots'],
            ['loop','Loop'],['pause_on_hover','Pause on Hover'],
        ] as [$key,$label]): ?>
        <div class="ps-toggle-row">
            <span><?= $label ?></span>
            <label class="ps-toggle">
                <input type="checkbox" id="cfg-<?= $key ?>" <?= !empty($s[$key])?'checked':'' ?> onchange="saveSettings()">
                <div class="ps-toggle-track"></div><div class="ps-toggle-thumb"></div>
            </label>
        </div>
        <?php endforeach ?>
    </div>
</div>

<!-- CANVAS -->
<div id="ps-canvas-wrap">
    <div id="ps-canvas" style="width:100%;height:<?= e($settings['height']) ?>">
        <div id="ps-canvas-empty">
            <div style="font-size:40px;margin-bottom:12px;opacity:.3">🎞</div>
            <p style="font-size:14px">Select a slide from the left panel</p>
        </div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div id="ps-right">
    <div id="ps-right-header">
        <div id="ps-right-title">Properties</div>
        <button id="ps-right-close" onclick="deselectLayer()" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:18px">✕</button>
    </div>
    <div id="ps-right-body">
        <div id="ps-right-empty"><div class="icon">🖱</div><p>Select a slide or click a layer to edit.</p></div>
    </div>
    <div id="ps-add-layer-bar">
        <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--muted);display:block;width:100%;margin-bottom:2px">Add Layer</span>
        <button class="ps-add-layer-btn" onclick="addLayer('text')">T Text</button>
        <button class="ps-add-layer-btn" onclick="addLayer('heading')">H Heading</button>
        <button class="ps-add-layer-btn" onclick="addLayer('image')">🖼 Image</button>
        <button class="ps-add-layer-btn" onclick="addLayer('button')">⬡ Button</button>
        <button class="ps-add-layer-btn" onclick="addLayer('html')">‹/› HTML</button>
        <button class="ps-add-layer-btn" onclick="addLayer('shape')">⬛ Shape</button>
    </div>
</div>

</div><!-- /ps-main -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.all.min.js"></script>
<script>
// ─── State ────────────────────────────────────────────────────────────────────
var PSE = {
    sliderId:    <?= (int)$slider['id'] ?>,
    base:        '<?= e($base) ?>',
    currentSlideId: null,
    currentSlide:   null,
    layers:         [],   // array of layer objects for current slide
    selectedLayerId: null,
    dirty:          false,
    saving:         false,
    animsIn:  <?= json_encode(array_keys($animsIn)) ?>,
    animsOut: <?= json_encode(array_keys($animsOut)) ?>,
    animsInLabels:  <?= json_encode($animsIn) ?>,
    animsOutLabels: <?= json_encode($animsOut) ?>,
};

var slidesData = <?= json_encode(array_map(function($sl) {
    return [
        'id'        => (int)$sl['id'],
        'title'     => $sl['title'],
        'bg_type'   => $sl['bg_type'],
        'bg_value'  => $sl['bg_value'],
        'bg_overlay'=> (float)$sl['bg_overlay'],
        'bg_overlay_color' => $sl['bg_overlay_color'],
        'duration'  => (int)$sl['duration'],
        'kenburns'  => (bool)$sl['kenburns'],
        'link'      => $sl['link'],
        'link_target' => $sl['link_target'],
        'active'    => (bool)$sl['active'],
    ];
}, $slides)) ?>;

// ─── ID generator ─────────────────────────────────────────────────────────────
function uid(){ return 'l_'+Math.random().toString(36).substr(2,8); }

// ─── Canvas sizing ─────────────────────────────────────────────────────────────
(function initCanvas(){
    var wrap = document.getElementById('ps-canvas-wrap');
    var canvas = document.getElementById('ps-canvas');
    function resize(){
        var w = wrap.clientWidth - 48;
        if(w > 1200) w = 1200;
        canvas.style.width = w + 'px';
    }
    resize();
    window.addEventListener('resize', resize);
})();

// ─── Select slide ─────────────────────────────────────────────────────────────
function selectSlide(id) {
    if(PSE.currentSlideId === id) return;
    if(PSE.dirty) saveLayersNow(function(){ _doSelectSlide(id); });
    else _doSelectSlide(id);
}

function _doSelectSlide(id) {
    // Update thumb highlight
    document.querySelectorAll('.ps-slide-thumb').forEach(function(t){ t.classList.remove('active'); });
    var th = document.getElementById('st-'+id);
    if(th) th.classList.add('active');

    PSE.currentSlideId = id;
    PSE.selectedLayerId = null;

    var slideObj = slidesData.find(function(s){ return s.id===id; });
    PSE.currentSlide = slideObj || null;

    // Render canvas background
    renderCanvasBg();

    // Load layers
    fetch(PSE.base + '/manage/sliders/slides/' + id + '/layers')
        .then(function(r){ return r.json(); })
        .then(function(data){
            PSE.layers = (data.layers || []).map(function(l){
                l._uid = uid();
                return l;
            });
            renderLayers();
            showSlidePanel();
        });
}

function renderCanvasBg() {
    var canvas = document.getElementById('ps-canvas');
    var s = PSE.currentSlide;
    canvas.style.background = '';
    canvas.style.backgroundImage = '';

    // Remove previous bg elements
    canvas.querySelectorAll('.ps-bg-el').forEach(function(el){ el.remove(); });

    if(!s) return;

    if(s.bg_type === 'color') {
        canvas.style.background = s.bg_value || '#1e293b';
    } else if(s.bg_type === 'image') {
        canvas.style.backgroundImage = 'url('+JSON.stringify(s.bg_value)+')';
        canvas.style.backgroundSize = 'cover';
        canvas.style.backgroundPosition = 'center';
    } else if(s.bg_type === 'gradient') {
        canvas.style.background = s.bg_value;
    }

    if(s.bg_overlay > 0) {
        var overlay = document.createElement('div');
        overlay.className = 'ps-bg-el';
        overlay.style.cssText = 'position:absolute;inset:0;background:'+s.bg_overlay_color+';opacity:'+s.bg_overlay+';pointer-events:none;z-index:0';
        canvas.appendChild(overlay);
    }

    document.getElementById('ps-canvas-empty').style.display = 'none';
}

// ─── Layer rendering ──────────────────────────────────────────────────────────
function renderLayers() {
    var canvas = document.getElementById('ps-canvas');
    canvas.querySelectorAll('.ps-layer').forEach(function(el){ el.remove(); });
    PSE.layers.forEach(function(l){ canvas.appendChild(buildLayerEl(l)); });
}

function buildLayerEl(l) {
    var el = document.createElement('div');
    el.className = 'ps-layer' + (l._uid === PSE.selectedLayerId ? ' selected' : '');
    el.dataset.uid = l._uid;

    var s = l.settings || {};
    var x = parseFloat(l.x) || 50;
    var y = parseFloat(l.y) || 50;
    var w = l.width && l.width !== 'auto' ? l.width : 'auto';
    var h = l.height && l.height !== 'auto' ? l.height : 'auto';

    el.style.cssText = 'left:'+x+'%;top:'+y+'%;'+(w!=='auto'?'width:'+w+';':'')+(h!=='auto'?'height:'+h+';':'')+'position:absolute;';

    // Inner content
    var inner = document.createElement('div');
    inner.style.cssText = 'pointer-events:none;position:relative;z-index:1;';
    inner.innerHTML = layerPreviewHtml(l);
    el.appendChild(inner);

    // Outline
    var outline = document.createElement('div');
    outline.className = 'ps-layer-outline';
    el.appendChild(outline);

    // Toolbar
    var toolbar = document.createElement('div');
    toolbar.className = 'ps-layer-toolbar';
    toolbar.innerHTML =
        '<button class="ps-layer-tbtn" onclick="moveLayerZ(\''+l._uid+'\',1)" title="Up">↑Z</button>'+
        '<button class="ps-layer-tbtn" onclick="moveLayerZ(\''+l._uid+'\',-1)" title="Down">↓Z</button>'+
        '<button class="ps-layer-tbtn" onclick="dupLayer(\''+l._uid+'\')" title="Duplicate">⧉</button>'+
        '<button class="ps-layer-tbtn" onclick="deleteLayer(\''+l._uid+'\')" title="Delete" style="color:#fca5a5">✕</button>';
    el.appendChild(toolbar);

    // Resize handle
    var resizeH = document.createElement('div');
    resizeH.className = 'ps-resize-handle';
    el.appendChild(resizeH);

    // Drag
    makeDraggable(el, l);
    makeResizable(resizeH, el, l);

    el.addEventListener('click', function(e){
        e.stopPropagation();
        selectLayer(l._uid);
    });

    return el;
}

function layerPreviewHtml(l) {
    var s = l.settings || {};
    var content = l.content || '';
    switch(l.type) {
        case 'heading':
        case 'text':
            var tag = l.type === 'heading' ? 'h2' : 'p';
            var css = 'margin:0;'+(s.color?'color:'+s.color+';':'')+(s.font_size?'font-size:'+s.font_size+';':'')+(s.font_weight?'font-weight:'+s.font_weight+';':'')+(s.text_align?'text-align:'+s.text_align+';':'');
            return '<'+tag+' style="'+css+'">'+(content||'Text')+'</'+tag+'>';
        case 'image':
            if(!content) return '<div style="width:120px;height:80px;background:rgba(255,255,255,.1);border:2px dashed rgba(255,255,255,.3);border-radius:4px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.4)">🖼</div>';
            return '<img src="'+content+'" style="max-width:'+(s.width||'200px')+';border-radius:'+(s.radius||'0')+';" alt="">';
        case 'button':
            var bcss = 'display:inline-block;padding:'+(s.padding||'10px 24px')+';background:'+(s.bg_color||'#10B27C')+';color:'+(s.color||'#fff')+';border-radius:'+(s.radius||'8px')+';font-size:'+(s.font_size||'15px')+';font-weight:'+(s.font_weight||'700')+';border:'+(s.border||'none')+';cursor:pointer;text-decoration:none';
            return '<a style="'+bcss+'">'+(content||'Button')+'</a>';
        case 'html':
            return content || '<div style="padding:8px;border:1px dashed rgba(255,255,255,.3);color:rgba(255,255,255,.5);font-size:12px">&lt;html&gt;</div>';
        case 'shape':
            var shape = s.shape || 'rect';
            var shcss = 'width:'+(s.width||'120px')+';height:'+(s.height||'60px')+';background:'+(s.bg_color||'rgba(16,178,124,.7)')+';opacity:'+(s.opacity||'1')+';border-radius:'+(shape==='circle'?'50%':(s.radius||'0'));
            return '<div style="'+shcss+'"></div>';
        default: return '<div style="padding:6px;color:rgba(255,255,255,.5);font-size:12px">'+l.type+'</div>';
    }
}

// ─── Drag & Resize ─────────────────────────────────────────────────────────────
function makeDraggable(el, l) {
    var startX, startY, startLeft, startTop, canvas;
    el.addEventListener('mousedown', function(e) {
        if(e.target.classList.contains('ps-resize-handle') || e.target.closest('.ps-layer-toolbar')) return;
        e.preventDefault();
        canvas = document.getElementById('ps-canvas');
        startX = e.clientX; startY = e.clientY;
        startLeft = parseFloat(l.x) || 0;
        startTop  = parseFloat(l.y) || 0;
        function onMove(ev) {
            var dx = ev.clientX - startX;
            var dy = ev.clientY - startY;
            var cw = canvas.clientWidth;
            var ch = canvas.clientHeight;
            var newX = Math.max(0, Math.min(100, startLeft + (dx/cw*100)));
            var newY = Math.max(0, Math.min(100, startTop  + (dy/ch*100)));
            l.x = parseFloat(newX.toFixed(2));
            l.y = parseFloat(newY.toFixed(2));
            el.style.left = l.x + '%';
            el.style.top  = l.y + '%';
            markDirty();
        }
        function onUp() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            if(PSE.selectedLayerId === l._uid) updateLayerPositionInputs(l);
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
}

function makeResizable(handle, el, l) {
    handle.addEventListener('mousedown', function(e) {
        e.preventDefault(); e.stopPropagation();
        var startX = e.clientX, startY = e.clientY;
        var startW = el.offsetWidth, startH = el.offsetHeight;
        function onMove(ev) {
            var nw = Math.max(20, startW + ev.clientX - startX);
            var nh = Math.max(20, startH + ev.clientY - startY);
            el.style.width = nw + 'px';
            el.style.height = nh + 'px';
            l.width  = nw + 'px';
            l.height = nh + 'px';
            markDirty();
        }
        function onUp() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
}

// ─── Layer selection & properties ─────────────────────────────────────────────
function selectLayer(uid) {
    PSE.selectedLayerId = uid;
    renderLayers();
    var l = PSE.layers.find(function(x){ return x._uid===uid; });
    if(l) showLayerPanel(l);
}

function deselectLayer() {
    PSE.selectedLayerId = null;
    renderLayers();
    showSlidePanel();
}

document.getElementById('ps-canvas').addEventListener('click', function(e){
    if(e.target === this || e.target.id === 'ps-canvas-empty') deselectLayer();
});

// ─── Slide panel ──────────────────────────────────────────────────────────────
function showSlidePanel() {
    var s = PSE.currentSlide;
    if(!s) { document.getElementById('ps-right-body').innerHTML = '<div id="ps-right-empty"><div class="icon">🎞</div><p>Select a slide from the left.</p></div>'; return; }
    document.getElementById('ps-right-title').textContent = 'Slide Settings';
    var html = '<div class="ps-section-title">Background</div>' +
        '<div class="ps-field"><label>Type</label><select id="sl-bg-type" onchange="onBgTypeChange()">' +
        ['color','image','gradient'].map(function(v){ return '<option value="'+v+'"'+(s.bg_type===v?' selected':'')+'>'+v.charAt(0).toUpperCase()+v.slice(1)+'</option>'; }).join('') +
        '</select></div>' +
        '<div class="ps-field" id="sl-bg-value-wrap"><label id="sl-bg-value-label">' + (s.bg_type==='image'?'Image URL':'Color / Value') + '</label>' +
        '<input type="' + (s.bg_type==='color'?'color':'text') + '" id="sl-bg-value" value="'+escAttr(s.bg_value||'')+'" oninput="onSlideChange()"></div>' +
        '<div class="ps-row"><div class="ps-field"><label>Overlay Opacity</label><input type="range" id="sl-bg-overlay" min="0" max="1" step="0.05" value="'+(s.bg_overlay||0)+'" oninput="document.getElementById(\'sl-overlay-v\').textContent=this.value;onSlideChange()"><span id="sl-overlay-v" style="font-size:12px;color:var(--muted)">'+(s.bg_overlay||0)+'</span></div>' +
        '<div class="ps-field"><label>Overlay Color</label><input type="color" id="sl-bg-overlay-color" value="'+(s.bg_overlay_color||'#000000')+'" oninput="onSlideChange()"></div></div>' +
        '<div class="ps-sep"></div>' +
        '<div class="ps-section-title">Slide Options</div>' +
        '<div class="ps-field"><label>Title</label><input type="text" id="sl-title" value="'+escAttr(s.title||'')+'" oninput="onSlideChange()"></div>' +
        '<div class="ps-field"><label>Duration (ms)</label><input type="number" id="sl-duration" value="'+(s.duration||6000)+'" step="500" oninput="onSlideChange()"></div>' +
        '<div class="ps-field"><label>Link URL</label><input type="text" id="sl-link" value="'+escAttr(s.link||'')+'" oninput="onSlideChange()"></div>' +
        '<div class="ps-field"><label>Link Target</label><select id="sl-link-target" onchange="onSlideChange()"><option value="_self"'+(s.link_target==='_self'?' selected':'')+'>Same Tab</option><option value="_blank"'+(s.link_target==='_blank'?' selected':'')+'>New Tab</option></select></div>' +
        '<div class="ps-toggle-row"><span>Ken Burns Effect</span><label class="ps-toggle"><input type="checkbox" id="sl-kenburns"'+(s.kenburns?' checked':'')+' onchange="onSlideChange()"><div class="ps-toggle-track"></div><div class="ps-toggle-thumb"></div></label></div>' +
        '<div class="ps-toggle-row"><span>Active</span><label class="ps-toggle"><input type="checkbox" id="sl-active"'+(s.active?' checked':'')+' onchange="onSlideChange()"><div class="ps-toggle-track"></div><div class="ps-toggle-thumb"></div></label></div>';
    document.getElementById('ps-right-body').innerHTML = html;
}

function onBgTypeChange() {
    var t = document.getElementById('sl-bg-type').value;
    var input = document.getElementById('sl-bg-value');
    var label = document.getElementById('sl-bg-value-label');
    if(t === 'color') { input.type = 'color'; label.textContent = 'Color'; }
    else if(t === 'image') { input.type = 'text'; label.textContent = 'Image URL'; }
    else { input.type = 'text'; label.textContent = 'CSS Gradient'; }
    onSlideChange();
}

function onSlideChange() {
    if(!PSE.currentSlide) return;
    var s = PSE.currentSlide;
    s.bg_type   = document.getElementById('sl-bg-type')?.value || s.bg_type;
    s.bg_value  = document.getElementById('sl-bg-value')?.value || '';
    s.bg_overlay= parseFloat(document.getElementById('sl-bg-overlay')?.value || 0);
    s.bg_overlay_color = document.getElementById('sl-bg-overlay-color')?.value || '#000000';
    s.title     = document.getElementById('sl-title')?.value || '';
    s.duration  = parseInt(document.getElementById('sl-duration')?.value || 6000);
    s.link      = document.getElementById('sl-link')?.value || '';
    s.link_target = document.getElementById('sl-link-target')?.value || '_self';
    s.kenburns  = document.getElementById('sl-kenburns')?.checked || false;
    s.active    = document.getElementById('sl-active')?.checked || false;
    renderCanvasBg();
    // Update slide thumbnail title
    var th = document.getElementById('st-'+s.id);
    if(th) th.querySelector('.ps-slide-title').textContent = s.title || 'Slide';
    markDirty();
    // Debounced save slide meta
    clearTimeout(s._saveTimer);
    s._saveTimer = setTimeout(function(){
        fetch(PSE.base+'/manage/sliders/slides/'+s.id+'/update', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(s)
        });
    }, 800);
}

// ─── Layer panel ──────────────────────────────────────────────────────────────
function showLayerPanel(l) {
    document.getElementById('ps-right-title').textContent = l.type.charAt(0).toUpperCase()+l.type.slice(1)+' Layer';
    var s = l.settings || {};
    var animInOpts = PSE.animsIn.map(function(v){ return '<option value="'+v+'"'+(l.anim_in===v?' selected':'')+'>'+PSE.animsInLabels[v]+'</option>'; }).join('');
    var animOutOpts = PSE.animsOut.map(function(v){ return '<option value="'+v+'"'+(l.anim_out===v?' selected':'')+'>'+PSE.animsOutLabels[v]+'</option>'; }).join('');

    var contentField = '';
    if(l.type === 'heading' || l.type === 'text') {
        contentField = '<div class="ps-field"><label>Content</label><textarea id="lp-content" oninput="onLayerChange(\''+ l._uid +'\')">'+escHtml(l.content||'')+'</textarea></div>';
    } else if(l.type === 'image') {
        contentField = '<div class="ps-field"><label>Image URL</label><input type="text" id="lp-content" value="'+escAttr(l.content||'')+'" oninput="onLayerChange(\''+l._uid+'\')"><button onclick="openGalleryForLayer(\''+l._uid+'\')" style="margin-top:5px;width:100%;padding:6px;background:var(--bg);border:1.5px solid var(--border);border-radius:6px;font-size:12px;cursor:pointer">🖼 Select from Gallery</button></div>';
    } else if(l.type === 'button') {
        contentField = '<div class="ps-field"><label>Button Text</label><input type="text" id="lp-content" value="'+escAttr(l.content||'Button')+'" oninput="onLayerChange(\''+l._uid+'\')"></div>';
    } else if(l.type === 'html') {
        contentField = '<div class="ps-field"><label>HTML Code</label><textarea id="lp-content" style="font-family:monospace;font-size:12px" oninput="onLayerChange(\''+l._uid+'\')">'+escHtml(l.content||'')+'</textarea></div>';
    }

    var styleFields = buildStyleFields(l);

    var html = contentField +
        '<div class="ps-sep"></div>' +
        '<div class="ps-section-title">Position & Size</div>' +
        '<div class="ps-row">' +
        '<div class="ps-field"><label>X (%)</label><input type="number" id="lp-x" value="'+l.x+'" step="0.5" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
        '<div class="ps-field"><label>Y (%)</label><input type="number" id="lp-y" value="'+l.y+'" step="0.5" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
        '</div><div class="ps-row">' +
        '<div class="ps-field"><label>Width</label><input type="text" id="lp-width" value="'+escAttr(l.width||'auto')+'" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
        '<div class="ps-field"><label>Height</label><input type="text" id="lp-height" value="'+escAttr(l.height||'auto')+'" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
        '</div>' +
        '<div class="ps-field"><label>Depth (parallax 0–1)</label><input type="range" id="lp-depth" min="0" max="1" step="0.05" value="'+(l.depth||0.5)+'" oninput="this.nextElementSibling.textContent=parseFloat(this.value).toFixed(2);onLayerChange(\''+l._uid+'\')"><span style="font-size:12px;color:var(--muted)">'+(parseFloat(l.depth||0.5).toFixed(2))+'</span></div>' +
        '<div class="ps-sep"></div>' +
        '<div class="ps-section-title">Animation In</div>' +
        '<div class="ps-row">' +
        '<div class="ps-field"><label>Effect</label><select id="lp-anim-in" onchange="onLayerChange(\''+l._uid+'\')">'+animInOpts+'</select></div>' +
        '<div class="ps-field"><label>Duration (ms)</label><input type="number" id="lp-anim-duration" value="'+(l.anim_duration||700)+'" step="100" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
        '</div>' +
        '<div class="ps-field"><label>Delay (ms)</label><input type="number" id="lp-anim-delay" value="'+(l.anim_delay||300)+'" step="100" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
        '<div class="ps-sep"></div>' +
        '<div class="ps-section-title">Animation Out</div>' +
        '<div class="ps-row">' +
        '<div class="ps-field"><label>Effect</label><select id="lp-anim-out" onchange="onLayerChange(\''+l._uid+'\')">'+animOutOpts+'</select></div>' +
        '<div class="ps-field"><label>Delay (ms)</label><input type="number" id="lp-anim-out-delay" value="'+(l.anim_out_delay||0)+'" step="100" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
        '</div>' +
        styleFields;

    document.getElementById('ps-right-body').innerHTML = html;
}

function buildStyleFields(l) {
    var s = l.settings || {};
    var t = l.type;
    var out = '<div class="ps-sep"></div><div class="ps-section-title">Style</div>';
    if(t === 'heading' || t === 'text') {
        out += field('Font Size','lp-s-font_size',s.font_size||'','text',l._uid) +
               field('Font Weight','lp-s-font_weight',s.font_weight||'700','text',l._uid) +
               field('Color','lp-s-color',s.color||'#ffffff','color',l._uid) +
               '<div class="ps-field"><label>Text Align</label><select id="lp-s-text_align" onchange="onLayerChange(\''+l._uid+'\')">'+
               ['left','center','right'].map(function(v){ return '<option value="'+v+'"'+(s.text_align===v?' selected':'')+'>'+v+'</option>'; }).join('')+'</select></div>' +
               field('Letter Spacing','lp-s-letter_spacing',s.letter_spacing||'','text',l._uid) +
               field('Line Height','lp-s-line_height',s.line_height||'','text',l._uid) +
               field('Text Shadow','lp-s-text_shadow',s.text_shadow||'','text',l._uid);
    } else if(t === 'button') {
        out += field('BG Color','lp-s-bg_color',s.bg_color||'#10B27C','color',l._uid) +
               field('Text Color','lp-s-color',s.color||'#ffffff','color',l._uid) +
               field('Font Size','lp-s-font_size',s.font_size||'15px','text',l._uid) +
               field('Font Weight','lp-s-font_weight',s.font_weight||'700','text',l._uid) +
               field('Padding','lp-s-padding',s.padding||'10px 28px','text',l._uid) +
               field('Border Radius','lp-s-radius',s.radius||'8px','text',l._uid) +
               field('Border','lp-s-border',s.border||'none','text',l._uid) +
               '<div class="ps-field"><label>URL</label><input type="text" id="lp-s-url" value="'+escAttr(s.url||'#')+'" oninput="onLayerChange(\''+l._uid+'\')"></div>' +
               '<div class="ps-field"><label>Target</label><select id="lp-s-target" onchange="onLayerChange(\''+l._uid+'\')"><option value="_self"'+(s.target!=='_blank'?' selected':'')+'>Same Tab</option><option value="_blank"'+(s.target==='_blank'?' selected':'')+'>New Tab</option></select></div>';
    } else if(t === 'image') {
        out += field('Width','lp-s-width',s.width||'200px','text',l._uid) +
               field('Border Radius','lp-s-radius',s.radius||'0','text',l._uid) +
               field('Opacity','lp-s-opacity',s.opacity||'1','text',l._uid);
    } else if(t === 'shape') {
        out += '<div class="ps-field"><label>Shape</label><select id="lp-s-shape" onchange="onLayerChange(\''+l._uid+'\')">'+
               ['rect','circle'].map(function(v){ return '<option value="'+v+'"'+(s.shape===v?' selected':'')+'>'+v+'</option>'; }).join('')+'</select></div>' +
               field('Background','lp-s-bg_color',s.bg_color||'rgba(16,178,124,.7)','color',l._uid) +
               field('Width','lp-s-width',s.width||'120px','text',l._uid) +
               field('Height','lp-s-height',s.height||'60px','text',l._uid) +
               field('Opacity','lp-s-opacity',s.opacity||'1','text',l._uid) +
               field('Border Radius','lp-s-radius',s.radius||'0','text',l._uid);
    }
    return out;
}

function field(label, id, val, type, uid) {
    var inputType = (type==='color') ? 'color' : 'text';
    return '<div class="ps-field"><label>'+label+'</label><input type="'+inputType+'" id="'+id+'" value="'+escAttr(val)+'" oninput="onLayerChange(\''+uid+'\')"></div>';
}

function onLayerChange(uid) {
    var l = PSE.layers.find(function(x){ return x._uid===uid; });
    if(!l) return;
    // Update core fields
    if(document.getElementById('lp-content')) l.content = document.getElementById('lp-content').value;
    if(document.getElementById('lp-x'))       l.x = parseFloat(document.getElementById('lp-x').value)||0;
    if(document.getElementById('lp-y'))       l.y = parseFloat(document.getElementById('lp-y').value)||0;
    if(document.getElementById('lp-width'))   l.width  = document.getElementById('lp-width').value||'auto';
    if(document.getElementById('lp-height'))  l.height = document.getElementById('lp-height').value||'auto';
    if(document.getElementById('lp-depth'))   l.depth  = parseFloat(document.getElementById('lp-depth').value)||0.5;
    if(document.getElementById('lp-anim-in')) l.anim_in = document.getElementById('lp-anim-in').value;
    if(document.getElementById('lp-anim-out'))l.anim_out= document.getElementById('lp-anim-out').value;
    if(document.getElementById('lp-anim-delay'))    l.anim_delay     = parseInt(document.getElementById('lp-anim-delay').value)||0;
    if(document.getElementById('lp-anim-duration')) l.anim_duration  = parseInt(document.getElementById('lp-anim-duration').value)||700;
    if(document.getElementById('lp-anim-out-delay'))l.anim_out_delay = parseInt(document.getElementById('lp-anim-out-delay').value)||0;

    // Collect settings
    var settings = l.settings || {};
    var settingKeys = ['font_size','font_weight','color','text_align','letter_spacing','line_height','text_shadow',
                       'bg_color','padding','radius','border','url','target','shape','width','height','opacity'];
    settingKeys.forEach(function(k){
        var el = document.getElementById('lp-s-'+k);
        if(el) settings[k] = el.value;
    });
    l.settings = settings;

    // Re-render just this layer
    var el = document.querySelector('.ps-layer[data-uid="'+uid+'"]');
    if(el) {
        el.style.left = l.x+'%';
        el.style.top  = l.y+'%';
        el.style.width  = (l.width !== 'auto' ? l.width : '');
        el.style.height = (l.height !== 'auto' ? l.height : '');
        var inner = el.querySelector('div:not(.ps-layer-outline):not(.ps-layer-toolbar):not(.ps-resize-handle)');
        if(inner) inner.innerHTML = layerPreviewHtml(l);
    }
    markDirty();
}

function updateLayerPositionInputs(l) {
    if(document.getElementById('lp-x')) document.getElementById('lp-x').value = l.x;
    if(document.getElementById('lp-y')) document.getElementById('lp-y').value = l.y;
}

// ─── Add / delete / duplicate layers ─────────────────────────────────────────
function addLayer(type) {
    if(!PSE.currentSlideId) {
        Swal.fire({icon:'info',title:'Select a slide first',timer:2000,showConfirmButton:false});
        return;
    }
    var defaults = {
        text:    {content:'Your text here', settings:{font_size:'20px',font_weight:'400',color:'#ffffff',text_align:'center'}},
        heading: {content:'Your Heading',   settings:{font_size:'48px',font_weight:'700',color:'#ffffff',text_align:'center'}},
        image:   {content:'',               settings:{width:'200px',radius:'0'}},
        button:  {content:'Click Here',     settings:{bg_color:'#10B27C',color:'#ffffff',font_size:'15px',font_weight:'700',padding:'10px 28px',radius:'8px',border:'none',url:'#',target:'_self'}},
        html:    {content:'<p>HTML</p>',    settings:{}},
        shape:   {content:'',               settings:{shape:'rect',bg_color:'rgba(16,178,124,.7)',width:'120px',height:'60px',opacity:'1',radius:'0'}},
    };
    var def = defaults[type] || {content:'',settings:{}};
    var l = {
        _uid: uid(), id: null, slide_id: PSE.currentSlideId,
        type: type, content: def.content,
        x: 50, y: 50, width: 'auto', height: 'auto', depth: 0.5,
        anim_in:'fadeIn', anim_out:'fadeOut', anim_delay:300, anim_duration:700, anim_out_delay:0,
        settings: def.settings, order_index: PSE.layers.length,
    };
    PSE.layers.push(l);
    document.getElementById('ps-canvas').appendChild(buildLayerEl(l));
    selectLayer(l._uid);
    markDirty();
}

function deleteLayer(uid) {
    PSE.layers = PSE.layers.filter(function(l){ return l._uid!==uid; });
    var el = document.querySelector('.ps-layer[data-uid="'+uid+'"]');
    if(el) el.remove();
    PSE.selectedLayerId = null;
    showSlidePanel();
    markDirty();
}

function dupLayer(uid) {
    var src = PSE.layers.find(function(l){ return l._uid===uid; });
    if(!src) return;
    var copy = JSON.parse(JSON.stringify(src));
    copy._uid = uid();
    copy.x += 3; copy.y += 3;
    copy.order_index = PSE.layers.length;
    PSE.layers.push(copy);
    document.getElementById('ps-canvas').appendChild(buildLayerEl(copy));
    selectLayer(copy._uid);
    markDirty();
}

function moveLayerZ(uid, dir) {
    var idx = PSE.layers.findIndex(function(l){ return l._uid===uid; });
    var newIdx = idx + dir;
    if(newIdx < 0 || newIdx >= PSE.layers.length) return;
    var tmp = PSE.layers[idx];
    PSE.layers[idx] = PSE.layers[newIdx];
    PSE.layers[newIdx] = tmp;
    renderLayers();
    selectLayer(uid);
    markDirty();
}

// ─── Slide operations ─────────────────────────────────────────────────────────
function addSlide() {
    fetch(PSE.base+'/manage/sliders/'+PSE.sliderId+'/slides/add', {method:'POST'})
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(!data.ok) return;
            var newSlide = {
                id: data.slide_id, title:'New Slide', bg_type:'color', bg_value:'#1e293b',
                bg_overlay:0, bg_overlay_color:'#000000', duration:6000, kenburns:false,
                link:'', link_target:'_self', active:true,
            };
            slidesData.push(newSlide);
            // Add thumb
            var list = document.getElementById('ps-slides-list');
            var btn  = document.getElementById('ps-add-slide');
            var thumb = document.createElement('div');
            thumb.className = 'ps-slide-thumb';
            thumb.id = 'st-'+data.slide_id;
            thumb.dataset.id = data.slide_id;
            thumb.onclick = function(){ selectSlide(data.slide_id); };
            thumb.innerHTML = '<div class="ps-slide-thumb-inner"><span style="color:rgba(255,255,255,.2);font-size:20px">◈</span><span class="ps-slide-num">'+(slidesData.length)+'</span></div>'+
                '<div class="ps-slide-title">New Slide</div>'+
                '<div class="ps-slide-actions"><button class="ps-slide-act" onclick="event.stopPropagation();dupSlide('+data.slide_id+')" title="Duplicate">⧉</button><button class="ps-slide-act" onclick="event.stopPropagation();deleteSlide('+data.slide_id+')" title="Delete" style="color:#fca5a5">✕</button></div>';
            list.insertBefore(thumb, btn);
            selectSlide(data.slide_id);
        });
}

function deleteSlide(id) {
    if(!confirm('Delete this slide and all its layers?')) return;
    fetch(PSE.base+'/manage/sliders/slides/'+id+'/delete', {method:'POST'})
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(!data.ok) return;
            slidesData = slidesData.filter(function(s){ return s.id!==id; });
            var th = document.getElementById('st-'+id);
            if(th) th.remove();
            if(PSE.currentSlideId === id) {
                PSE.currentSlideId = null;
                PSE.layers = [];
                document.getElementById('ps-canvas').querySelectorAll('.ps-layer,.ps-bg-el').forEach(function(e){ e.remove(); });
                document.getElementById('ps-canvas-empty').style.display = '';
                document.getElementById('ps-canvas').style.background = '';
                document.getElementById('ps-right-body').innerHTML = '<div id="ps-right-empty"><div class="icon">🎞</div><p>Select a slide.</p></div>';
            }
        });
}

function dupSlide(id) {
    var src = slidesData.find(function(s){ return s.id===id; });
    if(!src) return;
    fetch(PSE.base+'/manage/sliders/'+PSE.sliderId+'/slides/add', {method:'POST'})
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(!data.ok) return;
            // Copy slide settings
            fetch(PSE.base+'/manage/sliders/slides/'+data.slide_id+'/update', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({...src, id: data.slide_id})
            });
            // Copy layers
            fetch(PSE.base+'/manage/sliders/slides/'+id+'/layers')
                .then(function(r){ return r.json(); })
                .then(function(ld){
                    fetch(PSE.base+'/manage/sliders/slides/'+data.slide_id+'/layers', {
                        method:'POST', headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({layers: ld.layers || []})
                    });
                });
            location.reload();
        });
}

// ─── Gallery for layer image ──────────────────────────────────────────────────
function openGalleryForLayer(uid) {
    Swal.fire({
        title:'Select Image', width:860,
        html:'<div id="ps-gal-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;max-height:55vh;overflow-y:auto"><div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b">Loading...</div></div>',
        showConfirmButton:false, showCloseButton:true,
        didOpen:function(){
            fetch(PSE.base+'/manage/gallery/json')
                .then(function(r){ return r.json(); })
                .then(function(d){
                    var grid = document.getElementById('ps-gal-grid');
                    var items = (d.media||[]).filter(function(m){ return (m.mime_type||'').startsWith('image/'); });
                    if(!items.length){ grid.innerHTML='<div style="grid-column:1/-1;text-align:center;color:#64748b;padding:40px">No images</div>'; return; }
                    grid.innerHTML = items.map(function(m){
                        var url = PSE.base+'/storage/media/'+m.path;
                        return '<div onclick="pickLayerImg(\''+uid+'\',\''+url+'\')" style="aspect-ratio:1;border-radius:6px;overflow:hidden;border:2px solid transparent;cursor:pointer;transition:border-color .12s" onmouseover="this.style.borderColor=\'#10B27C\'" onmouseout="this.style.borderColor=\'transparent\'"><img src="'+url+'" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>';
                    }).join('');
                });
        }
    });
}

function pickLayerImg(uid, url) {
    var l = PSE.layers.find(function(x){ return x._uid===uid; });
    if(!l) return;
    l.content = url;
    var input = document.getElementById('lp-content');
    if(input) input.value = url;
    var el = document.querySelector('.ps-layer[data-uid="'+uid+'"]');
    if(el) {
        var inner = el.querySelector('div:not(.ps-layer-outline):not(.ps-layer-toolbar):not(.ps-resize-handle)');
        if(inner) inner.innerHTML = layerPreviewHtml(l);
    }
    Swal.close();
    markDirty();
}

// ─── Save ─────────────────────────────────────────────────────────────────────
function markDirty() {
    PSE.dirty = true;
    document.getElementById('ps-save-status').textContent = 'Unsaved';
    document.getElementById('ps-save-status').style.color = '#f59e0b';
    clearTimeout(PSE._autoSave);
    PSE._autoSave = setTimeout(function(){ saveLayersNow(); }, 8000);
}

function saveLayersNow(callback) {
    if(!PSE.currentSlideId || PSE.saving) { if(callback) callback(); return; }
    PSE.saving = true;
    var layers = PSE.layers.map(function(l,i){
        return {
            id: l.id||null, type:l.type, content:l.content,
            x:l.x, y:l.y, width:l.width, height:l.height, depth:l.depth,
            anim_in:l.anim_in, anim_out:l.anim_out,
            anim_delay:l.anim_delay, anim_duration:l.anim_duration, anim_out_delay:l.anim_out_delay,
            settings:l.settings||{}, order_index:i,
        };
    });
    fetch(PSE.base+'/manage/sliders/slides/'+PSE.currentSlideId+'/layers', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({layers:layers})
    })
    .then(function(r){ return r.json(); })
    .then(function(){
        PSE.dirty  = false;
        PSE.saving = false;
        var st = document.getElementById('ps-save-status');
        st.textContent = 'Saved ✓'; st.style.color = '#10B27C';
        setTimeout(function(){ st.textContent=''; }, 2500);
        if(callback) callback();
    })
    .catch(function(){
        PSE.saving = false;
        if(callback) callback();
    });
}

function saveName(name) {
    fetch(PSE.base+'/manage/sliders/'+PSE.sliderId+'/settings', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({name:name, settings:collectSettings()})
    });
}

function saveSettings() {
    fetch(PSE.base+'/manage/sliders/'+PSE.sliderId+'/settings', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({name:document.getElementById('ps-slider-name').value, settings:collectSettings()})
    });
}

function collectSettings() {
    return {
        height:           document.getElementById('cfg-height')?.value           || '560px',
        transition:       document.getElementById('cfg-transition')?.value       || 'fade',
        transition_speed: parseInt(document.getElementById('cfg-transition-speed')?.value || 900),
        autoplay_speed:   parseInt(document.getElementById('cfg-autoplay-speed')?.value   || 6000),
        mouse_strength:   parseInt(document.getElementById('cfg-mouse-strength')?.value   || 25),
        autoplay:         document.getElementById('cfg-autoplay')?.checked       ?? true,
        mouse_parallax:   document.getElementById('cfg-mouse_parallax')?.checked ?? true,
        show_arrows:      document.getElementById('cfg-show_arrows')?.checked    ?? true,
        show_dots:        document.getElementById('cfg-show_dots')?.checked      ?? true,
        loop:             document.getElementById('cfg-loop')?.checked           ?? true,
        pause_on_hover:   document.getElementById('cfg-pause_on_hover')?.checked ?? true,
    };
}

// ─── Tabs ─────────────────────────────────────────────────────────────────────
function showLeftTab(name, el) {
    document.querySelectorAll('.ps-ltab').forEach(function(t){ t.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('ps-slides-list').style.display   = name==='slides'   ? 'block' : 'none';
    document.getElementById('ps-left-settings').style.display = name==='settings' ? 'block' : 'none';
}

// ─── Preview ──────────────────────────────────────────────────────────────────
function previewSlider() {
    window.open(PSE.base+'/api/v1/sliders/'+PSE.sliderId+'?preview=1','_blank');
}

// ─── Utility ──────────────────────────────────────────────────────────────────
function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s){ return String(s).replace(/"/g,'&quot;'); }

window.addEventListener('beforeunload', function(e){ if(PSE.dirty){ e.preventDefault(); e.returnValue=''; } });
window.addEventListener('keydown', function(e){
    if((e.ctrlKey||e.metaKey)&&e.key==='s'){ e.preventDefault(); saveLayersNow(); }
    if(e.key==='Delete'&&PSE.selectedLayerId){ deleteLayer(PSE.selectedLayerId); }
});

// ─── Init: select first slide ─────────────────────────────────────────────────
<?php if (!empty($slides)): ?>
selectSlide(<?= (int)$slides[0]['id'] ?>);
<?php endif ?>
</script>
</body>
</html>
