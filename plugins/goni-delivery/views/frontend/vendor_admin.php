<!DOCTYPE html>
<html lang="ka">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($vendor['name']) ?> · Admin</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f1f5f9;--surface:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;
      --amber:#f59e0b;--green:#22c55e;--blue:#3b82f6;--red:#ef4444;--purple:#8b5cf6}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text)}
a,a:hover{text-decoration:none}

/* Topbar */
.topbar{background:linear-gradient(135deg,#1e293b,#334155);color:#fff;padding:14px 20px;
        display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:200}
.topbar-back{display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.7);
             padding:6px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.2);cursor:pointer;transition:all .15s}
.topbar-back:hover{background:rgba(255,255,255,.1);color:#fff}
.topbar-title{font-size:16px;font-weight:900;flex:1}
.topbar-badge{background:#f59e0b;color:#fff;font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px}

/* Tab bar */
.tab-bar{background:var(--surface);border-bottom:1.5px solid var(--border);
         display:flex;overflow-x:auto;scrollbar-width:none;position:sticky;top:54px;z-index:150}
.tab-bar::-webkit-scrollbar{display:none}
.tab-btn{flex-shrink:0;padding:13px 20px;font-size:13px;font-weight:700;color:var(--muted);
         border:none;background:none;cursor:pointer;font-family:inherit;border-bottom:3px solid transparent;
         transition:all .15s;white-space:nowrap}
.tab-btn.active{color:var(--amber);border-bottom-color:var(--amber)}

/* Layout */
.main{max-width:900px;margin:0 auto;padding:24px 16px 60px}
.tab-pane{display:none}
.tab-pane.active{display:block}
.section{background:var(--surface);border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:20px;overflow:hidden}
.section-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.section-head h2{font-size:15px;font-weight:900}
.section-body{padding:20px}

/* Form */
.fg{margin-bottom:14px}
.fg:last-child{margin-bottom:0}
.fg label{display:block;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.fg input,.fg select,.fg textarea{width:100%;border:2px solid var(--border);border-radius:10px;padding:9px 12px;font-size:14px;font-family:inherit;outline:none;background:#fff;transition:border-color .12s}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:var(--amber)}
.fg textarea{resize:vertical;min-height:72px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
@media(max-width:600px){.grid-2,.grid-3{grid-template-columns:1fr}}
.save-btn{background:var(--amber);color:#fff;border:none;padding:11px 24px;border-radius:10px;font-size:14px;font-weight:800;cursor:pointer;font-family:inherit;transition:opacity .12s;display:inline-flex;align-items:center;gap:8px}
.save-btn:hover{opacity:.88}
.save-btn:disabled{opacity:.5}

/* Days checkboxes */
.days-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}
.day-cb{display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer}

/* Image upload */
.img-pair{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:500px){.img-pair{grid-template-columns:1fr}}
.img-box{border:2px dashed var(--border);border-radius:14px;padding:16px;text-align:center;position:relative;cursor:pointer;transition:border-color .15s}
.img-box:hover{border-color:var(--amber)}
.img-box.has-img{border-style:solid;border-color:#e2e8f0}
.img-preview{width:100%;height:120px;object-fit:cover;border-radius:10px;margin-bottom:10px;display:none}
.img-preview.show{display:block}
.img-placeholder{font-size:36px;margin-bottom:8px}
.img-label{font-size:13px;font-weight:700;color:var(--text);margin-bottom:3px}
.img-sub{font-size:11px;color:var(--muted)}
.img-input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.img-progress{display:none;position:absolute;bottom:0;left:0;right:0;height:3px;background:var(--amber);border-radius:0 0 12px 12px;animation:progbar 1.5s ease-in-out infinite}
@keyframes progbar{0%{width:0%}100%{width:100%}}

/* Branches */
.branch-list{display:flex;flex-direction:column;gap:10px;margin-bottom:14px}
.branch-item{background:var(--bg);border-radius:12px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;border:1.5px solid var(--border)}
.branch-item.inactive-branch{opacity:.55}
.branch-dot{width:10px;height:10px;border-radius:50%;background:var(--green);flex-shrink:0;margin-top:5px}
.branch-dot.off{background:#e2e8f0}
.branch-info{flex:1;min-width:0}
.branch-name{font-size:14px;font-weight:800}
.branch-addr{font-size:12px;color:var(--muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.branch-phone{font-size:12px;color:var(--muted)}
.branch-coords{font-size:10px;font-family:monospace;color:var(--blue);margin-top:3px}
.add-branch-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;
                border:2px dashed var(--border);border-radius:12px;background:none;cursor:pointer;
                font-size:13px;font-weight:700;color:var(--muted);width:100%;font-family:inherit;transition:all .12s}
.add-branch-btn:hover{border-color:var(--amber);color:var(--amber)}

/* Menu layout */
.menu-layout{display:grid;grid-template-columns:200px 1fr;gap:16px;min-height:400px}
@media(max-width:660px){.menu-layout{grid-template-columns:1fr}}
.cats-panel{background:var(--surface);border-radius:14px;overflow:hidden;border:1.5px solid var(--border)}
.cats-head{padding:12px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.cats-head span{font-size:13px;font-weight:800}
.cat-item{padding:10px 14px;cursor:pointer;font-size:13px;font-weight:700;border-left:3px solid transparent;transition:all .12s;display:flex;justify-content:space-between;align-items:center}
.cat-item:hover{background:var(--bg)}
.cat-item.active{background:#fffbeb;border-left-color:var(--amber);color:var(--amber)}
.cat-count{font-size:10px;font-weight:700;background:var(--border);color:var(--muted);padding:1px 7px;border-radius:999px}
.cat-item.active .cat-count{background:#fed7aa;color:#92400e}
.prods-panel{background:var(--surface);border-radius:14px;overflow:hidden;border:1.5px solid var(--border)}
.prods-head{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.prods-head span{font-size:13px;font-weight:800}
.prods-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;padding:12px}
.prod-card{border:1.5px solid var(--border);border-radius:12px;overflow:hidden;background:var(--bg);cursor:pointer;transition:border-color .12s}
.prod-card:hover{border-color:var(--amber)}
.prod-card.out-of-stock{opacity:.5}
.prod-img{width:100%;height:100px;object-fit:cover;display:block}
.prod-img-ph{width:100%;height:100px;background:linear-gradient(135deg,#f0f0f0,#e8e8e8);display:flex;align-items:center;justify-content:center;font-size:30px;color:var(--muted)}
.prod-info{padding:10px 10px 8px}
.prod-name{font-size:13px;font-weight:800;margin-bottom:3px}
.prod-desc{font-size:11px;color:var(--muted);margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.prod-bottom{display:flex;align-items:center;justify-content:space-between}
.prod-price{font-size:14px;font-weight:900;color:var(--amber)}
.prod-stock-badge{font-size:10px;padding:2px 7px;border-radius:6px;font-weight:700}
.prod-stock-badge.in{background:#dcfce7;color:#166534}
.prod-stock-badge.out{background:#fee2e2;color:#991b1b}
.menu-empty{padding:40px;text-align:center;color:var(--muted);font-size:13px}
.add-item-btn{background:var(--amber);color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:800;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:5px}
.add-item-btn:hover{opacity:.88}
.icon-btn{background:var(--bg);border:1.5px solid var(--border);width:28px;height:28px;border-radius:8px;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .12s}
.icon-btn:hover{border-color:var(--amber);color:var(--amber)}

/* Offers */
.offers-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.offer-card{border:1.5px solid var(--border);border-radius:14px;padding:14px 16px;background:var(--surface);cursor:pointer;transition:border-color .12s}
.offer-card:hover{border-color:var(--amber)}
.offer-card.inactive-offer{opacity:.6}
.offer-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px}
.offer-code{font-size:14px;font-weight:900;font-family:monospace;color:var(--text)}
.offer-toggle{position:relative;width:42px;height:24px;flex-shrink:0}
.offer-toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#e2e8f0;border-radius:999px;transition:.2s}
.toggle-slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s}
.offer-toggle input:checked+.toggle-slider{background:var(--green)}
.offer-toggle input:checked+.toggle-slider:before{transform:translateX(18px)}
.offer-type-pill{display:inline-block;font-size:10px;font-weight:800;padding:2px 8px;border-radius:6px;margin-bottom:6px}
.offer-details{font-size:12px;color:var(--muted);line-height:1.6}
.offer-dates{font-size:11px;color:var(--blue);margin-top:5px}
.offer-empty{padding:40px;text-align:center;color:var(--muted);font-size:13px}
.offers-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}

/* Templates */
.tpl-topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.tpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.tpl-card{border:1.5px solid var(--border);border-radius:14px;padding:14px 16px;background:var(--surface);cursor:pointer;transition:border-color .12s}
.tpl-card:hover{border-color:var(--amber)}
.tpl-card-head{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.tpl-card-name{font-size:14px;font-weight:900;flex:1}
.tpl-items{font-size:12px;color:var(--muted);line-height:1.7;margin-bottom:10px}
.tpl-apply-btn{width:100%;padding:8px;background:#f0fdf4;color:#166534;border:1.5px solid #bbf7d0;border-radius:8px;font-size:12px;font-weight:800;cursor:pointer;font-family:inherit;transition:.15s}
.tpl-apply-btn:hover{background:#dcfce7;border-color:#86efac}
/* Quick-apply chips in product modal */
.pm-tpl-chip{padding:5px 12px;border:1.5px solid var(--border);border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;background:var(--surface);color:var(--text);transition:.15s;white-space:nowrap;display:flex;align-items:center;gap:5px}
.pm-tpl-chip:hover{border-color:var(--amber);background:#fffbeb;color:var(--amber)}
/* Combos */
.combos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.combo-card{border:1.5px solid var(--border);border-radius:14px;padding:14px 16px;background:var(--surface);cursor:pointer;transition:border-color .12s}
.combo-card:hover{border-color:var(--amber)}
.combo-card.inactive-combo{opacity:.6}
.combo-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px}
.combo-name{font-size:14px;font-weight:900;color:var(--text)}
.combo-pill{font-size:10px;font-weight:800;padding:2px 8px;border-radius:6px}
.combo-products{font-size:12px;color:var(--muted);line-height:1.6;margin-top:4px}
.combo-empty{padding:40px;text-align:center;color:var(--muted);font-size:13px}
.combos-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
/* Combo product checklist in modal */
.combo-prod-list{display:flex;flex-direction:column;gap:6px;max-height:260px;overflow-y:auto;padding:2px 0}
.combo-prod-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:border-color .12s}
.combo-prod-item:hover{border-color:var(--amber)}
.combo-prod-item.selected{border-color:var(--amber);background:#fffbeb}
.combo-prod-item input{width:16px;height:16px;accent-color:var(--amber);cursor:pointer;flex-shrink:0}
.combo-prod-item-name{flex:1;font-size:13px;font-weight:700}
.combo-prod-item-price{font-size:12px;font-weight:800;color:var(--amber)}
/* Product combos section (inside product modal) */
.pm-combo-section{border-top:1.5px solid var(--border);padding-top:12px;margin-top:4px}
.pm-combo-row{display:flex;align-items:center;gap:10px;padding:8px 12px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;margin-bottom:6px;transition:border-color .12s}
.pm-combo-row:hover{border-color:var(--amber)}
.pm-combo-row.on{border-color:var(--amber);background:#fffbeb}
.pm-combo-row input{width:15px;height:15px;accent-color:var(--amber);cursor:pointer;flex-shrink:0}
.pm-combo-row-name{flex:1;font-size:13px;font-weight:700}
.pm-combo-row-sub{font-size:11px;color:var(--muted)}

/* Combo meal builder sections */
.cmb-section{border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:6px}
.cmb-section-head{background:var(--bg);padding:10px 12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.cmb-section-icon{font-size:18px;flex-shrink:0}
.cmb-section-info{flex:1;min-width:0}
.cmb-section-title{font-size:13px;font-weight:800}
.cmb-section-sub{font-size:11px;color:var(--muted)}
.cmb-section-count{font-size:11px;font-weight:900;background:var(--amber);color:#fff;min-width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0 4px;flex-shrink:0}
.cmb-section-count.zero{background:var(--border);color:var(--muted)}
.cmb-prod-list{padding:6px 8px;max-height:200px;overflow-y:auto;border-top:1px solid var(--border)}
/* Category filter select inside section head */
.cmb-cat-filter{font-size:11px;border:1.5px solid var(--border);border-radius:8px;padding:3px 6px;background:var(--card);color:var(--text);font-family:inherit;cursor:pointer;max-width:130px;flex-shrink:0}
/* Extra section: editable name input */
.cmb-extra-name-input{font-size:13px;font-weight:800;border:none;background:transparent;color:var(--text);font-family:inherit;outline:none;min-width:80px;flex:1;padding:0}
.cmb-extra-name-input:focus{border-bottom:1.5px solid var(--amber)}
/* Remove section button */
.cmb-remove-btn{width:24px;height:24px;border-radius:6px;border:1.5px solid var(--border);background:var(--card);cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.15s}
.cmb-remove-btn:hover{background:#fef2f2;border-color:var(--red);color:var(--red)}
/* Add extra button */
.cmb-add-extra-btn{width:100%;padding:10px;margin:4px 0 8px;border:2px dashed var(--border);border-radius:10px;background:none;color:var(--muted);font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:.15s}
.cmb-add-extra-btn:hover{border-color:var(--amber);color:var(--amber);background:#fffbeb}
/* Combo meal card extra info */
.cmb-meal-row{font-size:11px;color:var(--muted);margin-top:3px;line-height:1.5}

/* ── Modifier groups ── */
.mod-add-group-btns{display:flex;gap:8px;margin-bottom:6px}
.mod-add-group-btn{flex:1;padding:9px 6px;border:2px dashed var(--border);border-radius:10px;background:none;color:var(--muted);font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:.15s;text-align:center;line-height:1.4}
.mod-add-group-btn:hover{border-color:var(--amber);color:var(--amber);background:#fffbeb}
.mod-group{border:1.5px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:6px}
.mod-group-head{background:var(--bg);padding:9px 10px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.mod-type-badge{font-size:11px;padding:2px 7px;border-radius:20px;font-weight:800;flex-shrink:0;white-space:nowrap}
.mod-type-choice{background:#dbeafe;color:#1d4ed8}
.mod-type-exclusion{background:#fee2e2;color:#dc2626}
.mod-type-size{background:#dcfce7;color:#166534}
.mod-group-name{flex:1;border:none;background:transparent;font-size:13px;font-weight:700;font-family:inherit;outline:none;min-width:50px;color:var(--text)}
.mod-group-name:focus{border-bottom:1.5px solid var(--amber)}
.mod-group-opt{font-size:11px;color:var(--muted);display:flex;align-items:center;gap:3px;white-space:nowrap}
.mod-group-body{padding:4px 8px 2px}
.mod-item-row{display:flex;align-items:center;gap:6px;padding:3px 0;border-bottom:1px solid #f8fafc}
.mod-item-row:last-child{border-bottom:none}
.mod-item-name{flex:1;font-size:13px;border:1px solid transparent;border-radius:6px;padding:3px 6px;font-family:inherit;outline:none;background:transparent;min-width:0}
.mod-item-name:focus{border-color:var(--border);background:var(--card)}
.mod-item-price{width:64px;text-align:right;font-size:12px;border:1px solid var(--border);border-radius:6px;padding:3px 5px;font-family:inherit;outline:none}
.mod-item-del{width:20px;height:20px;border-radius:5px;border:none;background:none;cursor:pointer;font-size:13px;color:var(--muted);flex-shrink:0;padding:0;line-height:1}
.mod-item-del:hover{color:var(--red)}
.mod-add-item{font-size:12px;color:var(--amber);border:none;background:none;cursor:pointer;font-family:inherit;font-weight:700;padding:5px 0 6px;display:block}
/* Modal */
.modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;display:none;align-items:center;justify-content:center;padding:16px}
.modal-ov.open{display:flex}
.modal{background:var(--surface);border-radius:20px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.modal-head{padding:18px 20px 0;display:flex;align-items:center;justify-content:space-between}
.modal-head h3{font-size:16px;font-weight:900}
.modal-close{background:var(--bg);border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;font-family:inherit}
.modal-body{padding:14px 20px 0;display:flex;flex-direction:column;gap:12px}
.modal-footer{padding:14px 20px 20px;display:flex;gap:10px;justify-content:flex-end}
.btn-cancel{background:var(--bg);border:none;padding:10px 18px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
.btn-save{background:var(--amber);color:#fff;border:none;padding:10px 22px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
.btn-del{background:#fef2f2;color:var(--red);border:none;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-right:auto}
#branchMap{height:240px;border-radius:10px;border:1.5px solid var(--border);margin-top:6px;position:relative;z-index:0;isolation:isolate}
#branchMap .leaflet-pane,#branchMap .leaflet-top,#branchMap .leaflet-bottom{z-index:auto}
#branchMap .leaflet-control{z-index:auto}

/* Toast */
.toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:#0f172a;color:#fff;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.25);opacity:0;transform:translateY(10px);transition:all .25s;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}
.toast.err{background:var(--red)}

/* Spinner */
.spinner{display:inline-block;width:16px;height:16px;border:2.5px solid rgba(0,0,0,.15);border-top-color:var(--amber);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<?php
$v    = $vendor;
$days = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
$daysOpen = (string)($v['days_open'] ?? '1234567');
$sym = $sym ?? '₾';
?>

<!-- Topbar -->
<div class="topbar">
  <a href="<?= e($base)?>/delivery/portal/<?= e($token)?>" class="topbar-back">← Portal</a>
  <div class="topbar-title">⚙ ვენდორის ადმინი</div>
  <span class="topbar-badge">🏪 <?= e($v['name'])?></span>
</div>

<!-- Tab bar -->
<div class="tab-bar" id="tabBar">
  <button class="tab-btn active" data-tab="info">⚙ ინფო</button>
  <button class="tab-btn" data-tab="images">🖼 ფოტოები</button>
  <button class="tab-btn" data-tab="branches">📍 ობიექტები</button>
  <button class="tab-btn" data-tab="menu">🍽 მენიუ</button>
  <button class="tab-btn" data-tab="offers">🎁 შეთავაზება</button>
  <button class="tab-btn" data-tab="templates">📋 შაბლონები</button>
</div>

<div class="main">

  <!-- ═══ TAB: ინფო ═══ -->
  <div class="tab-pane active" id="pane-info">

    <div class="section">
      <div class="section-head"><span style="font-size:20px">🏪</span><h2>ძირითადი ინფო</h2></div>
      <div class="section-body">
        <div class="grid-2">
          <div class="fg">
            <label>სახელი *</label>
            <input type="text" id="pName" value="<?= e($v['name']??'')?>">
          </div>
          <div class="fg">
            <label>სტატუსი</label>
            <select id="pStatus">
              <option value="active"   <?= ($v['status']??'')==='active'  ?'selected':''?>>✓ Active</option>
              <option value="busy"     <?= ($v['status']??'')==='busy'    ?'selected':''?>>⏳ Busy</option>
              <option value="inactive" <?= ($v['status']??'')==='inactive'?'selected':''?>>✗ Inactive</option>
            </select>
          </div>
        </div>
        <div class="fg">
          <label>აღწერა</label>
          <textarea id="pDesc"><?= e($v['description']??'')?></textarea>
        </div>
        <div class="grid-2">
          <div class="fg"><label>ტელეფონი</label><input type="tel" id="pPhone" value="<?= e($v['phone']??'')?>"></div>
          <div class="fg"><label>Email</label><input type="email" id="pEmail" value="<?= e($v['email']??'')?>"></div>
        </div>
        <div class="fg">
          <label>Cuisine Tags (კომა-გამყოფი)</label>
          <input type="text" id="pTags" value="<?= e($v['cuisine_tags']??'')?>" placeholder="Georgian, Pizza, Burgers">
        </div>
        <div class="fg">
          <label>მენიუს ზომა</label>
          <input type="text" id="pMenuSize" value="<?= e($v['menu_size']??'')?>" placeholder="მაგ: 50+ დასახელება">
        </div>
        <div class="grid-3">
          <div class="fg"><label>გახსნა</label><input type="time" id="pOpen" value="<?= e($v['open_time']??'')?>"></div>
          <div class="fg"><label>დახურვა</label><input type="time" id="pClose" value="<?= e($v['close_time']??'')?>"></div>
          <div class="fg"><label>Prep Time (წთ)</label><input type="number" id="pPrep" value="<?= (int)($v['prep_time_min']??20)?>" min="1"></div>
        </div>
        <div class="fg">
          <label>სამუშაო დღეები</label>
          <div class="days-row" id="daysRow">
            <?php foreach($days as $n=>$l): ?>
            <label class="day-cb">
              <input type="checkbox" value="<?= $n?>" <?= str_contains($daysOpen,(string)$n)?'checked':''?>>
              <?= $l?>
            </label>
            <?php endforeach ?>
          </div>
        </div>
        <div class="grid-2" style="margin-top:4px">
          <div class="fg"><label>Min Order (<?= e($sym)?>)</label><input type="number" id="pMinOrder" value="<?= number_format((float)($v['min_order']??0),2)?>" min="0" step="0.01"></div>
          <div class="fg"><label>Delivery Fee (<?= e($sym)?>)</label><input type="number" id="pDeliveryFee" value="<?= number_format((float)($v['delivery_fee']??3),2)?>" min="0" step="0.01"></div>
        </div>
        <div style="margin-top:4px">
          <button class="save-btn" onclick="saveProfile()">💾 ინფოს შენახვა</button>
        </div>
      </div>
    </div>

  </div><!-- /pane-info -->

  <!-- ═══ TAB: ფოტოები ═══ -->
  <div class="tab-pane" id="pane-images">

    <div class="section">
      <div class="section-head"><span style="font-size:20px">🖼</span><h2>ფოტოები</h2></div>
      <div class="section-body">
        <div class="img-pair">
          <?php
          $coverSrc = !empty($v['cover_image']) ? e($v['cover_image']).'?v='.abs(crc32($v['cover_image'])) : '';
          $logoSrc  = !empty($v['logo'])        ? e($v['logo']).'?v='.abs(crc32($v['logo']))               : '';
          ?>
          <div class="img-box <?= $coverSrc?'has-img':''?>" id="coverBox">
            <?php if($coverSrc): ?>
            <img class="img-preview" id="coverPreview" src="<?= $coverSrc?>" alt="cover"
                 onload="this.classList.add('show')"
                 onerror="this.style.display='none'">
            <?php else: ?>
            <img class="img-preview" id="coverPreview" src="" alt="cover" style="display:none">
            <?php endif ?>
            <div class="img-placeholder">🖼</div>
            <div class="img-label">Cover Image</div>
            <div class="img-sub">1200×400px · max 5MB<br>jpg, png, webp</div>
            <input class="img-input" type="file" accept="image/*" onchange="uploadImg(this,'cover')">
            <div class="img-progress" id="coverProg"></div>
          </div>
          <div class="img-box <?= $logoSrc?'has-img':''?>" id="logoBox">
            <?php if($logoSrc): ?>
            <img class="img-preview" id="logoPreview" src="<?= $logoSrc?>" alt="logo"
                 onload="this.classList.add('show')"
                 onerror="this.style.display='none'">
            <?php else: ?>
            <img class="img-preview" id="logoPreview" src="" alt="logo" style="display:none">
            <?php endif ?>
            <div class="img-placeholder">🏪</div>
            <div class="img-label">ლოგო / Avatar</div>
            <div class="img-sub">400×400px · max 5MB<br>jpg, png, webp</div>
            <input class="img-input" type="file" accept="image/*" onchange="uploadImg(this,'logo')">
            <div class="img-progress" id="logoProg"></div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /pane-images -->

  <!-- ═══ TAB: ობიექტები ═══ -->
  <div class="tab-pane" id="pane-branches">

    <div class="section">
      <div class="section-head"><span style="font-size:20px">📍</span><h2>ობიექტები (Branches)</h2>
        <span style="font-size:12px;color:var(--muted);margin-left:auto">შეკვეთა მიდის ყველაზე ახლო ობიექტზე</span>
      </div>
      <div class="section-body">
        <div class="branch-list" id="branchList">
          <?php if(empty($branches)): ?>
          <div style="text-align:center;padding:24px;color:var(--muted);font-size:13px">
            📍 ობიექტი არ არის — დაამატე პირველი
          </div>
          <?php else: ?>
          <?php foreach($branches as $br):
            $bPin = str_pad((string)((abs(crc32('gd_branch_v1_' . (int)$br['id'])) % 9000) + 1000), 4, '0', STR_PAD_LEFT);
          ?>
          <div class="branch-item <?= !(int)$br['active']?'inactive-branch':''?>" id="branchItem_<?=(int)$br['id']?>">
            <span class="branch-dot <?= !(int)$br['active']?'off':''?>"></span>
            <div class="branch-info">
              <div class="branch-name"><?= e($br['name'])?></div>
              <?php if($br['address']): ?><div class="branch-addr">📍 <?= e($br['address'])?></div><?php endif ?>
              <?php if($br['phone']):   ?><div class="branch-phone">📞 <?= e($br['phone'])?></div><?php endif ?>
              <?php if($br['lat']&&$br['lng']): ?>
              <div class="branch-coords">🗺 <?= round((float)$br['lat'],5)?>, <?= round((float)$br['lng'],5)?></div>
              <?php endif ?>
              <div style="margin-top:5px;display:inline-flex;align-items:center;gap:6px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:8px;padding:3px 10px">
                <span style="font-size:11px;color:#92400e;font-weight:700">🔐 პორტალის PIN:</span>
                <span style="font-size:16px;font-weight:900;letter-spacing:3px;font-family:monospace;color:#b45309"><?= $bPin ?></span>
              </div>
            </div>
            <button class="save-btn" style="background:var(--bg);color:var(--text);padding:7px 12px;font-size:12px"
                    onclick="openBranchModal(<?=(int)$br['id']?>)">✏</button>
          </div>
          <?php endforeach ?>
          <?php endif ?>
        </div>
        <button class="add-branch-btn" onclick="openBranchModal(null)">＋ ობიექტის დამატება</button>
      </div>
    </div>

  </div><!-- /pane-branches -->

  <!-- ═══ TAB: მენიუ ═══ -->
  <div class="tab-pane" id="pane-menu">

    <div id="menuLoading" style="text-align:center;padding:40px;color:var(--muted);font-size:13px">
      <div class="spinner" style="margin:0 auto 10px"></div>იტვირთება…
    </div>

    <div id="menuContent" style="display:none">
      <div class="menu-layout">
        <!-- Categories -->
        <div class="cats-panel">
          <div class="cats-head">
            <span>კატეგორიები</span>
            <button class="icon-btn" title="დამატება" onclick="openCatModal(null)">＋</button>
          </div>
          <div id="catsList"></div>
        </div>
        <!-- Products -->
        <div class="prods-panel">
          <div class="prods-head">
            <span id="prodsHeadTitle">პროდუქტები</span>
            <button class="add-item-btn" onclick="openProdModal(null)">＋ პროდუქტი</button>
          </div>
          <div id="prodsGrid" class="prods-grid"></div>
        </div>
      </div>
    </div>

  </div><!-- /pane-menu -->

  <!-- ═══ TAB: შეთავაზება ═══ -->
  <div class="tab-pane" id="pane-offers">

    <div id="offersLoading" style="text-align:center;padding:40px;color:var(--muted);font-size:13px">
      <div class="spinner" style="margin:0 auto 10px"></div>იტვირთება…
    </div>

    <div id="offersContent" style="display:none">
      <div class="offers-topbar">
        <div style="font-size:14px;font-weight:800;color:var(--text)">🎁 ფასდაკლებები და შეთავაზებები</div>
        <button class="add-item-btn" onclick="openOfferModal(null)">＋ შეთავაზება</button>
      </div>
      <div class="offers-grid" id="offersGrid"></div>
    </div>

  </div><!-- /pane-offers -->

  <!-- ═══ TAB: შაბლონები ═══ -->
  <div class="tab-pane" id="pane-templates">

    <div id="tplLoading" style="text-align:center;padding:40px;color:var(--muted);font-size:13px">
      <div class="spinner" style="width:22px;height:22px;margin:0 auto 10px"></div>იტვირთება…
    </div>
    <div id="tplContent" style="display:none">
      <div class="tpl-topbar">
        <div>
          <div style="font-size:15px;font-weight:900;margin-bottom:3px">📋 მოდიფიკატორის შაბლონები</div>
          <div style="font-size:12px;color:var(--muted)">ერთხელ განსაზღვრე — გამოიყენე ნებისმიერ პროდუქტზე ერთი კლიკით.</div>
        </div>
        <button class="add-item-btn" onclick="openTplModal(null)">＋ შაბლონი</button>
      </div>
      <div id="tplGrid" class="tpl-grid"></div>
    </div>

  </div><!-- /pane-templates -->


</div><!-- /main -->

<!-- ═══ Modal: Branch ═══ -->
<div class="modal-ov" id="branchModal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="bmTitle">ობიექტის დამატება</h3>
      <button class="modal-close" onclick="closeModal('branchModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="bmId">
      <div class="fg"><label>სახელი *</label><input type="text" id="bmName" placeholder="e.g. მთავარი, ვაკე"></div>
      <div class="grid-2">
        <div class="fg"><label>ტელეფონი</label><input type="tel" id="bmPhone" placeholder="+995…"></div>
        <div class="fg">
          <label>სტატუსი</label>
          <select id="bmActive"><option value="1">✓ Active</option><option value="0">✗ Inactive</option></select>
        </div>
      </div>
      <div class="fg">
        <label style="display:flex;justify-content:space-between">
          <span>მისამართი</span>
          <span id="bmCoordsLabel" style="font-size:10px;font-family:monospace;font-weight:400;color:var(--blue)"></span>
        </label>
        <input type="text" id="bmAddress" placeholder="რუკაზე pin-ის დასმით ან ხელით">
        <input type="hidden" id="bmLat">
        <input type="hidden" id="bmLng">
        <div id="branchMap" style="margin-top:8px"></div>
        <p style="font-size:11px;color:var(--muted);margin-top:5px">რუკაზე კლიკი → pin + მისამართი ავტო-შეივსება</p>
      </div>

      <!-- PIN section — visible only when editing an existing branch -->
      <div id="bmPinSection" style="display:none;padding-top:14px;border-top:1px solid var(--border);margin-top:14px">
        <label style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:10px">🔐 პორტალის PIN</label>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <div id="bmPinBoxes" style="display:flex;gap:8px"></div>
          <button type="button" id="bmPinCopyBtn" onclick="copyBranchPin()" style="background:#fffbeb;border:1.5px solid #fde68a;color:#92400e;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap">📋 კოპირება</button>
        </div>
        <p style="font-size:11px;color:var(--muted);margin-top:8px;line-height:1.5">ობიექტის თანამშრომლებს გაუზიარეთ ეს PIN — პორტალში ობიექტის არჩევისას მოთხოვნილია</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-del" id="bmDelBtn" onclick="deleteBranch()" style="display:none">🗑 წაშლა</button>
      <button class="btn-cancel" onclick="closeModal('branchModal')">გაუქმება</button>
      <button class="btn-save" onclick="saveBranch()">💾 შენახვა</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: Category ═══ -->
<div class="modal-ov" id="catModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-head">
      <h3 id="cmTitle">კატეგორიის დამატება</h3>
      <button class="modal-close" onclick="closeModal('catModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="cmId">
      <div class="fg"><label>სახელი *</label><input type="text" id="cmName" placeholder="e.g. სალათები"></div>
      <div class="fg"><label>რიგი (sort)</label><input type="number" id="cmSort" value="0" min="0"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-del" id="cmDelBtn" onclick="deleteCategory()" style="display:none">🗑 წაშლა</button>
      <button class="btn-cancel" onclick="closeModal('catModal')">გაუქმება</button>
      <button class="btn-save" onclick="saveCategory()">💾 შენახვა</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: Product ═══ -->
<div class="modal-ov" id="prodModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-head">
      <h3 id="pmTitle">პროდუქტის დამატება</h3>
      <button class="modal-close" onclick="closeModal('prodModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="pmId">

      <!-- ── Quick-apply templates ── -->
      <div id="pmTplBar" style="display:none;padding:8px 10px;background:var(--bg);border-radius:10px;margin-bottom:4px">
        <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">⚡ შაბლონიდან დამატება</div>
        <div id="pmTplChips" style="display:flex;flex-wrap:wrap;gap:6px"></div>
      </div>

      <div class="fg"><label>სახელი *</label><input type="text" id="pmName" placeholder="პროდუქტის სახელი"></div>
      <div class="fg"><label>აღწერა</label><textarea id="pmDesc" style="min-height:56px" placeholder="მოკლე აღწერა…"></textarea></div>
      <div class="fg">
        <label>ფოტოს URL (სურვილისამებრ)</label>
        <input type="url" id="pmImage" placeholder="https://…/photo.jpg" oninput="previewProdImg(this.value)">
        <img id="pmImgPreview" src="" alt=""
             style="display:none;width:100%;height:100px;object-fit:cover;border-radius:8px;margin-top:6px;border:1.5px solid var(--border)"
             onerror="this.style.display='none'"
             onload="this.style.display='block'">
      </div>
      <div class="grid-2">
        <div class="fg"><label>ფასი (<?= e($sym)?>)*</label><input type="number" id="pmPrice" step="0.01" min="0" placeholder="0.00"></div>
        <div class="fg">
          <label>კატეგორია</label>
          <select id="pmCat"></select>
        </div>
      </div>
      <div class="grid-2">
        <div class="fg">
          <label>სტატუსი</label>
          <select id="pmActive">
            <option value="1">✓ Active</option>
            <option value="0">✗ Inactive</option>
          </select>
        </div>
        <div class="fg">
          <label>მარაგი</label>
          <select id="pmStock">
            <option value="1">✓ In Stock</option>
            <option value="0">✗ Out of Stock</option>
          </select>
        </div>
      </div>

      <!-- ── Sizes ── -->
      <div style="border-top:1.5px solid var(--border);margin-top:12px;padding-top:12px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">📏 ზომები</div>
          <button type="button" class="mod-add-group-btn" style="max-width:160px;padding:6px 10px"
                  onclick="pmAddSizeGroup()">＋ ზომების ჯგუფი</button>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:8px">მომხმარებელი ირჩევს ერთ ზომას. პირველი ზომა — ბაზისური ფასი (+0), დანარჩენები — ფასის სხვაობა.</div>
        <div id="pmSizesContainer"></div>
      </div>

      <!-- ── Modifier groups (options + exclusions) ── -->
      <div style="border-top:1.5px solid var(--border);margin-top:4px;padding-top:12px">
        <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px">⚙ ოფციები</div>
        <div class="mod-add-group-btns">
          <button type="button" class="mod-add-group-btn" onclick="pmAddModGroup({type:'choice'})">
            🔘 ოფცია / ვარიანტი<br><small style="font-weight:400;opacity:.7">მარწყვი, შოკოლადი…</small>
          </button>
          <button type="button" class="mod-add-group-btn" onclick="pmAddModGroup({type:'exclusion'})">
            ✖ გარეშე ოფცია<br><small style="font-weight:400;opacity:.7">ყველის გარეშე…</small>
          </button>
        </div>
        <div id="pmModContainer"></div>
      </div>

      <!-- ── Combo builder ── -->
      <div style="border-top:1.5px solid var(--border);margin-top:4px;padding-top:12px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;cursor:pointer" onclick="pmToggleCombo()">
          <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;flex:1">🔗 კომბო კომპონენტები</div>
          <span id="pmCmbToggleIcon" style="font-size:14px;color:var(--muted);transition:transform .2s">▸</span>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:10px">თუ ეს პროდუქტი კომბოა (მაგ. Burger Combo) — ჩამოაშლე და აარჩიე შემადგენლები.</div>
        <div id="pmCmbBody" style="display:none">
          <!-- ✅ Mandatory -->
          <div class="cmb-section">
            <div class="cmb-section-head">
              <span class="cmb-section-icon">✅</span>
              <div class="cmb-section-info">
                <div class="cmb-section-title">სავალდებულო</div>
                <div class="cmb-section-sub">ყოველთვის შედის</div>
              </div>
              <select class="cmb-cat-filter" id="pmCmbMandCatFilter"
                      onchange="filterSecProds('pmCmbMandList','pmCmbMandCount',this.value)"></select>
              <span class="cmb-section-count zero" id="pmCmbMandCount">0</span>
            </div>
            <div id="pmCmbMandList" class="cmb-prod-list combo-prod-list"></div>
          </div>
          <!-- ➕ Dynamic extras -->
          <div id="pmCmbExtras"></div>
          <button type="button" class="cmb-add-extra-btn" onclick="pmCmbAddExtra()">＋ დამატებითი განყოფილება</button>
          <!-- 🥤 Drinks -->
          <div class="cmb-section">
            <div class="cmb-section-head">
              <span class="cmb-section-icon">🥤</span>
              <div class="cmb-section-info">
                <div class="cmb-section-title">სასმელი</div>
                <div class="cmb-section-sub">მომხმარებელი ირჩევს ერთ-ერთს</div>
              </div>
              <select class="cmb-cat-filter" id="pmCmbDrinkCatFilter"
                      onchange="filterSecProds('pmCmbDrinkList','pmCmbDrinkCount',this.value)"></select>
              <span class="cmb-section-count zero" id="pmCmbDrinkCount">0</span>
            </div>
            <div id="pmCmbDrinkList" class="cmb-prod-list combo-prod-list"></div>
          </div>
        </div>
      </div>

    </div>
    <div class="modal-footer">
      <button class="btn-del" id="pmDelBtn" onclick="deleteProduct()" style="display:none">🗑 წაშლა</button>
      <button class="btn-cancel" onclick="closeModal('prodModal')">გაუქმება</button>
      <button class="btn-save" onclick="saveProduct()">💾 შენახვა</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: Offer ═══ -->
<div class="modal-ov" id="offerModal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="omTitle">შეთავაზების დამატება</h3>
      <button class="modal-close" onclick="closeModal('offerModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="omId">
      <div class="grid-2">
        <div class="fg"><label>კოდი / სახელი</label><input type="text" id="omCode" placeholder="e.g. SUMMER20"></div>
        <div class="fg">
          <label>ტიპი</label>
          <select id="omType" onchange="onOfferTypeChange()">
            <option value="percent">% ფასდაკლება</option>
            <option value="fixed">ფიქსირებული</option>
            <option value="free_delivery">უფასო მიტანა</option>
          </select>
        </div>
      </div>
      <div class="fg" id="omValueRow">
        <label id="omValueLabel">მნიშვნელობა *</label>
        <input type="number" id="omValue" step="0.01" min="0" placeholder="0">
      </div>
      <div class="grid-2">
        <div class="fg"><label>Min Order (<?= e($sym)?>)</label><input type="number" id="omMinOrder" step="0.01" min="0" value="0"></div>
        <div class="fg">
          <label>სტატუსი</label>
          <select id="omActive"><option value="1">✓ Active</option><option value="0">✗ Inactive</option></select>
        </div>
      </div>
      <div class="grid-2">
        <div class="fg"><label>იწყება (optional)</label><input type="date" id="omStart"></div>
        <div class="fg"><label>მთავრდება (optional)</label><input type="date" id="omEnd"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-del" id="omDelBtn" onclick="deleteOffer()" style="display:none">🗑 წაშლა</button>
      <button class="btn-cancel" onclick="closeModal('offerModal')">გაუქმება</button>
      <button class="btn-save" onclick="saveOffer()">💾 შენახვა</button>
    </div>
  </div>
</div>

<!-- comboMealModal removed — combo builder is now inside the product modal -->

<!-- ═══ Modal: Modifier Template ═══ -->
<div class="modal-ov" id="tplModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-head">
      <h3 id="tplModalTitle">შაბლონის დამატება</h3>
      <button class="modal-close" onclick="closeModal('tplModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="tplId">
      <div class="grid-2">
        <div class="fg">
          <label>სახელი *</label>
          <input type="text" id="tplName" placeholder="მაგ. ზომა, გარეშე, სოსი…">
        </div>
        <div class="fg">
          <label>ტიპი</label>
          <select id="tplType" onchange="onTplTypeChange()">
            <option value="choice">🔘 ოფცია / ვარიანტი</option>
            <option value="exclusion">✖ გარეშე</option>
            <option value="size">📏 ზომა</option>
          </select>
        </div>
      </div>
      <div class="grid-2" id="tplChoiceOpts">
        <div class="fg">
          <label style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" id="tplRequired"> სავალდებულო
          </label>
        </div>
        <div class="fg">
          <label>მაქსიმუმ არჩევანი</label>
          <select id="tplMaxSelect">
            <option value="1">max 1</option>
            <option value="2">max 2</option>
            <option value="3">max 3</option>
            <option value="5">max 5</option>
            <option value="10">max 10</option>
            <option value="20">max 20</option>
          </select>
        </div>
      </div>
      <!-- Items -->
      <div style="border-top:1.5px solid var(--border);padding-top:10px">
        <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px">ვარიანტები</div>
        <div id="tplItemsContainer"></div>
        <button type="button" class="mod-add-item" onclick="tplAddItem()">＋ ვარიანტის დამატება</button>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-del" id="tplDelBtn" onclick="deleteTpl()" style="display:none">🗑 წაშლა</button>
      <button class="btn-cancel" onclick="closeModal('tplModal')">გაუქმება</button>
      <button class="btn-save" onclick="saveTpl()">💾 შენახვა</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var BASE  = <?= json_encode($base) ?>;
var TOKEN = <?= json_encode($token) ?>;
var SYM   = <?= json_encode($sym ?? '₾') ?>;
var API   = BASE + '/api/delivery/portal/' + TOKEN;

var _branches    = <?= json_encode($branches) ?>;
var BRANCH_PINS  = <?= json_encode(array_combine(
    array_map(fn($b) => (int)$b['id'], $branches),
    array_map(fn($b) => str_pad((string)((abs(crc32('gd_branch_v1_'.(int)$b['id']))%9000)+1000),4,'0',STR_PAD_LEFT), $branches)
) ?: new stdClass()) ?>;
var _branchMap   = null, _branchMarker = null;
var _cats        = [];
var _prods       = [];
var _activeCatId = null;
var _offers      = [];

// ── Toast ──────────────────────────────────────────────────────────────────
function toast(msg, isErr){
    var t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast' + (isErr?' err':'') + ' show';
    setTimeout(function(){ t.className = 'toast' + (isErr?' err':''); }, 3000);
}

// ── API helper ─────────────────────────────────────────────────────────────
function api(method, path, body, signal){
    var opts = {method:method, headers:{'Content-Type':'application/json'}};
    if(body)   opts.body   = JSON.stringify(body);
    if(signal) opts.signal = signal;
    return fetch(API+path, opts).then(function(r){ return r.json(); });
}

// ── Tab switching ──────────────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var tab = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-pane').forEach(function(p){ p.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('pane-'+tab).classList.add('active');
        if(tab === 'menu')      loadMenu();
        if(tab === 'offers')    loadOffers();
        if(tab === 'templates') loadTemplates();
    });
});

// ── Save Profile ───────────────────────────────────────────────────────────
function saveProfile(){
    var daysChecked = Array.from(document.querySelectorAll('#daysRow input:checked'))
                         .map(function(cb){ return cb.value; }).join('');
    api('POST','/admin/update',{
        name:          document.getElementById('pName').value.trim(),
        status:        document.getElementById('pStatus').value,
        description:   document.getElementById('pDesc').value.trim(),
        phone:         document.getElementById('pPhone').value.trim(),
        email:         document.getElementById('pEmail').value.trim(),
        cuisine_tags:  document.getElementById('pTags').value.trim(),
        menu_size:     document.getElementById('pMenuSize').value.trim(),
        open_time:     document.getElementById('pOpen').value,
        close_time:    document.getElementById('pClose').value,
        prep_time_min: parseInt(document.getElementById('pPrep').value)||20,
        days_open:     daysChecked,
        min_order:     parseFloat(document.getElementById('pMinOrder').value)||0,
        delivery_fee:  parseFloat(document.getElementById('pDeliveryFee').value)||0,
    }).then(function(d){
        if(d.ok) toast('✓ შენახულია');
        else toast(d.error||'Error', true);
    });
}

// ── Image Upload ───────────────────────────────────────────────────────────
function uploadImg(input, type){
    var file = input.files[0];
    if(!file) return;
    var fd = new FormData();
    fd.append('file', file);
    fd.append('type', type);
    var prog = document.getElementById(type==='logo'?'logoProg':'coverProg');
    prog.style.display = 'block';
    fetch(API+'/admin/upload',{method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            prog.style.display = 'none';
            if(d.ok){
                var prev = document.getElementById(type==='logo'?'logoPreview':'coverPreview');
                prev.style.display = '';
                prev.onload  = function(){ this.classList.add('show'); };
                prev.onerror = function(){ this.style.display='none'; };
                prev.src = d.url + '?v=' + Date.now();
                toast('✓ ატვირთულია');
            } else { toast(d.error||'Upload failed', true); }
        }).catch(function(){ prog.style.display='none'; toast('Upload failed', true); });
}

// ── Modal helpers ──────────────────────────────────────────────────────────
function closeModal(id){
    document.getElementById(id).classList.remove('open');
    if(id === 'branchModal' && _branchMap){ _branchMap.remove(); _branchMap=null; _branchMarker=null; }
    if(id === 'prodModal'){
        // Reset sizes & combo state
        document.getElementById('pmSizesContainer').innerHTML = '';
        document.getElementById('pmModContainer').innerHTML   = '';
        document.getElementById('pmCmbExtras').innerHTML      = '';
        document.getElementById('pmCmbBody').style.display    = 'none';
        document.getElementById('pmCmbToggleIcon').style.transform = '';
        _pmSizeIdx   = 0;
        _pmCmbExtraIdx = 0;
    }
}
document.querySelectorAll('.modal-ov').forEach(function(ov){
    ov.addEventListener('click', function(e){
        if(e.target === ov) closeModal(ov.id);
    });
});
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){
        ['branchModal','catModal','prodModal','offerModal','tplModal'].forEach(function(id){
            var el = document.getElementById(id);
            if(el && el.classList.contains('open')) closeModal(id);
        });
    }
});

// ══════════════════════════════════════════════════════════════════════════
// BRANCHES
// ══════════════════════════════════════════════════════════════════════════
function renderBranchPinBoxes(pin) {
    var wrap = document.getElementById('bmPinBoxes');
    if (!wrap) return;
    wrap.innerHTML = '';
    (pin + '').split('').forEach(function(d) {
        var box = document.createElement('div');
        box.textContent = d;
        box.style.cssText = 'width:46px;height:56px;background:#fffbeb;border:2px solid #fde68a;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:900;font-family:monospace;color:#b45309;letter-spacing:0';
        wrap.appendChild(box);
    });
}

function copyBranchPin() {
    var bid = document.getElementById('bmId').value;
    var pin = BRANCH_PINS[bid] || '';
    if (!pin) return;
    navigator.clipboard.writeText(pin).then(function() {
        var btn = document.getElementById('bmPinCopyBtn');
        if (btn) { btn.textContent = '✓ კოპირებულია'; setTimeout(function(){ btn.textContent = '📋 კოპირება'; }, 1800); }
    }).catch(function() {
        prompt('PIN:', pin);
    });
}

function openBranchModal(branchId){
    document.getElementById('bmTitle').textContent = branchId ? 'ობიექტის რედაქტირება' : 'ობიექტის დამატება';
    document.getElementById('bmDelBtn').style.display = branchId ? 'block' : 'none';
    document.getElementById('bmId').value = branchId || '';

    if(branchId){
        var b = _branches.find(function(x){ return String(x.id)===String(branchId); }) || {};
        document.getElementById('bmName').value    = b.name    || '';
        document.getElementById('bmPhone').value   = b.phone   || '';
        document.getElementById('bmActive').value  = String(parseInt(b.active)===1 ? 1 : 0);
        document.getElementById('bmAddress').value = b.address || '';
        document.getElementById('bmLat').value     = b.lat     || '';
        document.getElementById('bmLng').value     = b.lng     || '';
        if(b.lat&&b.lng) document.getElementById('bmCoordsLabel').textContent = parseFloat(b.lat).toFixed(5)+', '+parseFloat(b.lng).toFixed(5);
        // Show PIN section
        var pin = BRANCH_PINS[branchId];
        if (pin) {
            document.getElementById('bmPinSection').style.display = 'block';
            renderBranchPinBoxes(pin);
        } else {
            document.getElementById('bmPinSection').style.display = 'none';
        }
    } else {
        ['bmName','bmPhone','bmAddress','bmLat','bmLng'].forEach(function(id){ document.getElementById(id).value=''; });
        document.getElementById('bmActive').value = '1';
        document.getElementById('bmCoordsLabel').textContent = '';
        document.getElementById('bmPinSection').style.display = 'none';
    }
    document.getElementById('branchModal').classList.add('open');
    setTimeout(function(){
        initBranchMap(branchId ? parseFloat(document.getElementById('bmLat').value)||null : null,
                      branchId ? parseFloat(document.getElementById('bmLng').value)||null : null);
    }, 80);
}

function initBranchMap(initLat, initLng){
    if(_branchMap){ _branchMap.remove(); _branchMap=null; _branchMarker=null; }
    var lat = initLat || 41.6938, lng = initLng || 44.8015;
    var zoom = (initLat&&initLng) ? 15 : 12;
    _branchMap = L.map('branchMap').setView([lat,lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(_branchMap);
    if(initLat && initLng){
        _branchMarker = L.marker([lat,lng],{draggable:true}).addTo(_branchMap);
        bindMarkerDrag();
    }
    _branchMap.on('click',function(e){ setBranchPin(e.latlng.lat, e.latlng.lng); });
}

function setBranchPin(lat,lng){
    document.getElementById('bmLat').value = lat.toFixed(7);
    document.getElementById('bmLng').value = lng.toFixed(7);
    document.getElementById('bmCoordsLabel').textContent = lat.toFixed(5)+', '+lng.toFixed(5);
    if(_branchMarker){ _branchMarker.setLatLng([lat,lng]); }
    else { _branchMarker = L.marker([lat,lng],{draggable:true}).addTo(_branchMap); bindMarkerDrag(); }
    _branchMap.setView([lat,lng], Math.max(_branchMap.getZoom(),15));
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&zoom=18',
          {headers:{'Accept-Language':'ka'}})
        .then(function(r){return r.json();}).then(function(d){
            if(d&&d.display_name && !document.getElementById('bmAddress').dataset.manual)
                document.getElementById('bmAddress').value = d.display_name;
        }).catch(function(){});
}

function bindMarkerDrag(){
    _branchMarker.on('dragend',function(e){
        var ll = e.target.getLatLng();
        setBranchPin(ll.lat, ll.lng);
    });
}

document.getElementById('bmAddress').addEventListener('input',function(){ this.dataset.manual='1'; });

function saveBranch(){
    var id = document.getElementById('bmId').value;
    var name = document.getElementById('bmName').value.trim();
    if(!name){ toast('სახელი სავალდებულოა', true); return; }
    var body = {
        name:    name,
        phone:   document.getElementById('bmPhone').value.trim(),
        active:  parseInt(document.getElementById('bmActive').value),
        address: document.getElementById('bmAddress').value.trim(),
        lat:     parseFloat(document.getElementById('bmLat').value)||null,
        lng:     parseFloat(document.getElementById('bmLng').value)||null,
    };
    api('POST', id ? '/branches/'+id+'/update' : '/branches', body).then(function(d){
        if(d.ok){ toast('✓ შენახულია'); closeModal('branchModal'); location.reload(); }
        else toast(d.error||'Error', true);
    });
}

function deleteBranch(){
    var id = document.getElementById('bmId').value;
    if(!id||!confirm('ობიექტი წაიშლება?')) return;
    api('POST','/branches/'+id+'/delete',{}).then(function(d){
        if(d.ok){ toast('წაიშალა'); closeModal('branchModal'); location.reload(); }
        else toast(d.error||'Error', true);
    });
}

// ══════════════════════════════════════════════════════════════════════════
// MENU
// ══════════════════════════════════════════════════════════════════════════
var _menuLoaded = false;

function loadMenu(){
    if(_menuLoaded){
        // Data already cached — just render immediately
        document.getElementById('menuLoading').style.display = 'none';
        document.getElementById('menuContent').style.display = 'block';
        renderCats();
        filterCat(_activeCatId);
        return;
    }
    document.getElementById('menuLoading').style.display = 'block';
    document.getElementById('menuContent').style.display = 'none';
    api('GET','/catalog',null).then(function(d){
        _menuLoaded = true;
        _cats  = d.categories || [];
        _prods = d.products   || [];
        document.getElementById('menuLoading').style.display = 'none';
        document.getElementById('menuContent').style.display = 'block';
        renderCats();
        filterCat(null);
    }).catch(function(){
        document.getElementById('menuLoading').innerHTML = '<span style="color:var(--red)">❌ ვერ ჩაიტვირთა</span>';
    });
}

function renderCats(){
    var el = document.getElementById('catsList');
    var allActive = (_activeCatId === null);
    var allItem = '<div class="cat-item'+(allActive?' active':'')+'" onclick="filterCat(null)">'
        +'<span>ყველა</span>'
        +'<span class="cat-count">'+_prods.length+'</span>'
        +'</div>';
    if(!_cats.length){
        el.innerHTML = allItem;
        return;
    }
    el.innerHTML = allItem + _cats.map(function(c){
        var count = _prods.filter(function(p){ return String(p.category_id)===String(c.id); }).length;
        return '<div class="cat-item'+(String(_activeCatId)===String(c.id)?' active':'')+'" onclick="filterCat('+c.id+')" id="catItem_'+c.id+'">'
            +'<span>'+escHtml(c.name)+'</span>'
            +'<span class="cat-count">'+count+'</span>'
            +'<button class="icon-btn" style="margin-left:6px;font-size:11px;width:22px;height:22px" '
            +'onclick="event.stopPropagation();openCatModal('+c.id+')">✏</button>'
            +'</div>';
    }).join('');
}

function filterCat(catId){
    _activeCatId = catId;
    renderCats();
    var title = catId ? 'პროდუქტები' : 'ყველა პროდუქტი';
    if(catId){
        var cat = _cats.find(function(c){ return String(c.id)===String(catId); });
        if(cat) title = escHtml(cat.name);
    }
    document.getElementById('prodsHeadTitle').textContent = title;
    renderProds();
}

function renderProds(){
    var el = document.getElementById('prodsGrid');
    var filtered = _activeCatId ? _prods.filter(function(p){ return String(p.category_id)===String(_activeCatId); }) : _prods;
    if(!filtered.length){
        el.innerHTML = '<div class="menu-empty">პროდუქტი არ არის — დაამატე</div>';
        return;
    }
    el.innerHTML = filtered.map(function(p){
        var imgHtml = p.image
            ? '<img class="prod-img" src="'+escHtml(p.image)+'" alt="" onerror="this.style.display=\'none\'">'
            : '<div class="prod-img-ph">🍽</div>';
        var inStock = parseInt(p.in_stock)!==0;
        return '<div class="prod-card'+(inStock?'':' out-of-stock')+'" onclick="openProdModal('+p.id+')">'
            + imgHtml
            +'<div class="prod-info">'
            +'<div class="prod-name">'+escHtml(p.name)+'</div>'
            +'<div class="prod-desc">'+escHtml(p.description||'')+'</div>'
            +'<div class="prod-bottom">'
            +'<span class="prod-price">'+parseFloat(p.price).toFixed(2)+SYM+'</span>'
            +'<span class="prod-stock-badge '+(inStock?'in':'out')+'" '
            +'onclick="event.stopPropagation();toggleStock('+p.id+','+(inStock?'0':'1')+')">'
            +(inStock?'In Stock':'Out')+'</span>'
            +'</div></div></div>';
    }).join('');
}

function toggleStock(prodId, inStock){
    api('POST','/products/'+prodId+'/stock',{in_stock:inStock}).then(function(d){
        if(d.ok){
            var p = _prods.find(function(x){ return String(x.id)===String(prodId); });
            if(p){ p.in_stock = inStock; renderProds(); }
            toast(inStock ? '✓ In Stock' : 'Out of Stock');
        } else toast(d.error||'Error', true);
    });
}

// Category modal
function openCatModal(catId){
    document.getElementById('cmTitle').textContent = catId ? 'კატეგორიის რედაქტირება' : 'კატეგორიის დამატება';
    document.getElementById('cmDelBtn').style.display = catId ? 'block' : 'none';
    document.getElementById('cmId').value = catId || '';
    if(catId){
        var c = _cats.find(function(x){ return String(x.id)===String(catId); }) || {};
        document.getElementById('cmName').value = c.name || '';
        document.getElementById('cmSort').value = c.sort_order || 0;
    } else {
        document.getElementById('cmName').value = '';
        document.getElementById('cmSort').value = '0';
    }
    document.getElementById('catModal').classList.add('open');
    setTimeout(function(){ document.getElementById('cmName').focus(); }, 80);
}

function saveCategory(){
    var id   = document.getElementById('cmId').value;
    var name = document.getElementById('cmName').value.trim();
    if(!name){ toast('სახელი სავალდებულოა', true); return; }
    var body = {name:name, sort_order:parseInt(document.getElementById('cmSort').value)||0};
    api('POST', id ? '/categories/'+id+'/update' : '/categories', body).then(function(d){
        if(d.ok){
            toast('✓ შენახულია'); closeModal('catModal'); _menuLoaded=false; loadMenu();
        } else toast(d.error||'Error', true);
    });
}

function deleteCategory(){
    var id = document.getElementById('cmId').value;
    if(!id||!confirm('კატეგორია წაიშლება?')) return;
    api('POST','/categories/'+id+'/delete',{}).then(function(d){
        if(d.ok){ toast('წაიშალა'); closeModal('catModal'); _menuLoaded=false; loadMenu(); }
        else toast(d.error||'Error', true);
    });
}

// ── Product modal ──────────────────────────────────────────────────────────
function openProdModal(prodId){
    document.getElementById('pmTitle').textContent = prodId ? 'პროდუქტის რედაქტირება' : 'პროდუქტის დამატება';
    document.getElementById('pmDelBtn').style.display = prodId ? 'block' : 'none';
    document.getElementById('pmId').value = prodId || '';

    var sel = document.getElementById('pmCat');
    sel.innerHTML = '<option value="">— კატეგორია —</option>'
        + _cats.map(function(c){ return '<option value="'+c.id+'">'+escHtml(c.name)+'</option>'; }).join('');

    if(prodId){
        var p = _prods.find(function(x){ return String(x.id)===String(prodId); }) || {};
        document.getElementById('pmName').value   = p.name  || '';
        document.getElementById('pmDesc').value   = p.description || '';
        document.getElementById('pmPrice').value  = p.price || '';
        document.getElementById('pmActive').value = String(parseInt(p.active)===0 ? 0 : 1);
        document.getElementById('pmStock').value  = String(parseInt(p.in_stock)===0 ? 0 : 1);
        document.getElementById('pmImage').value  = p.image || '';
        sel.value = p.category_id || '';
        previewProdImg(p.image || '');
    } else {
        ['pmName','pmDesc','pmPrice','pmImage'].forEach(function(id){ document.getElementById(id).value=''; });
        document.getElementById('pmActive').value = '1';
        document.getElementById('pmStock').value  = '1';
        sel.value = _activeCatId || '';
        previewProdImg('');
    }

    // Load modifier groups (choice/exclusion/size) and combo
    pmLoadModifiers(prodId);
    pmLoadCombo(prodId);

    // Render template chips (load templates if not yet loaded)
    if(_tplLoaded){
        pmRenderTplChips();
    } else {
        api('GET','/modifier-templates',null).then(function(d){
            _templates = d.templates || [];
            _tplLoaded = true;
            pmRenderTplChips();
        }).catch(function(){});
    }

    document.getElementById('prodModal').classList.add('open');
    setTimeout(function(){ document.getElementById('pmName').focus(); }, 80);
}

// ── Modifier groups ────────────────────────────────────────────────────────

function pmLoadModifiers(prodId){
    document.getElementById('pmModContainer').innerHTML   = '';
    document.getElementById('pmSizesContainer').innerHTML = '';
    if(!prodId) return; // new product — start empty
    api('GET', '/products/'+prodId+'/modifiers', null).then(function(d){
        (d.groups||[]).forEach(function(g){ pmAddModGroup(g); });
    }).catch(function(){});
}

function pmAddModGroup(g){
    g = g || {};
    var type    = g.type || 'choice';
    var isSize  = type === 'size';
    var isExcl  = type === 'exclusion';
    var uid     = 'pmMg_' + Date.now() + '_' + Math.floor(Math.random()*9999);
    var bodyId  = uid + '_body';
    var maxVal  = parseInt(g.max_select) || (isExcl ? 20 : 1);
    var reqChk  = parseInt(g.required||0) === 1 ? ' checked' : '';
    var typeLbl = isSize ? '📏 ზომა' : (isExcl ? '✖ გარეშე' : '🔘 ოფცია');
    var typeCls = isSize ? 'mod-type-size' : (isExcl ? 'mod-type-exclusion' : 'mod-type-choice');
    var placeholder = isSize ? 'ზომების ჯგუფი (მაგ. პიცის ზომა)' : 'ჯგუფის სახელი (მაგ. გემო)';

    var maxOpts = [1,2,3,5,10,20].map(function(v){
        return '<option value="'+v+'"'+(maxVal===v?' selected':'')+'>max '+v+'</option>';
    }).join('');

    var div = document.createElement('div');
    div.className = 'mod-group';
    div.id = uid;
    div.innerHTML =
        '<div class="mod-group-head">'
        +'<span class="mod-type-badge '+typeCls+'">'+typeLbl+'</span>'
        +'<input type="hidden" class="pmMgType" value="'+type+'">'
        +'<input type="text" class="mod-group-name" value="'+escHtml(g.name||'')+'" placeholder="'+escHtml(placeholder)+'">'
        +(isExcl ? '' :
            (!isSize
                ? '<label class="mod-group-opt"><input type="checkbox" class="pmMgReq"'+reqChk+'> სავალდ.</label>'
                  +'<select class="pmMgMax" style="font-size:11px;border:1.5px solid var(--border);border-radius:6px;padding:2px 4px;background:var(--card);font-family:inherit">'+maxOpts+'</select>'
                : '' /* sizes: always required, single-select — no UI knobs needed */)
        )
        +'<button type="button" class="cmb-remove-btn" onclick="document.getElementById(\''+uid+'\').remove()" style="margin-left:auto;flex-shrink:0">🗑</button>'
        +'</div>'
        +'<div class="mod-group-body" id="'+bodyId+'"></div>'
        +'<div style="padding:0 8px 6px">'
        +'<button type="button" class="mod-add-item" onclick="pmAddItem(\''+bodyId+'\''+(isSize?',null,true':'')+')">'
        +(isSize ? '＋ ზომის დამატება' : '＋ ჩამატება')+'</button>'
        +'</div>';

    // Sizes go into their own container; choice/exclusion into pmModContainer
    var container = isSize
        ? document.getElementById('pmSizesContainer')
        : document.getElementById('pmModContainer');
    container.appendChild(div);
    (g.modifiers||[]).forEach(function(m){ pmAddItem(bodyId, m, isSize); });
}

function pmAddSizeGroup(){
    pmAddModGroup({type:'size'});
}

function pmAddItem(bodyId, m, isSize){
    m = m || {};
    var pricePlaceholder = isSize ? '±0.00' : '0.00';
    var priceTitle       = isSize ? 'ფასის სხვაობა (±)' : 'ფასის დამატება (+0.00)';
    var namePlaceholder  = isSize ? 'ზომა (მაგ. S, M, L, XL)' : 'დასახელება';
    var row = document.createElement('div');
    row.className = 'mod-item-row';
    row.innerHTML =
        '<input type="text" class="mod-item-name" value="'+escHtml(m.name||'')+'" placeholder="'+escHtml(namePlaceholder)+'">'
        +'<input type="number" class="mod-item-price" value="'+parseFloat(m.price||0).toFixed(2)+'" step="0.01" placeholder="'+pricePlaceholder+'" title="'+priceTitle+'">'
        +'<span style="font-size:11px;color:var(--muted)">'+SYM+'</span>'
        +'<button type="button" class="mod-item-del" onclick="this.closest(\'.mod-item-row\').remove()">✕</button>';
    var body = document.getElementById(bodyId);
    if(body){
        body.appendChild(row);
        row.querySelector('.mod-item-name').focus();
    }
}

function pmCollectModGroups(){
    var groups = [];
    // Collect sizes (always required=1, max_select=1)
    document.querySelectorAll('#pmSizesContainer .mod-group').forEach(function(g){
        var name = (g.querySelector('.mod-group-name')||{}).value || '';
        var mods = [];
        g.querySelectorAll('.mod-item-row').forEach(function(row){
            var n = (row.querySelector('.mod-item-name')||{}).value || '';
            var p = parseFloat((row.querySelector('.mod-item-price')||{}).value||0)||0;
            if(n.trim()) mods.push({name:n.trim(), price:p});
        });
        if(mods.length) groups.push({name:name.trim(), type:'size', required:1, max_select:1, modifiers:mods});
    });
    // Collect choice/exclusion
    document.querySelectorAll('#pmModContainer .mod-group').forEach(function(g){
        var type  = (g.querySelector('.pmMgType')||{}).value || 'choice';
        var name  = (g.querySelector('.mod-group-name')||{}).value || '';
        var reqEl = g.querySelector('.pmMgReq');
        var maxEl = g.querySelector('.pmMgMax');
        var req   = reqEl ? (reqEl.checked ? 1 : 0) : 0;
        var max   = maxEl ? (parseInt(maxEl.value)||1) : 20;
        var mods  = [];
        g.querySelectorAll('.mod-item-row').forEach(function(row){
            var n = (row.querySelector('.mod-item-name')||{}).value || '';
            var p = parseFloat((row.querySelector('.mod-item-price')||{}).value||0)||0;
            if(n.trim()) mods.push({name:n.trim(), price:p});
        });
        if(mods.length) groups.push({name:name.trim(), type:type, required:req, max_select:max, modifiers:mods});
    });
    return groups;
}

function previewProdImg(url){
    var img = document.getElementById('pmImgPreview');
    if(url){ img.src = url; }
    else { img.src = ''; img.style.display = 'none'; }
}

function saveProduct(){
    var id    = document.getElementById('pmId').value;
    var name  = document.getElementById('pmName').value.trim();
    var price = parseFloat(document.getElementById('pmPrice').value);
    if(!name)  { toast('სახელი სავალდებულოა', true); return; }
    if(isNaN(price)||price<0){ toast('ფასი სავალდებულოა', true); return; }
    var body = {
        name:        name,
        description: document.getElementById('pmDesc').value.trim(),
        price:       price,
        image:       document.getElementById('pmImage').value.trim(),
        category_id: parseInt(document.getElementById('pmCat').value)||null,
        active:      parseInt(document.getElementById('pmActive').value),
        in_stock:    parseInt(document.getElementById('pmStock').value),
    };
    api('POST', id ? '/products/'+id+'/update' : '/products', body).then(function(d){
        if(!d.ok){ toast(d.error||'Error', true); return; }
        var saveId = id || (d.id ? String(d.id) : null);
        var doFinish = function(){
            toast('✓ შენახულია'); closeModal('prodModal'); _menuLoaded=false; loadMenu();
        };
        if(saveId){
            // 1) Save modifier groups (sizes + choice + exclusion)
            var groups = pmCollectModGroups();
            var modP = api('POST', '/products/'+saveId+'/modifiers/save', {groups: groups});

            // 2) Save combo data if the combo section has any selections
            var comboData = pmCollectCombo();
            var hasCombo = comboData.mandatory.length || comboData.extras.length || comboData.drinks.length;
            var comboP = hasCombo
                ? api('POST', '/combo-meals/'+saveId+'/update', Object.assign(
                    {name: document.getElementById('pmName').value.trim(),
                     price: parseFloat(document.getElementById('pmPrice').value)||0,
                     image: document.getElementById('pmImage').value.trim()||null,
                     category_id: parseInt(document.getElementById('pmCat').value)||null},
                    comboData))
                : Promise.resolve({ok:true});

            Promise.all([modP, comboP]).then(doFinish).catch(doFinish);
        } else {
            doFinish();
        }
    }).catch(function(){
        toast('სერვერის შეცდომა', true);
    });
}

function deleteProduct(){
    var id = document.getElementById('pmId').value;
    if(!id||!confirm('პროდუქტი წაიშლება?')) return;
    api('POST','/products/'+id+'/delete',{}).then(function(d){
        if(d.ok){ toast('წაიშალა'); closeModal('prodModal'); _menuLoaded=false; loadMenu(); }
        else toast(d.error||'Error', true);
    });
}

// ══════════════════════════════════════════════════════════════════════════
// COMBO IN PRODUCT MODAL
// ══════════════════════════════════════════════════════════════════════════
var _pmCmbExtraIdx = 0;
var _pmSizeIdx     = 0; // reserved for future inline use
var _comboMeals    = [];

function pmToggleCombo(){
    var body = document.getElementById('pmCmbBody');
    var icon = document.getElementById('pmCmbToggleIcon');
    var isOpen = body.style.display === 'block';
    body.style.display = isOpen ? 'none' : 'block';
    icon.style.transform = isOpen ? '' : 'rotate(90deg)';
    // Populate cat filters on first open
    if(!isOpen){
        pmCmbPopulateCatFilters();
        renderSecProds('pmCmbMandList',  'pmCmbMandCount',  '', getCheckedIds('pmCmbMandList'));
        renderSecProds('pmCmbDrinkList', 'pmCmbDrinkCount', '', getCheckedIds('pmCmbDrinkList'));
    }
}

function pmCmbPopulateCatFilters(){
    ['pmCmbMandCatFilter','pmCmbDrinkCatFilter'].forEach(function(id){
        var el = document.getElementById(id);
        if(el && !el.childNodes.length) el.innerHTML = cmbCatOptions('');
    });
}

function getCheckedIds(listId){
    return Array.from(document.querySelectorAll('#'+listId+' input[type=checkbox]:checked'))
        .map(function(cb){ return cb.value; });
}

function pmLoadCombo(prodId){
    // Reset
    document.getElementById('pmCmbExtras').innerHTML = '';
    _pmCmbExtraIdx = 0;

    // Repopulate cat filters (categories may have changed since last open)
    ['pmCmbMandCatFilter','pmCmbDrinkCatFilter'].forEach(function(id){
        var el = document.getElementById(id);
        if(el) el.innerHTML = cmbCatOptions('');
    });

    // Render empty lists
    renderSecProds('pmCmbMandList',  'pmCmbMandCount',  '', []);
    renderSecProds('pmCmbDrinkList', 'pmCmbDrinkCount', '', []);

    if(!prodId) return;

    // Try in-memory cache first, otherwise fetch
    var m = _comboMeals.find(function(x){ return String(x.id) === String(prodId); });
    if(m){
        pmApplyComboData(m);
    } else {
        api('GET', '/combo-meals', null).then(function(d){
            _comboMeals = d.meals || [];
            var m2 = _comboMeals.find(function(x){ return String(x.id) === String(prodId); });
            if(m2) pmApplyComboData(m2);
        }).catch(function(){});
    }
}

function pmApplyComboData(m){
    if(!m) return;
    var mandIds  = (m.mandatory||[]).map(function(p){ return p.id; });
    var drinkIds = (m.drinks||[]).map(function(p){ return p.id; });
    var hasData  = mandIds.length || drinkIds.length || (m.extras && m.extras.length);
    if(hasData){
        document.getElementById('pmCmbBody').style.display = 'block';
        document.getElementById('pmCmbToggleIcon').style.transform = 'rotate(90deg)';
        pmCmbPopulateCatFilters();
    }
    renderSecProds('pmCmbMandList',  'pmCmbMandCount',  '', mandIds);
    renderSecProds('pmCmbDrinkList', 'pmCmbDrinkCount', '', drinkIds);
    (m.extras||[]).forEach(function(ex){
        pmCmbAddExtra(ex.name, '', (ex.products||[]).map(function(p){ return p.id; }));
    });
}

function pmCmbAddExtra(name, catId, checkedIds){
    var idx     = ++_pmCmbExtraIdx;
    var listId  = 'pmCmbExtraList_'+idx;
    var countId = 'pmCmbExtraCnt_'+idx;
    var secId   = 'pmCmbExtraSec_'+idx;

    // Ensure cat filters are populated
    pmCmbPopulateCatFilters();

    var div = document.createElement('div');
    div.className = 'cmb-section';
    div.id = secId;
    div.innerHTML =
        '<div class="cmb-section-head">'
        +'<span class="cmb-section-icon">➕</span>'
        +'<input type="text" class="cmb-extra-name-input" value="'+escHtml(name||'')+'" placeholder="განყოფილების სახელი (მაგ. ტოპინგები)">'
        +'<select class="cmb-cat-filter" onchange="filterSecProds(\''+listId+'\',\''+countId+'\',this.value)">'
        +cmbCatOptions(catId||'')+'</select>'
        +'<span class="cmb-section-count zero" id="'+countId+'">0</span>'
        +'<button type="button" class="cmb-remove-btn" onclick="pmCmbRemoveExtra(\''+secId+'\')">🗑</button>'
        +'</div>'
        +'<div id="'+listId+'" class="cmb-prod-list combo-prod-list"></div>';

    // Open combo body if collapsed
    document.getElementById('pmCmbBody').style.display = 'block';
    document.getElementById('pmCmbToggleIcon').style.transform = 'rotate(90deg)';

    document.getElementById('pmCmbExtras').appendChild(div);
    renderSecProds(listId, countId, catId||'', checkedIds||[]);
}

function pmCmbRemoveExtra(secId){
    var el = document.getElementById(secId);
    if(el) el.remove();
}

function pmCollectCombo(){
    function getChecked(listId){
        return Array.from(document.querySelectorAll('#'+listId+' input[type=checkbox]:checked'))
            .map(function(cb){ return parseInt(cb.value); });
    }
    var extras = Array.from(document.querySelectorAll('#pmCmbExtras .cmb-section'))
        .map(function(sec){
            var nameEl = sec.querySelector('.cmb-extra-name-input');
            var listEl = sec.querySelector('.cmb-prod-list');
            var prods  = listEl
                ? Array.from(listEl.querySelectorAll('input[type=checkbox]:checked')).map(function(cb){ return parseInt(cb.value); })
                : [];
            return {name: nameEl ? nameEl.value.trim()||'დამატებითი' : 'დამატებითი', products: prods};
        })
        .filter(function(ex){ return ex.products.length > 0; });
    return {
        mandatory: getChecked('pmCmbMandList'),
        extras:    extras,
        drinks:    getChecked('pmCmbDrinkList'),
    };
}

// ══════════════════════════════════════════════════════════════════════════
// OFFERS
// ══════════════════════════════════════════════════════════════════════════
var _offersLoaded = false;

function loadOffers(){
    if(_offersLoaded) return;
    document.getElementById('offersLoading').style.display = 'block';
    document.getElementById('offersContent').style.display = 'none';
    api('GET','/offers',null).then(function(d){
        _offersLoaded = true;
        _offers = d.offers || [];
        document.getElementById('offersLoading').style.display = 'none';
        document.getElementById('offersContent').style.display = 'block';
        renderOffers();
    }).catch(function(){
        document.getElementById('offersLoading').innerHTML = '<span style="color:var(--red)">❌ ვერ ჩაიტვირთა</span>';
    });
}

function renderOffers(){
    var el = document.getElementById('offersGrid');
    if(!_offers.length){ el.innerHTML='<div class="offer-empty">🎁 შეთავაზება არ არის</div>'; return; }
    var typeColors = {percent:'#dbeafe;color:#1d4ed8',fixed:'#dcfce7;color:#166534',free_delivery:'#f3e8ff;color:#7c3aed'};
    var typeLabels = {percent:'% ფასდაკლება',fixed:'ფიქსირებული',free_delivery:'უფასო მიტანა'};
    el.innerHTML = _offers.map(function(o){
        var active = parseInt(o.active)!==0;
        var tc = typeColors[o.type] || '#f1f5f9;color:#64748b';
        var tl = typeLabels[o.type] || o.type;
        var val = o.type==='free_delivery' ? '' : (parseFloat(o.value||0).toFixed(o.type==='percent'?0:2)+(o.type==='percent'?'%':SYM));
        return '<div class="offer-card'+(active?'':' inactive-offer')+'" onclick="openOfferModal('+o.id+')">'
            +'<div class="offer-card-head">'
            +'<div class="offer-code">'+(o.code||o.id)+'</div>'
            +'<label class="offer-toggle" onclick="event.stopPropagation()">'
            +'<input type="checkbox"'+(active?' checked':'')
            +' onchange="toggleOffer('+o.id+',this.checked)">'
            +'<span class="toggle-slider"></span></label>'
            +'</div>'
            +'<span class="offer-type-pill" style="background:'+tc+'">'+tl+'</span>'
            +'<div class="offer-details">'
            +(val ? '💰 '+val+'<br>' : '')
            +(parseFloat(o.min_order||0)>0 ? 'Min order: '+parseFloat(o.min_order).toFixed(2)+SYM+'<br>' : '')
            +'</div>'
            +(o.starts_at||o.ends_at ? '<div class="offer-dates">📅 '+(o.starts_at||'…')+' → '+(o.ends_at||'…')+'</div>' : '')
            +'</div>';
    }).join('');
}

function toggleOffer(id, active){
    api('POST','/offers/'+id+'/update',{active:active?1:0}).then(function(d){
        if(d.ok){
            var o = _offers.find(function(x){ return String(x.id)===String(id); });
            if(o){ o.active = active?1:0; renderOffers(); }
            toast(active ? '✓ გააქტიურდა' : 'გამოირთო');
        } else toast(d.error||'Error', true);
    });
}

function onOfferTypeChange(){
    var type = document.getElementById('omType').value;
    var row  = document.getElementById('omValueRow');
    if(type==='free_delivery'){
        row.style.display='none';
    } else {
        row.style.display='block';
        document.getElementById('omValueLabel').textContent = type==='percent' ? 'ფასდაკლება (%)' : 'ფასდაკლება ('+SYM+')';
    }
}

function openOfferModal(offerId){
    document.getElementById('omTitle').textContent = offerId ? 'შეთავაზების რედაქტირება' : 'შეთავაზების დამატება';
    document.getElementById('omDelBtn').style.display = offerId ? 'block' : 'none';
    document.getElementById('omId').value = offerId || '';
    if(offerId){
        var o = _offers.find(function(x){ return String(x.id)===String(offerId); }) || {};
        document.getElementById('omCode').value     = o.code     || '';
        document.getElementById('omType').value     = o.type     || 'percent';
        document.getElementById('omValue').value    = o.value    || '0';
        document.getElementById('omMinOrder').value = o.min_order|| '0';
        document.getElementById('omActive').value   = String(parseInt(o.active)===0 ? 0 : 1);
        document.getElementById('omStart').value    = (o.starts_at||'').substring(0,10);
        document.getElementById('omEnd').value      = (o.ends_at||'').substring(0,10);
    } else {
        ['omCode','omStart','omEnd'].forEach(function(id){ document.getElementById(id).value=''; });
        document.getElementById('omType').value     = 'percent';
        document.getElementById('omValue').value    = '0';
        document.getElementById('omMinOrder').value = '0';
        document.getElementById('omActive').value   = '1';
    }
    onOfferTypeChange();
    document.getElementById('offerModal').classList.add('open');
    setTimeout(function(){ document.getElementById('omCode').focus(); }, 80);
}

function saveOffer(){
    var id   = document.getElementById('omId').value;
    var type = document.getElementById('omType').value;
    var body = {
        code:      document.getElementById('omCode').value.trim(),
        type:      type,
        value:     type==='free_delivery' ? 0 : (parseFloat(document.getElementById('omValue').value)||0),
        min_order: parseFloat(document.getElementById('omMinOrder').value)||0,
        active:    parseInt(document.getElementById('omActive').value),
        starts_at: document.getElementById('omStart').value || null,
        ends_at:   document.getElementById('omEnd').value   || null,
    };
    api('POST', id ? '/offers/'+id+'/update' : '/offers', body).then(function(d){
        if(d.ok){
            toast('✓ შენახულია'); closeModal('offerModal'); _offersLoaded=false; loadOffers();
        } else toast(d.error||'Error', true);
    });
}

function deleteOffer(){
    var id = document.getElementById('omId').value;
    if(!id||!confirm('შეთავაზება წაიშლება?')) return;
    api('POST','/offers/'+id+'/delete',{}).then(function(d){
        if(d.ok){ toast('წაიშალა'); closeModal('offerModal'); _offersLoaded=false; loadOffers(); }
        else toast(d.error||'Error', true);
    });
}

// ══════════════════════════════════════════════════════════════════════════
// MODIFIER TEMPLATES
// ══════════════════════════════════════════════════════════════════════════
var _tplLoaded    = false;
var _templates    = [];
var _tplInFlight  = false;

function loadTemplates(){
    var loadEl = document.getElementById('tplLoading');
    var contEl = document.getElementById('tplContent');

    // Already loaded — just show cached data
    if(_tplLoaded){
        if(loadEl) loadEl.style.display = 'none';
        if(contEl) contEl.style.display = 'block';
        try { renderTemplates(); } catch(e){ console.error(e); }
        return;
    }

    // Prevent a second parallel request
    if(_tplInFlight) return;
    _tplInFlight = true;

    // Restore spinner text in case a previous attempt changed it
    if(loadEl){
        loadEl.style.display = 'block';
        loadEl.innerHTML = '<div class="spinner" style="width:22px;height:22px;margin:0 auto 10px"></div>იტვირთება…';
    }
    if(contEl) contEl.style.display = 'none';

    // Abort if server doesn't respond within 15 seconds
    var ctrl    = new AbortController();
    var timeout = setTimeout(function(){ ctrl.abort(); }, 15000);

    api('GET', '/modifier-templates', null, ctrl.signal).then(function(d){
        clearTimeout(timeout);
        _tplInFlight = false;
        _tplLoaded   = true;
        _templates   = d.templates || [];
        if(loadEl) loadEl.style.display = 'none';
        if(contEl) contEl.style.display = 'block';
        try { renderTemplates(); } catch(e){ console.error('renderTemplates', e); }
        try { pmRenderTplChips(); } catch(e){}
    }).catch(function(err){
        clearTimeout(timeout);
        _tplInFlight = false;
        console.error('loadTemplates error', err);
        if(loadEl) loadEl.innerHTML =
            '<div style="padding:24px;text-align:center">'
            + '<span style="font-size:28px;display:block;margin-bottom:6px">❌</span>'
            + '<span style="color:var(--red);font-size:13px;font-weight:700">ვერ ჩაიტვირთა</span><br>'
            + '<button onclick="loadTemplates()" style="margin-top:12px;padding:7px 18px;border-radius:8px;border:none;background:#f59e0b;color:#fff;cursor:pointer;font-weight:700;font-family:inherit;font-size:13px">🔄 სცადე</button>'
            + '</div>';
    });
}

var TYPE_ICON = {choice:'🔘', exclusion:'✖', size:'📏'};
var TYPE_LABEL = {choice:'ოფცია', exclusion:'გარეშე', size:'ზომა'};
var TYPE_COLOR = {choice:'#dbeafe;color:#1d4ed8', exclusion:'#fee2e2;color:#dc2626', size:'#dcfce7;color:#166534'};

function renderTemplates(){
    var el = document.getElementById('tplGrid');
    if(!_templates.length){
        el.innerHTML = '<div style="padding:40px;text-align:center;color:var(--muted);font-size:13px">📋 შაბლონები არ არის — დაამატე პირველი</div>';
        return;
    }
    el.innerHTML = _templates.map(function(t){
        var icon  = TYPE_ICON[t.type]  || '⚙';
        var label = TYPE_LABEL[t.type] || t.type;
        var color = TYPE_COLOR[t.type] || '#f1f5f9;color:#64748b';
        var items = (t.items||[]).map(function(i){
            return escHtml(i.name) + (parseFloat(i.price||0) !== 0 ? ' <span style="color:var(--amber);font-size:11px">('+parseFloat(i.price).toFixed(2)+SYM+')</span>' : '');
        }).join(' · ');
        return '<div class="tpl-card" onclick="openTplModal('+t.id+')">'
            +'<div class="tpl-card-head">'
            +'<span style="font-size:16px">'+icon+'</span>'
            +'<span class="tpl-card-name">'+escHtml(t.name)+'</span>'
            +'<span style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:6px;background:'+color+'">'+label+'</span>'
            +'</div>'
            +(items ? '<div class="tpl-items">'+items+'</div>' : '')
            +'<button type="button" class="tpl-apply-btn" onclick="event.stopPropagation();applyTplToAll('+t.id+',\''+escHtml(t.name)+'\')">'
            +'⚡ ყველა პროდუქტს მიამატე</button>'
            +'</div>';
    }).join('');
}

function openTplModal(tplId){
    document.getElementById('tplModalTitle').textContent = tplId ? 'შაბლონის რედაქტირება' : 'შაბლონის დამატება';
    document.getElementById('tplDelBtn').style.display = tplId ? 'block' : 'none';
    document.getElementById('tplId').value = tplId || '';
    document.getElementById('tplItemsContainer').innerHTML = '';

    if(tplId){
        var t = _templates.find(function(x){ return String(x.id)===String(tplId); }) || {};
        document.getElementById('tplName').value     = t.name     || '';
        document.getElementById('tplType').value     = t.type     || 'choice';
        document.getElementById('tplRequired').checked = parseInt(t.required||0) === 1;
        document.getElementById('tplMaxSelect').value  = String(t.max_select || 1);
        (t.items||[]).forEach(function(i){ tplAddItem(i); });
    } else {
        document.getElementById('tplName').value      = '';
        document.getElementById('tplType').value      = 'choice';
        document.getElementById('tplRequired').checked = false;
        document.getElementById('tplMaxSelect').value  = '1';
    }
    onTplTypeChange();
    document.getElementById('tplModal').classList.add('open');
    setTimeout(function(){ document.getElementById('tplName').focus(); }, 80);
}

function onTplTypeChange(){
    var type = document.getElementById('tplType').value;
    var row  = document.getElementById('tplChoiceOpts');
    // Size = always required + max 1; exclusion = always unrequired + max 20
    if(type === 'size'){
        row.style.display = 'none';
        document.getElementById('tplRequired').checked = true;
        document.getElementById('tplMaxSelect').value  = '1';
    } else if(type === 'exclusion'){
        row.style.display = 'grid';
        document.getElementById('tplMaxSelect').value  = '20';
    } else {
        row.style.display = 'grid';
    }
}

function tplAddItem(item){
    item = item || {};
    var row = document.createElement('div');
    row.className = 'mod-item-row';
    var type = document.getElementById('tplType').value;
    var ph   = type === 'size' ? 'ზომა (მაგ. S, M, L, XL)' : 'ვარიანტის სახელი';
    row.innerHTML =
        '<input type="text" class="mod-item-name" value="'+escHtml(item.name||'')+'" placeholder="'+escHtml(ph)+'">'
        +'<input type="number" class="mod-item-price" value="'+parseFloat(item.price||0).toFixed(2)+'" step="0.01" placeholder="0.00">'
        +'<span style="font-size:11px;color:var(--muted)">'+SYM+'</span>'
        +'<button type="button" class="mod-item-del" onclick="this.closest(\'.mod-item-row\').remove()">✕</button>';
    document.getElementById('tplItemsContainer').appendChild(row);
    if(!item.name) row.querySelector('.mod-item-name').focus();
}

function saveTpl(){
    var id   = document.getElementById('tplId').value;
    var name = document.getElementById('tplName').value.trim();
    if(!name){ toast('სახელი სავალდებულოა', true); return; }
    var type  = document.getElementById('tplType').value;
    var items = [];
    document.querySelectorAll('#tplItemsContainer .mod-item-row').forEach(function(row){
        var n = (row.querySelector('.mod-item-name')||{}).value || '';
        var p = parseFloat((row.querySelector('.mod-item-price')||{}).value||0)||0;
        if(n.trim()) items.push({name:n.trim(), price:p});
    });
    if(!items.length){ toast('მინიმუმ ერთი ვარიანტი სავალდებულოა', true); return; }

    var body = {
        name:       name,
        type:       type,
        required:   document.getElementById('tplRequired').checked ? 1 : 0,
        max_select: parseInt(document.getElementById('tplMaxSelect').value)||1,
        items:      items,
    };
    var url = id ? '/modifier-templates/'+id+'/update' : '/modifier-templates';
    api('POST', url, body).then(function(d){
        if(!d.ok){ toast(d.error||'Error', true); return; }
        toast('✓ შენახულია');
        closeModal('tplModal');
        _tplLoaded = false;
        loadTemplates();
    }).catch(function(){ toast('სერვერის შეცდომა', true); });
}

function deleteTpl(){
    var id = document.getElementById('tplId').value;
    if(!id||!confirm('შაბლონი წაიშლება?')) return;
    api('POST','/modifier-templates/'+id+'/delete',{}).then(function(d){
        if(d.ok){ toast('წაიშალა'); closeModal('tplModal'); _tplLoaded=false; loadTemplates(); }
        else toast(d.error||'Error', true);
    });
}

function applyTplToAll(tplId, tplName){
    if(!confirm('შაბლონი "'+tplName+'" ყველა პროდუქტს დაემატება (ან განახლდება)? ')) return;
    api('POST','/modifier-templates/'+tplId+'/apply-all',{}).then(function(d){
        if(!d.ok){ toast(d.error||'Error', true); return; }
        toast('✓ '+d.applied+' პროდუქტს დაემატა');
        _menuLoaded = false; // invalidate product modifier cache
    }).catch(function(){ toast('სერვერის შეცდომა', true); });
}

// ── Templates chips in product modal ────────────────────────────────────────

function pmRenderTplChips(){
    var bar   = document.getElementById('pmTplBar');
    var chips = document.getElementById('pmTplChips');
    if(!bar||!chips) return;
    if(!_templates.length){ bar.style.display='none'; return; }
    bar.style.display = 'block';
    chips.innerHTML = _templates.map(function(t){
        var icon = TYPE_ICON[t.type] || '⚙';
        return '<button type="button" class="pm-tpl-chip" onclick="pmApplyTemplate('+t.id+')">'
            +icon+' '+escHtml(t.name)+'</button>';
    }).join('');
}

function pmApplyTemplate(tplId){
    var t = _templates.find(function(x){ return String(x.id)===String(tplId); });
    if(!t) return;
    // Convert template to modifier group and add to modal
    var groupData = {
        name:       t.name,
        type:       t.type,
        required:   t.required,
        max_select: t.max_select,
        modifiers:  (t.items||[]).map(function(i){ return {name:i.name, price:i.price}; }),
    };
    pmAddModGroup(groupData);
    toast('✓ "'+escHtml(t.name)+'" დაემატა');
}

// ── Combo helpers (shared between product modal and any future use) ─────────

/** Build <option> list for a category filter (ყველა + each cat) */
function cmbCatOptions(selectedId){
    var sel = String(selectedId||'');
    return '<option value=""'+(sel===''?' selected':'')+'> ყველა კატეგორია</option>'
        + _cats.map(function(c){
            return '<option value="'+c.id+'"'+(String(c.id)===sel?' selected':'')+'>'+escHtml(c.name)+'</option>';
          }).join('');
}

/** Render product checkboxes inside a section list, filtered by catId */
function renderSecProds(listId, countId, catId, checkedIds){
    var strIds  = (checkedIds||[]).map(String);
    var prods   = catId ? _prods.filter(function(p){ return String(p.category_id)===String(catId); }) : _prods;
    var el      = document.getElementById(listId);
    if(!el) return;
    if(!_prods.length){
        el.innerHTML = '<div style="font-size:12px;color:var(--muted);padding:8px">პროდუქტები არ არის — 🍽 მენიუ ტაბში დაამატე</div>';
        updCmbCount(countId, listId); return;
    }
    if(!prods.length){
        el.innerHTML = '<div style="font-size:12px;color:var(--muted);padding:8px">ამ კატეგორიაში პროდუქტი არ არის</div>';
        updCmbCount(countId, listId); return;
    }
    el.innerHTML = prods.map(function(p){
        var on = strIds.indexOf(String(p.id)) !== -1;
        return '<label class="combo-prod-item'+(on?' selected':'')+'"><input type="checkbox" value="'+p.id+'"'+(on?' checked':'')
            +' onchange="this.closest(\'label\').classList.toggle(\'selected\',this.checked);updCmbCount(\''+countId+'\',\''+listId+'\')">'
            +'<span class="combo-prod-item-name">'+escHtml(p.name)+'</span>'
            +'<span class="combo-prod-item-price">'+parseFloat(p.price||0).toFixed(2)+SYM+'</span></label>';
    }).join('');
    updCmbCount(countId, listId);
}

/** Called when a section's cat-filter changes — preserves checked items */
function filterSecProds(listId, countId, catId){
    var checked = Array.from(document.querySelectorAll('#'+listId+' input[type=checkbox]:checked'))
        .map(function(cb){ return cb.value; });
    renderSecProds(listId, countId, catId, checked);
}

function updCmbCount(countId, listId){
    var n  = document.querySelectorAll('#'+listId+' input[type=checkbox]:checked').length;
    var el = document.getElementById(countId);
    if(!el) return;
    el.textContent = n;
    el.classList.toggle('zero', n===0);
}


// ── Utility ────────────────────────────────────────────────────────────────
function escHtml(s){
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
