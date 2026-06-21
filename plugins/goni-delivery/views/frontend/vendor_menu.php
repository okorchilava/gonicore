<?php
$isOpen   = $delivery->isVendorOpen($vendor);
$catEmoji = match($vendor['category'] ?? 'restaurant') {
    'grocery'  => '🛒', 'pharmacy' => '💊', 'cafe' => '☕',
    'bakery'   => '🥐', 'other'    => '📦', default => '🍽',
};
?>
<style>
/* ═══ Hide ALL site chrome ═══════════════════════════════════ */
.gc-header,.gc-footer,footer,header,nav.site-nav{display:none!important}
.gc-main{padding:0!important;margin:0!important;max-width:none!important;width:100%!important}
body{background:#f0f2f5!important;overflow-x:hidden}

*{box-sizing:border-box;margin:0;padding:0}
a,a:hover,a:visited,a:focus{text-decoration:none!important}
:root{
  --p:#f59e0b;--pd:#d97706;--g:#22c55e;--r:#ef4444;
  --bg:#f0f2f5;--card:#fff;--border:#e8ecf0;
  --text:#0d1117;--muted:#6b7a8d;
  --radius:14px;--font:system-ui,-apple-system,'Segoe UI',sans-serif;
}

/* ═══ Sticky topbar ══════════════════════════════════════════ */
.gd-bar{
  position:sticky;top:0;z-index:200;
  background:rgba(255,255,255,.97);backdrop-filter:blur(14px);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:12px;padding:12px 18px;
}
.gd-back{
  width:38px;height:38px;border-radius:50%;background:var(--bg);
  border:1.5px solid var(--border);display:flex;align-items:center;
  justify-content:center;text-decoration:none;color:var(--text);
  font-size:18px;flex-shrink:0;transition:.15s;
}
.gd-back:hover{background:#e2e8f0;border-color:#94a3b8}
.gd-bar-title{font-size:16px;font-weight:900;color:var(--text);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ═══ Hero ═══════════════════════════════════════════════════ */
.gd-hero{
  position:relative;height:240px;overflow:hidden;
  background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);
}
.gd-hero-img{width:100%;height:100%;object-fit:cover;opacity:.5}
.gd-hero-grad{
  position:absolute;inset:0;
  background:linear-gradient(to top,rgba(0,0,0,.88) 0%,rgba(0,0,0,.25) 55%,transparent 100%);
}
.gd-hero-body{
  position:absolute;bottom:0;left:0;right:0;
  padding:20px 20px 22px;color:#fff;
}
.gd-hero-row{display:flex;align-items:flex-end;gap:14px}
.gd-logo{
  width:62px;height:62px;border-radius:16px;background:#fff;
  display:flex;align-items:center;justify-content:center;
  font-size:28px;flex-shrink:0;box-shadow:0 4px 18px rgba(0,0,0,.35);
  overflow:hidden;
}
.gd-hero-name{font-size:22px;font-weight:900;line-height:1.2;margin-bottom:7px}
.gd-chips{display:flex;gap:5px;flex-wrap:wrap}
.gd-chip{
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;
  background:rgba(255,255,255,.16);backdrop-filter:blur(4px);color:#fff;
  border:1px solid rgba(255,255,255,.15);
}
.gd-chip.open{background:rgba(34,197,94,.28);color:#86efac;border-color:rgba(34,197,94,.3)}
.gd-chip.closed{background:rgba(100,116,139,.28);color:#cbd5e1}
.gd-chip-rating{background:rgba(251,191,36,.3);border-color:rgba(251,191,36,.6);color:#fef08a;gap:4px;font-weight:800}

/* ═══ Content wrap ═══════════════════════════════════════════ */
.gd-wrap{max-width:800px;margin:0 auto;padding:0 12px 120px}

/* ═══ Closed state ════════════════════════════════════════════ */
.gd-closed-wrap{max-width:800px;margin:0 auto;padding:48px 20px 80px;text-align:center}
.gd-closed-svg{font-size:90px;line-height:1;margin-bottom:20px;filter:grayscale(.3)}
.gd-closed-title{font-size:22px;font-weight:900;color:var(--text);margin-bottom:10px}
.gd-closed-sub{font-size:14px;color:var(--muted);margin-bottom:20px;line-height:1.6}
.gd-closed-hours{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;
  border:1.5px solid #e2e8f0;border-radius:12px;padding:8px 18px;
  font-size:13px;font-weight:700;color:var(--muted)}

/* ═══ Category pills ══════════════════════════════════════════ */
.gd-cats{
  display:flex;gap:6px;overflow-x:auto;padding:12px 0 10px;
  position:sticky;top:62px;z-index:100;
  background:var(--bg);
}
.gd-cats::-webkit-scrollbar{display:none}
.gd-pill{
  padding:7px 16px;border-radius:999px;border:1.5px solid var(--border);
  background:var(--card);font-size:12px;font-weight:700;cursor:pointer;
  white-space:nowrap;font-family:var(--font);color:var(--muted);
  transition:.15s;flex-shrink:0;
}
.gd-pill:hover{border-color:var(--p);color:var(--p)}
.gd-pill.active{background:var(--p);border-color:var(--p);color:#fff}

/* ═══ Search ═════════════════════════════════════════════════ */
.gd-search-wrap{padding:8px 0 4px}
.gd-search{
  width:100%;padding:11px 14px 11px 40px;
  border:1.5px solid var(--border);border-radius:13px;
  font-size:13px;font-family:var(--font);outline:none;
  background:var(--card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%236b7a8d' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") 13px center/16px no-repeat;
  transition:.15s;
}
.gd-search:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(245,158,11,.1)}

/* ═══ Section ════════════════════════════════════════════════ */
.gd-sec-title{
  font-size:14px;font-weight:900;color:var(--text);
  padding:18px 0 12px;border-bottom:1px solid var(--border);
  margin-bottom:10px;display:flex;align-items:center;gap:8px;
}
.gd-sec-cnt{
  font-size:11px;font-weight:700;color:var(--muted);
  background:var(--bg);padding:2px 8px;border-radius:999px;
}

/* ═══ Product card ═══════════════════════════════════════════ */
.gd-prod{
  display:flex;gap:12px;background:var(--card);
  border-radius:var(--radius);padding:14px;
  margin-bottom:8px;cursor:pointer;
  border:1.5px solid transparent;transition:all .15s;position:relative;
}
.gd-prod:hover{border-color:var(--p);box-shadow:0 2px 14px rgba(245,158,11,.12);transform:translateY(-1px)}
.gd-prod.oos{opacity:.5;cursor:default}
.gd-prod.oos:hover{border-color:transparent;transform:none;box-shadow:none}
.gd-pinfo{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}
.gd-pname{font-size:14px;font-weight:800;color:var(--text);line-height:1.3}
.gd-pdesc{font-size:12px;color:var(--muted);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.gd-pfooter{display:flex;align-items:center;gap:8px;margin-top:4px}
.gd-price{font-size:16px;font-weight:900;color:var(--text)}
.gd-compare{font-size:12px;color:#94a3b8;text-decoration:line-through}
.gd-img{
  width:94px;height:94px;border-radius:12px;object-fit:cover;
  flex-shrink:0;background:linear-gradient(135deg,#fef3c7,#fde68a);
  display:flex;align-items:center;justify-content:center;font-size:32px;
}
.gd-plus{
  position:absolute;bottom:12px;right:12px;
  width:34px;height:34px;border-radius:50%;
  background:var(--p);color:#fff;border:none;
  font-size:22px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:.15s;font-family:var(--font);
  box-shadow:0 3px 10px rgba(245,158,11,.4);
}
.gd-plus:hover{background:var(--pd);transform:scale(1.12)}
.oos .gd-plus{display:none}
.gd-oos-tag{font-size:10px;font-weight:700;background:var(--r);color:#fff;padding:2px 7px;border-radius:999px;margin-left:5px}
.gd-pop-tag{font-size:10px;font-weight:700;background:#ede9fe;color:#6d28d9;padding:2px 7px;border-radius:999px;margin-left:5px}

/* ═══ Floating cart bar ══════════════════════════════════════ */
.gd-float{
  position:fixed;bottom:0;left:0;right:0;
  z-index:9000;padding:10px 16px 20px;
  background:linear-gradient(to top,#fff 55%,rgba(255,255,255,0));
  pointer-events:none;
}
.gd-float-btn{
  width:100%;max-width:600px;margin:0 auto;display:flex;
  padding:16px 20px;background:var(--p);color:#fff;
  border:none;border-radius:17px;font-size:15px;font-weight:800;
  cursor:pointer;font-family:var(--font);
  align-items:center;justify-content:space-between;
  box-shadow:0 8px 28px rgba(245,158,11,.45);transition:.15s;
  pointer-events:all;
}
.gd-float-btn:active{transform:scale(.97)}
.gd-float-l{display:flex;align-items:center;gap:10px}
.gd-float-badge{
  background:rgba(255,255,255,.25);min-width:26px;height:26px;
  border-radius:999px;font-size:13px;font-weight:900;padding:0 6px;
  display:none;align-items:center;justify-content:center;
}
.gd-float-badge.has-items{display:flex}
.gd-float-tot{font-size:16px;font-weight:900;opacity:0;transition:.15s}
.gd-float-tot.has-items{opacity:1}

/* ═══ Modal ══════════════════════════════════════════════════ */
.gd-modal-bg{
  position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;
  display:none;align-items:flex-end;justify-content:center;
  backdrop-filter:blur(3px);
}
.gd-modal-bg.show{display:flex}
.gd-modal{
  background:#fff;border-radius:24px 24px 0 0;
  width:100%;max-width:560px;max-height:88vh;overflow-y:auto;
  padding:0 0 28px;animation:slideUp .22s ease;
}
@keyframes slideUp{from{transform:translateY(60px);opacity:0}to{transform:translateY(0);opacity:1}}
.gd-mhead{
  padding:20px 22px 14px;display:flex;align-items:flex-start;
  justify-content:space-between;gap:12px;
  position:sticky;top:0;background:#fff;z-index:1;
  border-bottom:1px solid #f1f5f9;
}
.gd-mname{font-size:19px;font-weight:900;color:var(--text)}
.gd-mdesc{font-size:13px;color:var(--muted);margin-top:3px}
.gd-mclose{
  width:32px;height:32px;border-radius:50%;background:var(--bg);
  border:none;font-size:18px;cursor:pointer;color:var(--muted);
  display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.15s;
}
.gd-mclose:hover{background:#e2e8f0;color:var(--text)}
.gd-mbody{padding:0 22px}
.gd-mgrp{margin-top:16px}
.gd-mgrp-title{
  font-size:12px;font-weight:800;color:var(--muted);
  text-transform:uppercase;letter-spacing:.5px;
  margin-bottom:8px;display:flex;align-items:center;gap:8px;
}
.gd-mreq{background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:999px;font-size:10px}
.gd-mopt{
  display:flex;align-items:center;justify-content:space-between;
  padding:11px 14px;border:1.5px solid var(--border);border-radius:12px;
  cursor:pointer;margin-bottom:6px;transition:.15s;gap:10px;
}
.gd-mopt:hover{border-color:var(--p)}
.gd-mopt.sel{border-color:var(--p);background:#fffbeb}
.gd-mopt input{display:none}
.gd-mopt-name{font-size:13px;font-weight:600;color:var(--text)}
.gd-mopt-price{font-size:12px;font-weight:800;color:var(--p)}
.gd-mfoot{
  padding:16px 22px 0;position:sticky;bottom:0;background:#fff;
  border-top:1px solid #f1f5f9;margin-top:8px;
}
.gd-mqrow{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.gd-mqtot{font-size:18px;font-weight:900;color:var(--text);margin-left:auto}
.gd-qbtn{
  width:30px;height:30px;border-radius:50%;border:1.5px solid var(--border);
  background:#fff;cursor:pointer;font-size:17px;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  font-family:var(--font);transition:.15s;color:var(--text);
}
.gd-qbtn:hover{border-color:var(--p);color:var(--p);background:#fffbeb}
.gd-qbtn.minus:hover{border-color:var(--r);color:var(--r);background:#fef2f2}
.gd-qnum{font-size:14px;font-weight:800;min-width:20px;text-align:center}
.gd-add-btn{
  width:100%;padding:15px;background:var(--p);color:#fff;
  border:none;border-radius:13px;font-size:15px;font-weight:800;
  cursor:pointer;font-family:var(--font);transition:.15s;
  box-shadow:0 4px 16px rgba(245,158,11,.35);
}
.gd-add-btn:hover{background:var(--pd)}
.gd-add-btn:disabled{background:#fcd34d;cursor:not-allowed}
</style>

<?php /* ── Sticky top bar ── */ ?>
<div class="gd-bar">
  <a href="<?= e($base).'/'.$slug?>" class="gd-back">←</a>
  <div class="gd-bar-title"><?= e($vendor['name'])?></div>
</div>

<?php /* ── Hero ── */ ?>
<div class="gd-hero">
  <?php if(!empty($vendor['cover_image'])): ?>
  <img src="<?= e($vendor['cover_image'])?>" class="gd-hero-img" alt="" onerror="this.style.display='none'">
  <?php endif ?>
  <div class="gd-hero-grad"></div>
  <div class="gd-hero-body">
    <div class="gd-hero-row">
      <div class="gd-logo">
        <?php if(!empty($vendor['logo'])): ?>
        <img src="<?= e($vendor['logo'])?>" style="width:100%;height:100%;object-fit:cover;border-radius:14px;display:block" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:28px"><?= $catEmoji?></span>
        <?php else: ?>
        <?= $catEmoji?>
        <?php endif ?>
      </div>
      <div>
        <div class="gd-hero-name">
          <?= e($vendor['name'])?>
          <?php if(!empty($vendor['menu_size'])): ?>
          <span style="font-size:13px;font-weight:500;opacity:.7;margin-left:8px">· <?= e($vendor['menu_size'])?></span>
          <?php endif ?>
        </div>
        <div class="gd-chips">
          <span class="gd-chip <?= $isOpen?'open':'closed'?>"><?= $isOpen?'✓ Open':'✕ Closed'?></span>
          <?php $r=(float)($vendor['rating']??0); ?>
          <span class="gd-chip gd-chip-rating">
            ★ <?= $r>0 ? number_format($r,1) : 'New'?>
          </span>
          <span class="gd-chip">🛵 <?= number_format((float)$vendor['delivery_fee'],2).$sym?></span>
          <span class="gd-chip">⏱ ~<?= (int)$vendor['prep_time_min']?>min</span>
          <?php if($vendor['min_order']>0): ?><span class="gd-chip">Min <?= number_format((float)$vendor['min_order'],0).$sym?></span><?php endif ?>
          <?php foreach(array_slice(array_filter(array_map('trim', explode(',', $vendor['cuisine_tags']??''))),0,3) as $tag): ?>
          <span class="gd-chip"><?= e($tag)?></span>
          <?php endforeach ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php /* ── Closed state ── */ ?>
<?php if(!$isOpen): ?>
<div class="gd-closed-wrap">
  <div class="gd-closed-svg">🔒</div>
  <div class="gd-closed-title">სამწუხაროდ ობიექტი დაკეტილია</div>
  <div class="gd-closed-sub">ამ მომენტში შეკვეთების მიღება შეჩერებულია.<br>გთხოვთ მოგვიანებით სცადოთ.</div>
  <?php if(!empty($vendor['open_time']) && !empty($vendor['close_time'])): ?>
  <div class="gd-closed-hours">🕐 სამუშაო საათები: <?= e($vendor['open_time'])?> – <?= e($vendor['close_time'])?></div>
  <?php endif ?>
</div>
<?php else: ?>

<?php /* ── Content area ── */ ?>
<div class="gd-wrap">

  <?php /* Category pills */ ?>
  <?php if($menu): ?>
  <div class="gd-cats" id="catNav">
    <?php foreach($menu as $i => $g): ?>
    <button class="gd-pill <?= $i===0?'active':''?>"
            data-target="sec_<?= (int)$g['category']['id']?>"
            onclick="scrollToCat('sec_<?= (int)$g['category']['id']?>',this)">
      <?= e($g['category']['name'])?>
    </button>
    <?php endforeach ?>
  </div>
  <?php endif ?>

  <?php /* Search */ ?>
  <div class="gd-search-wrap">
    <input class="gd-search" id="searchInput" type="text" placeholder="პროდუქტის ძიება..." oninput="filterProds(this.value)">
  </div>

  <?php /* Products */ ?>
  <?php foreach($menu as $g): ?>
  <div id="sec_<?= (int)$g['category']['id']?>" data-sec="<?= (int)$g['category']['id']?>">
    <div class="gd-sec-title">
      <?= e($g['category']['name'])?>
      <span class="gd-sec-cnt"><?= count($g['products'])?></span>
    </div>
    <?php foreach($g['products'] as $p):
      $oos = !$p['in_stock'];
    ?>
    <div class="gd-prod <?= $oos?'oos':''?>"
         data-name="<?= e(mb_strtolower($p['name']))?>"
         onclick="<?= !$oos ? 'openProd('.e(json_encode($p)).')' : '' ?>">
      <div class="gd-pinfo">
        <div class="gd-pname">
          <?= e($p['name'])?>
          <?php if($oos): ?><span class="gd-oos-tag">Out of stock</span><?php endif ?>
          <?php if(!$oos && $p['is_featured']): ?><span class="gd-pop-tag">⭐ Popular</span><?php endif ?>
        </div>
        <?php if($p['description']): ?><div class="gd-pdesc"><?= e($p['description'])?></div><?php endif ?>
        <div class="gd-pfooter">
          <span class="gd-price"><?= number_format((float)$p['price'],2).$sym?></span>
          <?php if(!empty($p['compare_price'])&&(float)$p['compare_price']>(float)$p['price']): ?>
          <span class="gd-compare"><?= number_format((float)$p['compare_price'],2).$sym?></span>
          <?php endif ?>
        </div>
      </div>
      <?php if(!empty($p['image'])): ?>
      <div class="gd-img" style="background:url(<?= e($p['image'])?>)center/cover;font-size:0"></div>
      <?php else: ?>
      <div class="gd-img"><?= $catEmoji?></div>
      <?php endif ?>
      <?php if(!$oos): ?>
      <button class="gd-plus" onclick="event.stopPropagation();openProd(<?= e(json_encode($p)) ?>)">＋</button>
      <?php endif ?>
    </div>
    <?php endforeach ?>
  </div>
  <?php endforeach ?>

</div>

<?php endif /* !$isOpen */ ?>

<?php /* ── Floating cart bar — only when open ── */ ?>
<?php if($isOpen): ?>
<div class="gd-float" id="floatBar">
  <button class="gd-float-btn" onclick="goCart()">
    <div class="gd-float-l">
      <div class="gd-float-badge" id="floatCnt">0</div>
      <span>შეკვეთა</span>
    </div>
    <span class="gd-float-tot" id="floatTot">—</span>
  </button>
</div>
<?php endif /* $isOpen */ ?>

<?php /* ── Modifier modal ── */ ?>
<div class="gd-modal-bg" id="modBg" onclick="if(this===event.target)closeMod()">
  <div class="gd-modal">
    <div class="gd-mhead">
      <div style="flex:1;min-width:0">
        <div class="gd-mname" id="mName"></div>
        <div class="gd-mdesc" id="mDesc"></div>
      </div>
      <button class="gd-mclose" onclick="closeMod()">✕</button>
    </div>
    <div class="gd-mbody" id="mBody"></div>
    <div class="gd-mfoot">
      <div class="gd-mqrow">
        <button class="gd-qbtn minus" onclick="chQty(-1)">−</button>
        <span class="gd-qnum" id="mQty">1</span>
        <button class="gd-qbtn" onclick="chQty(1)">＋</button>
        <span class="gd-mqtot" id="mTot"></span>
      </div>
      <button class="gd-add-btn" id="addBtn" onclick="addToCart()">კალათაში დამატება</button>
    </div>
  </div>
</div>

<script>
var BASE        = <?= json_encode($base) ?>;
var SLUG        = <?= json_encode($slug) ?>;
var SYM         = <?= json_encode($sym) ?>;
var MIN_ORDER   = <?= (float)($vendor['min_order']??0) ?>;
var FREE_THRESH = <?= (float)($vendor['free_delivery_threshold']??0) ?>;
var curProd = null, curQty = 1;

/* boot */
fetch(BASE+'/api/delivery/cart').then(r=>r.json()).then(d=>{if(d.cart)syncBar(d.cart);});

/* scroll spy */
var secs = [...document.querySelectorAll('[data-sec]')];
if(secs.length){
  var io = new IntersectionObserver(entries=>{
    entries.forEach(e=>{
      if(e.isIntersecting){
        var id = e.target.getAttribute('data-sec');
        document.querySelectorAll('.gd-pill').forEach(p=>p.classList.remove('active'));
        var pill = document.querySelector('.gd-pill[data-target="sec_'+id+'"]');
        if(pill){pill.classList.add('active');pill.scrollIntoView({behavior:'smooth',inline:'center',block:'nearest'});}
      }
    });
  },{threshold:.2});
  secs.forEach(s=>io.observe(s));
}

function scrollToCat(id,btn){
  var el=document.getElementById(id);
  if(el){var y=el.getBoundingClientRect().top+window.scrollY-120;window.scrollTo({top:y,behavior:'smooth'});}
  document.querySelectorAll('.gd-pill').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
}

function filterProds(q){
  q=q.toLowerCase().trim();
  document.querySelectorAll('.gd-prod').forEach(p=>{
    p.style.display=(!q||p.dataset.name.includes(q))?'':'none';
  });
  secs.forEach(s=>{
    var vis=[...s.querySelectorAll('.gd-prod')].some(p=>p.style.display!=='none');
    s.style.display=vis?'':'none';
  });
}

/* product modal */
function openProd(p){
  curProd=p; curQty=1;
  document.getElementById('mName').textContent=p.name;
  document.getElementById('mDesc').textContent=p.description||'';
  var body=document.getElementById('mBody'); body.innerHTML='';
  var hasOpts=false;

  // Modifier groups
  if(p.modifier_groups&&p.modifier_groups.length){
    hasOpts=true;
    p.modifier_groups.forEach(g=>{
      var div=document.createElement('div');div.className='gd-mgrp';
      div.innerHTML='<div class="gd-mgrp-title">'+esc(g.name)+(g.required?'<span class="gd-mreq">Required</span>':'')+'</div>';
      g.modifiers.forEach(m=>{
        var inp=g.max_select>1?`<input type="checkbox" value="${m.id}" onchange="onMod(this)">`:`<input type="radio" name="mg_${g.id}" value="${m.id}" onchange="onMod(this)">`;
        var lbl=document.createElement('label');lbl.className='gd-mopt';
        lbl.dataset.price=m.price; // modifier surcharge
        lbl.innerHTML=`<div>${inp}<span class="gd-mopt-name">${esc(m.name)}</span></div>`+(m.price>0?`<span class="gd-mopt-price">+${parseFloat(m.price).toFixed(2)}${SYM}</span>`:'');
        lbl.onclick=e=>{if(e.target.tagName==='INPUT')return;lbl.querySelector('input').click();};
        div.appendChild(lbl);
      });
      body.appendChild(div);
    });
  }

  // Combo groups (customer picks from catalog products — or sees always-included items)
  if(p.combos&&p.combos.length){
    p.combos.forEach(c=>{
      if(!c.products||!c.products.length) return;
      var comboType = c.type || 'choice';
      var div=document.createElement('div');div.className='gd-mgrp';

      if(comboType === 'included'){
        // ── Included type: just show "always included" badge list, no interaction ──
        hasOpts=true; // show modal even if only combos
        var reqBadge='<span style="background:#dcfce7;color:#166534;font-size:10px;font-weight:800;padding:2px 8px;border-radius:999px;margin-left:6px">✅ ყოველთვის შედის</span>';
        div.innerHTML='<div class="gd-mgrp-title">'+esc(c.name)+reqBadge+'</div>';
        var incList=document.createElement('div');incList.style.cssText='display:flex;flex-wrap:wrap;gap:6px;margin-bottom:4px';
        c.products.forEach(cp=>{
          var chip=document.createElement('span');
          chip.style.cssText='background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:7px 13px;font-size:13px;font-weight:700;color:#166534;display:flex;align-items:center;gap:5px';
          chip.innerHTML='<span>✓</span><span>'+esc(cp.name)+'</span>';
          incList.appendChild(chip);
        });
        div.appendChild(incList);
      } else {
        // ── Choice / Size type: radio/checkbox selection ──
        hasOpts=true;
        var reqSpan=c.required?'<span class="gd-mreq">Required</span>':'<span style="font-size:10px;color:var(--muted)">(optional)</span>';
        div.innerHTML='<div class="gd-mgrp-title">'+esc(c.name)+reqSpan+'</div>';
        c.products.forEach((cp,idx)=>{
          var checked=(c.required&&idx===0)?'checked':'';
          var inp=c.max_select>1
            ?`<input type="checkbox" name="combo_${c.id}" value="${cp.id}" ${checked} onchange="onMod(this)">`
            :`<input type="radio"    name="combo_${c.id}" value="${cp.id}" ${checked} onchange="onMod(this)">`;
          var lbl=document.createElement('label');lbl.className='gd-mopt'+(checked?' sel':'');
          lbl.dataset.price=cp.price_modifier||0; // size combos may have price_modifier
          lbl.dataset.comboId=c.id;
          lbl.dataset.comboType=comboType;
          var priceHtml=(parseFloat(cp.price_modifier||0)!==0)
            ?`<span class="gd-mopt-price">${parseFloat(cp.price_modifier)>0?'+':''}${parseFloat(cp.price_modifier).toFixed(2)}${SYM}</span>`
            :'';
          lbl.innerHTML=`<div>${inp}<span class="gd-mopt-name">${esc(cp.name)}</span></div>${priceHtml}`;
          lbl.onclick=e=>{if(e.target.tagName==='INPUT')return;lbl.querySelector('input').click();};
          div.appendChild(lbl);
        });
      }
      body.appendChild(div);
    });
  }

  if(!hasOpts){
    body.innerHTML='<div style="padding:14px 0;color:var(--muted);font-size:13px">No customisation options</div>';
  }
  updMTot();
  document.getElementById('mQty').textContent=1;
  document.getElementById('modBg').classList.add('show');
  document.body.style.overflow='hidden';
}

function onMod(inp){
  var lbl=inp.closest('.gd-mopt');
  if(inp.type==='radio') document.querySelectorAll(`[name="${inp.name}"]`).forEach(i=>i.closest('.gd-mopt').classList.remove('sel'));
  inp.checked?lbl.classList.add('sel'):lbl.classList.remove('sel');
  updMTot();
}
// Surcharge = modifier group prices + size combo price_modifiers
function getSurcharge(){
  var s=0;
  document.querySelectorAll('#mBody .gd-mopt input:checked').forEach(i=>{
    var lbl=i.closest('.gd-mopt');
    // All options store price in dataset.price (0 for plain choice/included, actual value for modifiers & size combos)
    s+=parseFloat(lbl.dataset.price||0);
  });
  return s;
}
// Modifier IDs only (not combo selections)
function getMods(){
  var m=[];
  document.querySelectorAll('#mBody .gd-mopt input:checked').forEach(i=>{
    if(!i.closest('.gd-mopt').dataset.comboId) m.push(parseInt(i.value));
  });
  return m;
}
// Combo selections: {comboId: [productId, ...]} — only for 'choice' and 'size' types (not 'included')
function getCombos(){
  var c={};
  document.querySelectorAll('#mBody .gd-mopt input:checked').forEach(i=>{
    var lbl=i.closest('.gd-mopt');
    var cid=lbl.dataset.comboId;
    if(cid && lbl.dataset.comboType!=='included'){
      if(!c[cid])c[cid]=[]; c[cid].push(parseInt(i.value));
    }
  });
  return c;
}
function updMTot(){var t=(parseFloat(curProd.price||0)+getSurcharge())*curQty;document.getElementById('mTot').textContent=t.toFixed(2)+SYM;}
function chQty(d){curQty=Math.max(1,curQty+d);document.getElementById('mQty').textContent=curQty;updMTot();}

function addToCart(){
  if(!curProd)return;
  var btn=document.getElementById('addBtn');btn.disabled=true;btn.textContent='...';
  fetch(BASE+'/api/delivery/cart/add',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({product_id:curProd.id,quantity:curQty,modifiers:getMods(),combos:getCombos()})
  }).then(r=>r.json()).then(d=>{
    btn.disabled=false;btn.textContent='კალათაში დამატება';
    if(d.error==='clear_cart'){
      if(confirm('სხვა ვენდორის პროდუქტი კალათაშია. გაასუფთავო?')){
        fetch(BASE+'/api/delivery/cart/clear',{method:'POST'}).then(()=>addToCart());
      }
      return;
    }
    if(d.ok){syncBar(d.cart);closeMod();}
  }).catch(()=>{btn.disabled=false;btn.textContent='კალათაში დამატება';});
}

function closeMod(){document.getElementById('modBg').classList.remove('show');document.body.style.overflow='';}

/* floating bar — always visible */
function syncBar(c){
  var hasItems = c.items && c.items.length > 0;
  var badge = document.getElementById('floatCnt');
  var tot   = document.getElementById('floatTot');
  if(hasItems){
    badge.textContent = c.count;
    badge.classList.add('has-items');
    tot.textContent = parseFloat(c.total).toFixed(2)+SYM;
    tot.classList.add('has-items');
  } else {
    badge.classList.remove('has-items');
    tot.classList.remove('has-items');
  }
}

function goCart(){window.location.href=BASE+'/'+SLUG+'/cart';}
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
</script>
