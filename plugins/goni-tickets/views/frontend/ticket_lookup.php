<?php
$eventsSlug = $tickets->setting('events_page_slug', 'events');
$hasSearch  = $from !== '' || $to !== '' || $date !== '';
$activeSubnav = 'my-ticket';
include __DIR__ . '/_subnav.php';
?>
<style>
/* ── Page ─────────────────────────────────────────────────────────────── */
.gt-tr-page{background:var(--gt-page-bg,#0a0812);min-height:80vh;position:relative}
.gt-glow{position:absolute;border-radius:50%;filter:blur(160px);pointer-events:none;z-index:0}
.gt-glow-a{width:800px;height:800px;background:rgba(124,58,237,.24);top:-280px;left:50%;transform:translateX(-60%)}
.gt-glow-b{width:600px;height:600px;background:rgba(236,72,153,.13);top:-200px;left:50%;transform:translateX(-5%)}

/* ── Hero ─────────────────────────────────────────────────────────────── */
.gt-tr-hero{position:relative;z-index:1;text-align:center;padding:64px 24px 0}
.gt-tr-hero h1{font-size:clamp(28px,4vw,52px);font-weight:900;color:#f1f5f9;letter-spacing:3px;line-height:1.1;margin-bottom:12px;font-family:'123Wave',sans-serif}
.gt-tr-hero h1 em{font-style:normal;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.gt-tr-hero-sub{font-size:14px;color:#94a3b8;margin-bottom:40px}

/* ── Search section ──────────────────────────────────────────────────── */
.gt-tr-search{position:relative;z-index:1;max-width:1160px;margin:0 auto;padding:0 24px 48px}

/* ── Search card ─────────────────────────────────────────────────────── */
.gt-tr-scard{
    background:#fff;
    border:1px solid rgba(0,0,0,.07);
    border-radius:24px 0 24px 0;
    box-shadow:0 20px 70px rgba(0,0,0,.3),0 4px 16px rgba(0,0,0,.1);
    overflow:hidden;
}
.gt-tr-srow{display:flex;align-items:stretch;height:64px}

/* Every field: flat horizontal row, vertically centred */
.gt-tr-sf{
    display:flex;align-items:center;gap:8px;
    padding:0 20px;
    border-right:1px solid rgba(0,0,0,.06);
    position:relative;transition:background .12s;
    cursor:default;
}
.gt-tr-sf:hover{background:#f8f8ff}
.gt-tr-sf--grow{flex:1 1 0;min-width:200px}
.gt-tr-sf--date{flex-grow:0;flex-shrink:0;width:132px;min-width:132px;max-width:132px;overflow:hidden;cursor:pointer;padding:0 6px;gap:0}
.gt-tr-sf--date .gt-tr-sf-date-val.empty{margin-left:-20px}
.gt-tr-sf--date .gt-tr-sf-date-val:not(.empty){margin-left:2px}
.gt-tr-sf--pax{flex-shrink:0;min-width:108px;cursor:pointer}

/* Icon */
.gt-tr-sf-icon{font-size:14px;flex-shrink:0;color:#94a3b8;line-height:1}

/* Text input (from / to) */
.gt-tr-sf-input{
    border:none;outline:none;
    font-size:14px;font-weight:600;color:#1e1b4b;
    background:transparent;flex:1;min-width:0;
    font-family:inherit;padding:0;
}
.gt-tr-sf-input::placeholder{color:#c4c4d4;font-weight:400}

/* Down-arrow (CSS triangle, no character) */
.gt-tr-sf-arrow{
    width:0;height:0;flex-shrink:0;margin-left:4px;align-self:center;
    border-left:4px solid transparent;
    border-right:4px solid transparent;
    border-top:5px solid #c8c8d8;
}

/* Date value display */
.gt-tr-sf-date-val{
    font-size:14px;font-weight:600;color:#1e1b4b;white-space:nowrap;cursor:pointer;
}
.gt-tr-sf-date-val.empty{color:#c4c4d4;font-weight:400}

/* Date separator */
.gt-tr-datesep{width:1px;flex-shrink:0;background:rgba(0,0,0,.06);align-self:stretch}

/* Passenger value */
.gt-tr-pax-val{font-size:14px;font-weight:600;color:#1e1b4b;flex:1}

/* Swap button */
.gt-tr-sswap-wrap{
    display:flex;align-items:center;justify-content:center;
    width:44px;flex-shrink:0;border-right:1px solid rgba(0,0,0,.06);
}
.gt-tr-sswap{
    background:none;border:none;cursor:pointer;
    width:32px;height:32px;border-radius:50%;
    font-size:17px;color:#94a3b8;
    display:flex;align-items:center;justify-content:center;
    transition:transform .4s,color .15s,background .15s;
}
.gt-tr-sswap:hover{transform:rotate(180deg);color:#7c3aed;background:rgba(124,58,237,.07)}

/* Pax popup */
.gt-tr-pax-popup{
    display:none;position:fixed;z-index:1000;
    background:#fff;border:1px solid rgba(0,0,0,.09);border-radius:16px;
    padding:0;width:300px;
    box-shadow:0 16px 48px rgba(0,0,0,.16),0 4px 12px rgba(0,0,0,.07);
    overflow:hidden;
}
.gt-tr-pax-popup.open{display:block}
.gt-tr-pax-row{
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 20px;border-bottom:1px solid rgba(0,0,0,.05);
}
.gt-tr-pax-row:last-child{border-bottom:none}
.gt-tr-pax-row-name{font-size:14px;font-weight:700;color:#1e1b4b;line-height:1.2}
.gt-tr-pax-row-age{font-size:11.5px;color:#94a3b8;margin-top:2px}
.gt-tr-pax-counter{display:flex;align-items:center;gap:10px;flex-shrink:0}
.gt-tr-pax-btn{
    width:28px;height:28px;border-radius:50%;
    background:#f1f5f9;border:1px solid rgba(0,0,0,.1);
    cursor:pointer;font-size:15px;font-weight:700;color:#1e1b4b;
    display:flex;align-items:center;justify-content:center;
    transition:background .12s,border-color .12s;line-height:1;
}
.gt-tr-pax-btn:hover:not(:disabled){background:rgba(124,58,237,.1);border-color:rgba(124,58,237,.25);color:#7c3aed}
.gt-tr-pax-btn:disabled{opacity:.35;cursor:default}
.gt-tr-pax-num{font-size:15px;font-weight:700;color:#1e1b4b;min-width:20px;text-align:center}
.gt-tr-pax-notice{
    background:#f8f7ff;border-top:1px solid rgba(0,0,0,.05);border-bottom:1px solid rgba(0,0,0,.05);
    padding:12px 20px;
}
.gt-tr-pax-notice p{font-size:12px;color:#94a3b8;margin:0 0 6px;line-height:1.4}
.gt-tr-pax-notice p:last-child{margin-bottom:0}
.gt-tr-pax-notice strong{color:#1e1b4b;font-weight:700}
.gt-tr-pax-wheel{
    display:flex;align-items:flex-start;gap:12px;
    padding:14px 20px;
}
.gt-tr-pax-wheel-icon{
    width:36px;height:36px;border-radius:8px;
    background:#3b5bdb;color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-size:18px;flex-shrink:0;
}
.gt-tr-pax-wheel-text{flex:1;min-width:0}
.gt-tr-pax-wheel-name{font-size:13px;font-weight:700;color:#1e1b4b;line-height:1.2}
.gt-tr-pax-wheel-sub{font-size:11px;color:#94a3b8;margin-top:2px;line-height:1.3}
/* toggle */
.gt-tr-pax-toggle{
    position:relative;width:42px;height:24px;flex-shrink:0;
    cursor:pointer;display:block;
}
.gt-tr-pax-toggle input{opacity:0;width:0;height:0;position:absolute}
.gt-tr-pax-toggle-track{
    position:absolute;inset:0;border-radius:12px;
    background:#e2e8f0;transition:background .2s;
}
.gt-tr-pax-toggle-track::after{
    content:'';position:absolute;
    width:20px;height:20px;border-radius:50%;
    background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.22);
    top:2px;left:2px;transition:transform .2s;
}
.gt-tr-pax-toggle input:checked + .gt-tr-pax-toggle-track{background:#7c3aed}
.gt-tr-pax-toggle input:checked + .gt-tr-pax-toggle-track::after{transform:translateX(18px)}

/* Search button */
.gt-tr-sbtn{
    background:linear-gradient(135deg,#7c3aed,#a855f7);
    color:#fff;border:none;
    border-radius:0 0 24px 0;
    padding:0 28px;font-size:14px;font-weight:700;
    cursor:pointer;font-family:inherit;flex-shrink:0;
    align-self:stretch;
    transition:opacity .15s;white-space:nowrap;
    min-width:120px;letter-spacing:.3px;
}
.gt-tr-sbtn:hover{opacity:.88}

/* Chips bar */
.gt-tr-chips-bar{display:flex;gap:8px;flex-wrap:wrap;padding:14px 2px 0;justify-content:center}
.gt-tr-chipx{
    display:inline-flex;align-items:center;gap:6px;
    border-radius:30px;padding:8px 20px;
    font-size:13.5px;font-weight:600;
    cursor:pointer;font-family:inherit;
    background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16);color:#e2e8f0;
    transition:all .15s;letter-spacing:.1px;
}
.gt-tr-chipx:hover{background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.25);color:#cbd5e1}
.gt-tr-chipx.active{background:rgba(124,58,237,.25);border-color:rgba(167,139,250,.5);color:#e9d5ff;font-weight:700}
html.gt-light .gt-tr-chipx{background:rgba(0,0,0,.05);border-color:rgba(0,0,0,.12);color:#64748b}
html.gt-light .gt-tr-chipx:hover{background:rgba(0,0,0,.09);border-color:rgba(0,0,0,.18);color:#334155}
html.gt-light .gt-tr-chipx.active{background:rgba(124,58,237,.12);border-color:rgba(124,58,237,.35);color:#6d28d9;font-weight:700}

/* ── Custom datepicker ────────────────────────────────────────────────── */
.gt-dp-wrap{position:relative;flex:1;display:flex;align-items:center}
.gt-dp-popup{
    display:none;position:fixed;z-index:1000;
    background:#fff;border:1px solid rgba(0,0,0,.09);
    border-radius:18px;padding:22px 20px 18px;
    width:580px;box-shadow:0 20px 60px rgba(0,0,0,.18),0 4px 16px rgba(0,0,0,.08);
}
.gt-dp-popup.open{display:block}
.gt-dp-title{
    font-size:15px;font-weight:800;color:#1e1b4b;
    text-align:center;margin-bottom:16px;letter-spacing:-.1px;
}
.gt-dp-months{
    display:grid;grid-template-columns:1fr 1fr;
    gap:0;position:relative;
}
.gt-dp-month{padding:0 10px}
.gt-dp-month:first-child{border-right:1px solid rgba(0,0,0,.07);padding-right:16px}
.gt-dp-month:last-child{padding-left:16px}
.gt-dp-month-label{
    font-size:13px;font-weight:700;color:#1e1b4b;
    text-align:center;margin-bottom:10px;
}
.gt-dp-nav-prev,.gt-dp-nav-next{
    position:absolute;top:-4px;
    background:transparent;border:1px solid rgba(0,0,0,.1);border-radius:8px;
    color:#7c3aed;font-size:18px;cursor:pointer;
    width:30px;height:30px;display:flex;align-items:center;justify-content:center;
    transition:background .12s,border-color .12s;
    z-index:1;
}
.gt-dp-nav-prev{left:0}
.gt-dp-nav-next{right:0}
.gt-dp-nav-prev:hover,.gt-dp-nav-next:hover{background:rgba(124,58,237,.08);border-color:rgba(124,58,237,.3)}
.gt-dp-weekdays{display:grid;grid-template-columns:repeat(7,1fr);margin-bottom:4px}
.gt-dp-wd{font-size:10px;font-weight:600;color:#94a3b8;text-align:center;padding:3px 0}
.gt-dp-days{display:grid;grid-template-columns:repeat(7,1fr);gap:1px}
.gt-dp-day{
    font-size:12.5px;font-weight:500;color:#1e1b4b;
    text-align:center;aspect-ratio:1;border-radius:50%;cursor:pointer;
    border:none;background:transparent;
    display:flex;align-items:center;justify-content:center;
    width:100%;transition:background .12s,color .12s;
    padding:0;
}
.gt-dp-day:hover:not(.gt-dp-day--past):not(.gt-dp-day--empty){background:rgba(124,58,237,.1);color:#7c3aed}
.gt-dp-day--today{border:2px solid #7c3aed;color:#7c3aed;font-weight:700}
.gt-dp-day--sel{background:#7c3aed !important;color:#fff !important;font-weight:700;border:none}
.gt-dp-day--past{color:#d1d5db;cursor:default}
.gt-dp-day--empty{cursor:default;visibility:hidden}
@media(max-width:640px){
    .gt-dp-popup{width:calc(100vw - 32px) !important;left:16px !important}
    .gt-dp-months{grid-template-columns:1fr}
    .gt-dp-month:first-child{border-right:none;border-bottom:1px solid rgba(0,0,0,.07);padding-right:10px;padding-bottom:14px;margin-bottom:14px}
    .gt-dp-month:last-child{padding-left:10px}
}

/* ── Results ─────────────────────────────────────────────────────────── */
.gt-tr-results{position:relative;z-index:1;max-width:1160px;margin:0 auto;padding:0 24px 80px}
.gt-tr-results-head{display:flex;align-items:center;gap:14px;margin-bottom:20px}
.gt-tr-results-head span{font-size:11px;font-weight:800;color:#a78bfa;text-transform:uppercase;letter-spacing:1.5px;white-space:nowrap}
.gt-tr-results-head::before,.gt-tr-results-head::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.06)}

.gt-tr-ticket{
    background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
    border-radius:16px 0 16px 0;display:grid;grid-template-columns:1fr auto;
    align-items:center;gap:20px;padding:22px 24px;margin-bottom:10px;
    text-decoration:none;transition:background .15s,border-color .15s;
}
.gt-tr-ticket:hover{background:rgba(124,58,237,.07);border-color:rgba(124,58,237,.25);text-decoration:none}
.gt-tr-route{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.gt-tr-point{font-size:15px;font-weight:900;color:#f1f5f9;line-height:1}
.gt-tr-arrow{color:#a78bfa;font-size:18px;flex-shrink:0}
.gt-tr-meta{font-size:12px;color:#94a3b8;display:flex;align-items:center;flex-wrap:wrap;gap:10px}
.gt-tr-title{font-size:13px;font-weight:700;color:#94a3b8;margin-bottom:6px}
.gt-tr-right{text-align:right;flex-shrink:0}
.gt-tr-time{font-size:22px;font-weight:900;color:#f1f5f9;letter-spacing:-.5px;line-height:1}
.gt-tr-date{font-size:11px;color:#94a3b8;margin-top:3px}
.gt-tr-price{display:inline-block;background:rgba(167,139,250,.12);color:#a78bfa;border-radius:10px;padding:4px 12px;font-size:13px;font-weight:800;margin-top:8px}
.gt-tr-empty{text-align:center;padding:40px 24px;color:#94a3b8}
.gt-tr-empty-icon{font-size:40px;margin-bottom:12px}

/* ── Light mode ──────────────────────────────────────────────────────── */
html.gt-light .gt-tr-page{background:#f5f3ff}
html.gt-light .gt-tr-hero h1{color:#1e1b4b}
html.gt-light .gt-tr-hero-sub{color:#64748b}
html.gt-light .gt-tr-ticket{background:#fff;border-color:rgba(0,0,0,.07)}
html.gt-light .gt-tr-ticket:hover{background:rgba(124,58,237,.04);border-color:rgba(124,58,237,.2)}
html.gt-light .gt-tr-point{color:#1e1b4b}
html.gt-light .gt-tr-time{color:#1e1b4b}
html.gt-light .gt-tr-results-head span{color:#7c3aed}
html.gt-light .gt-tr-results-head::before,
html.gt-light .gt-tr-results-head::after{background:rgba(0,0,0,.08)}

/* ── Background vehicle silhouettes ─────────────────────────────────── */
.gt-bg-deco{position:absolute;inset:0;overflow:hidden;pointer-events:none;z-index:0}
.gt-bg-deco svg{position:absolute;fill:none}
.gt-bg-train{
    bottom:-2%;left:-60px;width:600px;
    color:rgba(167,139,250,.07);
    transform:rotate(-4deg);
}
.gt-bg-bus{
    bottom:5%;right:-40px;width:460px;
    color:rgba(236,72,153,.06);
    transform:rotate(3deg);
}
.gt-bg-plane{
    top:8%;right:2%;width:320px;
    color:rgba(167,139,250,.08);
    transform:rotate(-12deg);
}
.gt-bg-car{
    top:38%;left:5%;width:380px;
    color:rgba(236,72,153,.07);
    transform:rotate(6deg);
}
html.gt-light .gt-bg-train{color:rgba(124,58,237,.05)}
html.gt-light .gt-bg-bus{color:rgba(236,72,153,.04)}
html.gt-light .gt-bg-plane{color:rgba(124,58,237,.06)}
html.gt-light .gt-bg-car{color:rgba(236,72,153,.05)}

/* ── Responsive ──────────────────────────────────────────────────────── */
@media(max-width:860px){
    .gt-tr-srow{flex-wrap:wrap;height:auto}
    .gt-tr-sf--grow{flex:1;min-width:calc(50% - 22px);height:56px}
    .gt-tr-sswap-wrap{width:44px;flex-shrink:0;border-right:none;border-bottom:1px solid rgba(0,0,0,.06);height:44px}
    .gt-tr-sf--date{flex:1;height:56px}
    .gt-tr-datesep{display:none}
    .gt-tr-sf--pax{flex:1;height:56px}
    .gt-tr-sbtn{border-radius:0 0 24px 0;width:100%;padding:16px;height:52px;align-self:auto}
}
@media(max-width:520px){
    .gt-tr-sf--grow{min-width:100%;border-right:none;border-bottom:1px solid rgba(0,0,0,.06)}
    .gt-tr-sf{border-right:none}
    .gt-tr-sswap-wrap{display:none}
    .gt-tr-ticket{grid-template-columns:1fr;gap:12px}
    .gt-tr-right{text-align:left}
}
</style>

<div class="gt-tr-page">
    <div class="gt-glow gt-glow-a"></div>
    <div class="gt-glow gt-glow-b"></div>

    <div class="gt-bg-deco">
        <!-- Train silhouette -->
        <svg class="gt-bg-train" viewBox="0 0 360 130" xmlns="http://www.w3.org/2000/svg">
            <rect x="8" y="32" width="344" height="64" rx="10" stroke="currentColor" stroke-width="3"/>
            <rect x="270" y="10" width="74" height="28" rx="7" stroke="currentColor" stroke-width="2.5"/>
            <rect x="28" y="43" width="36" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="80" y="43" width="36" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="132" y="43" width="36" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="184" y="43" width="36" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="236" y="43" width="26" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="284" y="43" width="36" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <circle cx="55" cy="108" r="14" stroke="currentColor" stroke-width="2.5"/>
            <circle cx="145" cy="108" r="14" stroke="currentColor" stroke-width="2.5"/>
            <circle cx="225" cy="108" r="14" stroke="currentColor" stroke-width="2.5"/>
            <circle cx="305" cy="108" r="14" stroke="currentColor" stroke-width="2.5"/>
            <line x1="55" y1="108" x2="305" y2="108" stroke="currentColor" stroke-width="1.5"/>
            <rect x="0" y="60" width="10" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
            <rect x="8" y="15" width="14" height="20" rx="3" stroke="currentColor" stroke-width="2"/>
        </svg>

        <!-- Bus silhouette -->
        <svg class="gt-bg-bus" viewBox="0 0 320 120" xmlns="http://www.w3.org/2000/svg">
            <path d="M22 88 L22 28 C22 22 28 16 35 16 L285 16 C295 16 302 24 302 34 L302 88" stroke="currentColor" stroke-width="3"/>
            <path d="M35 16 L35 6 C35 3 39 1 44 1 L276 1 C281 1 285 3 285 6 L285 16" stroke="currentColor" stroke-width="2"/>
            <line x1="16" y1="88" x2="308" y2="88" stroke="currentColor" stroke-width="3"/>
            <path d="M285 34 L300 34 L300 72 L285 72" stroke="currentColor" stroke-width="2"/>
            <rect x="36" y="24" width="32" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="80" y="24" width="32" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="124" y="24" width="32" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="168" y="24" width="32" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="212" y="24" width="32" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="255" y="24" width="24" height="24" rx="4" stroke="currentColor" stroke-width="2"/>
            <path d="M36 58 L36 88 M58 58 L58 88 M36 58 L58 58" stroke="currentColor" stroke-width="2"/>
            <circle cx="72" cy="102" r="14" stroke="currentColor" stroke-width="2.5"/>
            <circle cx="252" cy="102" r="14" stroke="currentColor" stroke-width="2.5"/>
        </svg>

        <!-- Car silhouette -->
        <svg class="gt-bg-car" viewBox="0 0 340 120" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 72 L30 56 C30 50 34 44 40 44 L70 44 L90 22 C94 17 100 14 107 14 L220 14 C228 14 234 18 238 24 L260 44 L300 44 C308 44 314 50 314 58 L314 72" stroke="currentColor" stroke-width="3"/>
            <line x1="22" y1="72" x2="320" y2="72" stroke="currentColor" stroke-width="3"/>
            <path d="M22 72 C22 72 14 72 12 76 L12 84 L328 84 L328 76 C326 72 320 72 320 72" stroke="currentColor" stroke-width="2"/>
            <path d="M95 44 L108 20 L225 20 L248 44" stroke="currentColor" stroke-width="2"/>
            <rect x="104" y="22" width="55" height="20" rx="4" stroke="currentColor" stroke-width="2"/>
            <rect x="168" y="22" width="55" height="20" rx="4" stroke="currentColor" stroke-width="2"/>
            <circle cx="80" cy="90" r="18" stroke="currentColor" stroke-width="2.5"/>
            <circle cx="80" cy="90" r="8" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="264" cy="90" r="18" stroke="currentColor" stroke-width="2.5"/>
            <circle cx="264" cy="90" r="8" stroke="currentColor" stroke-width="1.5"/>
            <path d="M14 76 L14 66 C14 64 16 62 18 62 L30 62" stroke="currentColor" stroke-width="2"/>
            <path d="M326 76 L326 66 C326 64 324 62 322 62 L310 62" stroke="currentColor" stroke-width="2"/>
            <rect x="32" y="54" width="20" height="12" rx="3" stroke="currentColor" stroke-width="1.5"/>
            <rect x="290" y="54" width="20" height="12" rx="3" stroke="currentColor" stroke-width="1.5"/>
        </svg>

        <!-- Airplane silhouette (top-down) -->
        <svg class="gt-bg-plane" viewBox="0 0 200 220" xmlns="http://www.w3.org/2000/svg">
            <path d="M100 8 C112 8 124 35 126 105 C126 148 118 182 100 192 C82 182 74 148 74 105 C76 35 88 8 100 8Z" stroke="currentColor" stroke-width="3"/>
            <path d="M76 95 C66 85 32 68 8 76 C8 88 32 102 76 108" stroke="currentColor" stroke-width="2.5"/>
            <path d="M124 95 C134 85 168 68 192 76 C192 88 168 102 124 108" stroke="currentColor" stroke-width="2.5"/>
            <path d="M78 162 C70 156 52 164 56 174 C62 176 72 172 78 168" stroke="currentColor" stroke-width="2.5"/>
            <path d="M122 162 C130 156 148 164 144 174 C138 176 128 172 122 168" stroke="currentColor" stroke-width="2.5"/>
            <ellipse cx="50" cy="90" rx="14" ry="6" stroke="currentColor" stroke-width="2"/>
            <ellipse cx="150" cy="90" rx="14" ry="6" stroke="currentColor" stroke-width="2"/>
            <circle cx="100" cy="68" r="4" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="100" cy="88" r="4" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="100" cy="108" r="4" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="100" cy="128" r="4" stroke="currentColor" stroke-width="1.5"/>
        </svg>
    </div>

    <div class="gt-tr-hero">
        <h1>იპოვე შენი <em>მარშრუტი</em></h1>
        <p class="gt-tr-hero-sub">ავტობუსი · მატარებელი · სხვა ტრანსპორტი</p>
    </div>

    <div class="gt-tr-search">
        <div class="gt-tr-scard">
            <form method="GET" action="<?= e($base) ?>/tickets/my-ticket" id="gtTrForm">
                <input type="hidden" name="transport" id="gtTransportVal" value="<?= e($transport ?? 'all') ?>">
                <input type="hidden" name="passengers" id="gtPaxVal" value="1">
                <input type="hidden" name="children" id="gtChildVal" value="0">
                <input type="hidden" name="wheelchair" id="gtWheelVal" value="0">

                <div class="gt-tr-srow">

                    <!-- From -->
                    <div class="gt-tr-sf gt-tr-sf--grow">
                        <span class="gt-tr-sf-icon">⊙</span>
                        <input class="gt-tr-sf-input" type="text" name="from"
                               value="<?= e($from) ?>" placeholder="საიდან"
                               autocomplete="off" id="gtFromInput">
                    </div>

                    <!-- Swap -->
                    <div class="gt-tr-sswap-wrap">
                        <button type="button" class="gt-tr-sswap" id="gtSwapBtn" title="შეცვლა">↻</button>
                    </div>

                    <!-- To -->
                    <div class="gt-tr-sf gt-tr-sf--grow">
                        <span class="gt-tr-sf-icon">📍</span>
                        <input class="gt-tr-sf-input" type="text" name="to"
                               value="<?= e($to) ?>" placeholder="სად"
                               autocomplete="off" id="gtToInput">
                    </div>

                    <!-- Depart date -->
                    <div class="gt-tr-sf gt-tr-sf--date" id="gtDateField1">
                        <span class="gt-tr-sf-icon">📅</span>
                        <div class="gt-tr-sf-date-val gt-dp-input empty" id="gtDpDisplay">გამგზავრება</div>
                        <input type="hidden" name="date" id="gtDpValue" value="<?= e($date) ?>">
                    </div>
                    <div class="gt-dp-popup" id="gtDpPopup">
                        <div class="gt-dp-title">გამგზავრების თარიღი</div>
                        <div class="gt-dp-months">
                            <button type="button" class="gt-dp-nav-prev" id="gtDpPrev">‹</button>
                            <div class="gt-dp-month">
                                <div class="gt-dp-month-label" id="gtDpLabel"></div>
                                <div class="gt-dp-weekdays"><?php foreach (['ორშ','სამ','ოთხ','ხუთ','პარ','შაბ','კვი'] as $w): ?><div class="gt-dp-wd"><?= $w ?></div><?php endforeach ?></div>
                                <div class="gt-dp-days" id="gtDpDays"></div>
                            </div>
                            <div class="gt-dp-month">
                                <div class="gt-dp-month-label" id="gtDpLabel1b"></div>
                                <div class="gt-dp-weekdays"><?php foreach (['ორშ','სამ','ოთხ','ხუთ','პარ','შაბ','კვი'] as $w): ?><div class="gt-dp-wd"><?= $w ?></div><?php endforeach ?></div>
                                <div class="gt-dp-days" id="gtDpDays1b"></div>
                            </div>
                            <button type="button" class="gt-dp-nav-next" id="gtDpNext">›</button>
                        </div>
                    </div>

                    <!-- Date separator -->
                    <div class="gt-tr-datesep"></div>

                    <!-- Return date -->
                    <div class="gt-tr-sf gt-tr-sf--date" id="gtDateField2">
                        <span class="gt-tr-sf-icon">📅</span>
                        <div class="gt-tr-sf-date-val gt-dp-input empty" id="gtDpDisplay2">დაბრუნება</div>
                        <input type="hidden" name="ret_date" id="gtDpValue2" value="">
                    </div>
                    <div class="gt-dp-popup" id="gtDpPopup2">
                        <div class="gt-dp-title">დაბრუნების თარიღი</div>
                        <div class="gt-dp-months">
                            <button type="button" class="gt-dp-nav-prev" id="gtDpPrev2">‹</button>
                            <div class="gt-dp-month">
                                <div class="gt-dp-month-label" id="gtDpLabel2"></div>
                                <div class="gt-dp-weekdays"><?php foreach (['ორშ','სამ','ოთხ','ხუთ','პარ','შაბ','კვი'] as $w): ?><div class="gt-dp-wd"><?= $w ?></div><?php endforeach ?></div>
                                <div class="gt-dp-days" id="gtDpDays2"></div>
                            </div>
                            <div class="gt-dp-month">
                                <div class="gt-dp-month-label" id="gtDpLabel2b"></div>
                                <div class="gt-dp-weekdays"><?php foreach (['ორშ','სამ','ოთხ','ხუთ','პარ','შაბ','კვი'] as $w): ?><div class="gt-dp-wd"><?= $w ?></div><?php endforeach ?></div>
                                <div class="gt-dp-days" id="gtDpDays2b"></div>
                            </div>
                            <button type="button" class="gt-dp-nav-next" id="gtDpNext2">›</button>
                        </div>
                    </div>

                    <!-- Passengers -->
                    <div class="gt-tr-sf gt-tr-sf--pax" id="gtPaxField">
                        <span class="gt-tr-sf-icon">👥</span>
                        <span class="gt-tr-pax-val" id="gtPaxDisplay">1 მგზავრი</span>
                        <span class="gt-tr-sf-arrow"></span>
                    </div>
                    <div class="gt-tr-pax-popup" id="gtPaxPopup">
                        <!-- Adults -->
                        <div class="gt-tr-pax-row">
                            <div>
                                <div class="gt-tr-pax-row-name">ზრდასრული</div>
                                <div class="gt-tr-pax-row-age">10 წლიდან</div>
                            </div>
                            <div class="gt-tr-pax-counter">
                                <button type="button" class="gt-tr-pax-btn" id="gtAdultMinus">−</button>
                                <span class="gt-tr-pax-num" id="gtAdultCount">1</span>
                                <button type="button" class="gt-tr-pax-btn" id="gtAdultPlus">+</button>
                            </div>
                        </div>
                        <!-- Children -->
                        <div class="gt-tr-pax-row">
                            <div>
                                <div class="gt-tr-pax-row-name">ბავშვი</div>
                                <div class="gt-tr-pax-row-age">3-დან 10 წლამდე</div>
                            </div>
                            <div class="gt-tr-pax-counter">
                                <button type="button" class="gt-tr-pax-btn" id="gtChildMinus">−</button>
                                <span class="gt-tr-pax-num" id="gtChildCount">0</span>
                                <button type="button" class="gt-tr-pax-btn" id="gtChildPlus">+</button>
                            </div>
                        </div>
                        <!-- Info notice -->
                        <div class="gt-tr-pax-notice">
                            <p><strong>5 წლამდე ასაკის ბავშვი</strong> ბილეთი არ სჭირდება</p>
                            <p>18 წლამდე ასაკის მგზავრი სრულწლოვანი პირის გარეშე რეისზე არ დაიშვება</p>
                        </div>
                        <!-- Wheelchair -->
                        <div class="gt-tr-pax-wheel">
                            <div class="gt-tr-pax-wheel-icon">♿</div>
                            <div class="gt-tr-pax-wheel-text">
                                <div class="gt-tr-pax-wheel-name">ეტლით მოსარგებელი</div>
                                <div class="gt-tr-pax-wheel-sub">შესაძლებელია მხოლოდ 1 ბილეთის ყიდვა</div>
                            </div>
                            <label class="gt-tr-pax-toggle">
                                <input type="checkbox" id="gtWheelchair">
                                <span class="gt-tr-pax-toggle-track"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Search -->
                    <button type="submit" class="gt-tr-sbtn">ძებნა</button>

                </div>
            </form>
        </div>

        <!-- Transport type chips -->
        <div class="gt-tr-chips-bar" id="gtTransportChips">
            <?php $_tc = $transport ?? 'all'; ?>
            <button type="button" class="gt-tr-chipx<?= $_tc==='all'   ?' active':'' ?>" data-val="all">🎟 ყველა</button>
            <button type="button" class="gt-tr-chipx<?= $_tc==='train' ?' active':'' ?>" data-val="train">🚂 მატარებელი</button>
            <button type="button" class="gt-tr-chipx<?= $_tc==='bus'   ?' active':'' ?>" data-val="bus">🚌 ავტობუსი</button>
            <button type="button" class="gt-tr-chipx<?= $_tc==='other' ?' active':'' ?>" data-val="other">✈ სხვა</button>
        </div>
    </div>

    <!-- Results -->
    <?php if ($hasSearch): ?>
    <div class="gt-tr-results">

        <?php if (empty($results)): ?>
        <div class="gt-tr-empty">
            <div class="gt-tr-empty-icon">🚌</div>
            <p style="font-size:15px;color:#e2e8f0;font-weight:700;margin-bottom:6px">მარშრუტი ვერ მოიძებნა</p>
            <p>სცადეთ სხვა პუნქტი ან თარიღი</p>
        </div>

        <?php else: ?>
        <div class="gt-tr-results-head">
            <span><?= count($results) ?> მარშრუტი</span>
        </div>

        <?php foreach ($results as $ev):
            $minPrice = $tickets->minPriceForEvent((int)$ev['id']);
            $soldOut  = $tickets->isSoldOut((int)$ev['id']);
            $eventUrl = e($base) . '/' . e($eventsSlug) . '/' . e($ev['slug']);
            $depTime  = date('H:i', strtotime($ev['event_date']));
            $depDate  = date('d.m.Y', strtotime($ev['event_date']));
            $venue    = trim((string)($ev['venue'] ?? ''));
            $location = trim((string)($ev['location'] ?? ''));
            $catIcon  = $ev['category'] === 'train' ? '🚂' : '🚌';
            $catLabel = $ev['category'] === 'train' ? 'მატარებელი' : 'ავტობუსი';
            $orgId    = (int)($ev['organizer_id'] ?? 0);
            $org      = ($orgId && isset($orgMap[$orgId])) ? $orgMap[$orgId] : null;
        ?>
        <a href="<?= $eventUrl ?>" class="gt-tr-ticket">
            <div>
                <div class="gt-tr-route">
                    <span class="gt-tr-point"><?= e($venue ?: '—') ?></span>
                    <span class="gt-tr-arrow">→</span>
                    <span class="gt-tr-point"><?= e($location ?: '—') ?></span>
                </div>
                <div class="gt-tr-title"><?= e($ev['title']) ?></div>
                <div class="gt-tr-meta">
                    <span><?= $catIcon ?> <?= $catLabel ?></span>
                    <?php if ($org): ?>
                    <span>· 👤 <?= e($org['name']) ?></span>
                    <?php endif ?>
                    <?php if ($soldOut): ?>
                    <span style="color:#fca5a5">· ადგილი არ არის</span>
                    <?php endif ?>
                </div>
            </div>
            <div class="gt-tr-right">
                <div class="gt-tr-time"><?= $depTime ?></div>
                <div class="gt-tr-date"><?= $depDate ?></div>
                <?php if (!$soldOut && $minPrice !== null): ?>
                <div class="gt-tr-price"><?= $minPrice > 0 ? $tickets->formatPrice($minPrice) : 'უფასო' ?></div>
                <?php elseif ($soldOut): ?>
                <div class="gt-tr-price" style="background:rgba(239,68,68,.1);color:#fca5a5">Sold Out</div>
                <?php endif ?>
            </div>
        </a>
        <?php endforeach ?>
        <?php endif ?>

    </div>
    <?php endif ?>

</div>

<script>
(function(){
    /* ── Swap ─────────────────────────────────────────────────── */
    var swapBtn = document.getElementById('gtSwapBtn');
    if (swapBtn) {
        swapBtn.addEventListener('click', function(){
            var f = document.getElementById('gtFromInput');
            var t = document.getElementById('gtToInput');
            if (!f || !t) return;
            var tmp = f.value; f.value = t.value; t.value = tmp;
        });
    }

    /* ── Quick city links ─────────────────────────────────────── */
    document.querySelectorAll('.gt-tr-sf--grow:first-child .gt-tr-sf-quick span').forEach(function(s){
        s.addEventListener('click', function(){
            var inp = document.getElementById('gtFromInput');
            if(inp){ inp.value = s.textContent; inp.focus(); }
        });
    });
    var toQuicks = document.querySelectorAll('.gt-tr-sf--grow');
    if(toQuicks.length >= 2){
        toQuicks[1].querySelectorAll('.gt-tr-sf-quick span').forEach(function(s){
            s.addEventListener('click', function(){
                var inp = document.getElementById('gtToInput');
                if(inp){ inp.value = s.textContent; inp.focus(); }
            });
        });
    }

    /* ── Transport chips ──────────────────────────────────────── */
    var chips     = document.querySelectorAll('.gt-tr-chipx');
    var transInput = document.getElementById('gtTransportVal');
    chips.forEach(function(chip){
        chip.addEventListener('click', function(){
            chips.forEach(function(c){ c.classList.remove('active'); });
            chip.classList.add('active');
            if (transInput) transInput.value = chip.dataset.val;
        });
    });

    /* ── Passenger counter ────────────────────────────────────── */
    var paxField    = document.getElementById('gtPaxField');
    var paxPopup    = document.getElementById('gtPaxPopup');
    var paxDisplay  = document.getElementById('gtPaxDisplay');
    var paxInput    = document.getElementById('gtPaxVal');
    var adultCount  = document.getElementById('gtAdultCount');
    var childCount  = document.getElementById('gtChildCount');
    var adultPlus   = document.getElementById('gtAdultPlus');
    var adultMinus  = document.getElementById('gtAdultMinus');
    var childPlus   = document.getElementById('gtChildPlus');
    var childMinus  = document.getElementById('gtChildMinus');
    var wheelchair  = document.getElementById('gtWheelchair');
    var childValInput = document.getElementById('gtChildVal');
    var wheelValInput = document.getElementById('gtWheelVal');
    var adults = 1, children = 0;

    if (paxPopup) {
        document.body.appendChild(paxPopup);
        paxPopup.style.position = 'fixed';
    }

    function positionPaxPopup() {
        if (!paxField || !paxPopup) return;
        var rect = paxField.getBoundingClientRect();
        var pw = paxPopup.offsetWidth || 300;
        var ph = paxPopup.offsetHeight || 280;
        var top = rect.top - ph - 10;
        if (top < 8) top = rect.bottom + 10;
        var left = rect.right - pw;
        left = Math.max(8, Math.min(left, window.innerWidth - pw - 8));
        paxPopup.style.top  = top + 'px';
        paxPopup.style.left = left + 'px';
    }

    function updatePaxDisplay() {
        if (!paxDisplay) return;
        var total = adults + children;
        var parts = [];
        if (adults)   parts.push(adults + ' ზრდ');
        if (children) parts.push(children + ' ბავშ');
        paxDisplay.textContent = parts.length ? parts.join(', ') : '0 მგზავრი';
        if (paxInput)      paxInput.value = total;
        if (childValInput) childValInput.value = children;
        if (adultCount)    adultCount.textContent = adults;
        if (childCount)    childCount.textContent = children;
        if (adultMinus)    adultMinus.disabled = adults <= 1;
        if (childMinus)    childMinus.disabled = children <= 0;
    }

    if (paxField) {
        paxField.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.gt-dp-popup.open').forEach(function(p){ p.classList.remove('open'); });
            var opening = !paxPopup.classList.contains('open');
            paxPopup.classList.toggle('open');
            if (opening) positionPaxPopup();
        });
    }
    adultPlus && adultPlus.addEventListener('click', function(e) {
        e.stopPropagation(); if(adults < 9) { adults++; updatePaxDisplay(); }
    });
    adultMinus && adultMinus.addEventListener('click', function(e) {
        e.stopPropagation(); if(adults > 1) { adults--; updatePaxDisplay(); }
    });
    childPlus && childPlus.addEventListener('click', function(e) {
        e.stopPropagation(); if(children < 9) { children++; updatePaxDisplay(); }
    });
    childMinus && childMinus.addEventListener('click', function(e) {
        e.stopPropagation(); if(children > 0) { children--; updatePaxDisplay(); }
    });
    wheelchair && wheelchair.addEventListener('change', function(e) {
        e.stopPropagation();
        if (wheelValInput) wheelValInput.value = wheelchair.checked ? '1' : '0';
    });
    document.addEventListener('click', function(e) {
        if (paxPopup && paxField &&
            !paxField.contains(e.target) && !paxPopup.contains(e.target)) {
            paxPopup.classList.remove('open');
        }
    });
    updatePaxDisplay();

    /* ── Double-month datepicker ─────────────────────────────── */
    var GEO_MONTHS = ['იანვარი','თებერვალი','მარტი','აპრილი','მაისი','ივნისი',
                      'ივლისი','აგვისტო','სექტემბერი','ოქტომბერი','ნოემბერი','დეკემბერი'];
    var _today = new Date(); _today.setHours(0,0,0,0);
    function pad(n){ return n < 10 ? '0'+n : ''+n; }

    function makeDatepicker(cfg) {
        var display  = document.getElementById(cfg.displayId);
        var hidden   = document.getElementById(cfg.hiddenId);
        var popup    = document.getElementById(cfg.popupId);
        var trigger  = document.getElementById(cfg.triggerId) || display;
        var label1   = document.getElementById(cfg.label1Id);
        var days1    = document.getElementById(cfg.days1Id);
        var label2   = document.getElementById(cfg.label2Id);
        var days2    = document.getElementById(cfg.days2Id);
        var prevBtn  = document.getElementById(cfg.prevId);
        var nextBtn  = document.getElementById(cfg.nextId);
        if (!display || !popup) return;

        /* portal to body so overflow:hidden on card doesn't clip it */
        document.body.appendChild(popup);
        popup.style.position = 'fixed';

        var min      = _today;
        var selDate  = hidden && hidden.value ? new Date(hidden.value) : null;
        var viewYear = selDate ? selDate.getFullYear() : min.getFullYear();
        var viewMonth= selDate ? selDate.getMonth()    : min.getMonth();

        /* show pre-selected date (from URL param) in display without auto-opening */
        if (selDate) {
            display.textContent = pad(selDate.getDate())+'.'+pad(selDate.getMonth()+1)+'.'+(selDate.getFullYear()+'').slice(-2);
            display.classList.remove('empty');
        }

        function renderMonth(labelEl, daysEl, year, month) {
            if (labelEl) labelEl.textContent = GEO_MONTHS[month] + ' ' + year;
            var first = (new Date(year, month, 1).getDay() + 6) % 7; /* Mon=0 */
            var total = new Date(year, month + 1, 0).getDate();
            daysEl.innerHTML = '';
            for (var i = 0; i < first; i++) {
                var sp = document.createElement('div');
                sp.className = 'gt-dp-day gt-dp-day--empty';
                daysEl.appendChild(sp);
            }
            for (var d = 1; d <= total; d++) {
                var btn = document.createElement('button');
                btn.type = 'button'; btn.textContent = d;
                var dt = new Date(year, month, d);
                var cls = 'gt-dp-day';
                if (dt < min)                                       cls += ' gt-dp-day--past';
                if (dt.getTime() === _today.getTime())              cls += ' gt-dp-day--today';
                if (selDate && dt.getTime() === selDate.getTime())  cls += ' gt-dp-day--sel';
                btn.className = cls;
                if (dt >= min) {
                    (function(dd, vy, vm, thisDate){
                        btn.addEventListener('click', function(){
                            selDate = thisDate;
                            if (hidden) hidden.value = vy+'-'+pad(vm+1)+'-'+pad(dd);
                            display.textContent = pad(dd)+'.'+pad(vm+1)+'.'+(vy+'').slice(-2);
                            display.classList.remove('empty');
                            popup.classList.remove('open');
                            render();
                        });
                    })(d, year, month, dt);
                }
                daysEl.appendChild(btn);
            }
        }

        function render() {
            renderMonth(label1, days1, viewYear, viewMonth);
            var m2 = viewMonth + 1, y2 = viewYear;
            if (m2 > 11) { m2 = 0; y2++; }
            renderMonth(label2, days2, y2, m2);
        }

        function positionPopup() {
            var rect = trigger.getBoundingClientRect();
            var pw   = popup.offsetWidth || 580;
            var left = rect.left + rect.width / 2 - pw / 2;
            left = Math.max(8, Math.min(left, window.innerWidth - pw - 8));
            popup.style.top  = (rect.bottom + 10) + 'px';
            popup.style.left = left + 'px';
        }

        trigger.addEventListener('click', function(ev) {
            ev.stopPropagation();
            document.querySelectorAll('.gt-dp-popup.open').forEach(function(p) {
                if (p !== popup) p.classList.remove('open');
            });
            if (paxPopup) paxPopup.classList.remove('open');
            var opening = !popup.classList.contains('open');
            popup.classList.toggle('open');
            if (opening) { render(); positionPopup(); }
        });
        prevBtn && prevBtn.addEventListener('click', function(ev) {
            ev.stopPropagation();
            viewMonth--; if (viewMonth < 0) { viewMonth = 11; viewYear--; } render();
        });
        nextBtn && nextBtn.addEventListener('click', function(ev) {
            ev.stopPropagation();
            viewMonth++; if (viewMonth > 11) { viewMonth = 0; viewYear++; } render();
        });
        document.addEventListener('click', function(ev) {
            if (!popup.contains(ev.target) && !trigger.contains(ev.target))
                popup.classList.remove('open');
        });
    }

    makeDatepicker({
        triggerId:'gtDateField1', displayId:'gtDpDisplay',  hiddenId:'gtDpValue',  popupId:'gtDpPopup',
        prevId:'gtDpPrev',        nextId:'gtDpNext',
        label1Id:'gtDpLabel',     days1Id:'gtDpDays',
        label2Id:'gtDpLabel1b',   days2Id:'gtDpDays1b'
    });
    makeDatepicker({
        triggerId:'gtDateField2', displayId:'gtDpDisplay2', hiddenId:'gtDpValue2', popupId:'gtDpPopup2',
        prevId:'gtDpPrev2',       nextId:'gtDpNext2',
        label1Id:'gtDpLabel2',    days1Id:'gtDpDays2',
        label2Id:'gtDpLabel2b',   days2Id:'gtDpDays2b'
    });
})();
</script>
