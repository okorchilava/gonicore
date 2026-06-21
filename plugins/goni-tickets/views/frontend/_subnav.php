<?php
/**
 * GoniTickets — plugin sub-nav bar
 * Variables expected: $base, $tickets (TicketService), $activeSubnav ('events'|'organizers')
 */
$_slug   = $tickets->setting('events_page_slug', 'events');
$_active = $activeSubnav ?? 'events';
$_h      = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
?>
<!-- apply saved theme before render to avoid flash -->
<script>
(function(){
    if (localStorage.getItem('gt-theme') === 'light') {
        document.documentElement.classList.add('gt-light');
    }
})();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<nav class="gt-subnav" aria-label="Tickets navigation">
    <div class="gt-subnav-inner">

        <ul class="gt-subnav-links">
            <li>
                <a href="<?= $_h($base) ?>/<?= $_h($_slug) ?>"
                   class="gt-subnav-link<?= $_active === 'events' ? ' gt-subnav-link--active' : '' ?>">
                    <span>🎭</span> ღონისძიებები
                </a>
            </li>
            <li>
                <a href="<?= $_h($base) ?>/<?= $_h($_slug) ?>/organizers"
                   class="gt-subnav-link<?= $_active === 'organizers' ? ' gt-subnav-link--active' : '' ?>">
                    <span>👤</span> ორგანიზატორები
                </a>
            </li>
            <li>
                <a href="<?= $_h($base) ?>/tickets/my-ticket"
                   class="gt-subnav-link<?= $_active === 'my-ticket' ? ' gt-subnav-link--active' : '' ?>">
                    <span>🎟</span> ბილეთები
                </a>
            </li>
        </ul>

        <div class="gt-subnav-right">
            <?php
            $_gtUser = null;
            try {
                $_gtUser = gc_container()->get(\GoniTickets\GtUserService::class)->currentUser();
            } catch (\Throwable $_e) {}
            ?>
            <?php if ($_gtUser): ?>
            <a href="<?= $_h($base) ?>/tickets/account" class="gt-login-btn" title="ჩემი ანგარიში">
                <span style="font-size:15px">👤</span>
                <span class="gt-login-btn-label"><?= htmlspecialchars(explode(' ', (string)($_gtUser['name']??'ანგარიში'))[0], ENT_QUOTES) ?></span>
            </a>
            <?php else: ?>
            <a href="<?= $_h($base) ?>/tickets/login" class="gt-login-btn" title="შესვლა">
                <span style="font-size:15px">👤</span>
                <span class="gt-login-btn-label">შესვლა</span>
            </a>
            <?php endif ?>

            <div class="gt-search-wrap" id="gtSearchWrap">
                <form class="gt-search-form" method="GET" action="<?= $_h($base) ?>/<?= $_h($_slug) ?>">
                    <input type="text" name="q" class="gt-search-input" id="gtSearchInput"
                           placeholder="ძებნა..." autocomplete="off" aria-label="ძებნა">
                    <button type="submit" class="gt-search-sub" aria-label="ძებნა">&#8594;</button>
                </form>
            </div>
            <button class="gt-search-btn" id="gtSearchBtn" title="ძებნა" aria-label="Search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </button>

            <button class="gt-theme-btn" id="gtThemeBtn" title="Toggle theme" aria-label="Toggle dark/light theme">
                <span class="gt-theme-icon gt-theme-icon--moon">🌙</span>
                <span class="gt-theme-icon gt-theme-icon--sun">☀️</span>
            </button>
        </div>

    </div>
</nav>

<style>
/* ── 123Wave font ────────────────────────────────────────────────────── */
@font-face {
    font-family: '123Wave';
    src: url('<?= $_h($base) ?>/assets/fonts/123Wave.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

/* apply to all plugin page headings */
.gt-heading h1,
.gt-orgs-heading h1 {
    font-family: '123Wave', sans-serif;
    letter-spacing: 0;          /* 123Wave has its own rhythm — reset tight tracking */
}

/* ── Noto Sans Georgian for all plugin pages ───────────────────────── */
.gt-subnav, .gt-page, .gt-orgs-page, .gt-org-page, .gt-tr-page,
.gt-tv-page, .gt-auth-page, .gt-acc-page,
.gt-dp-popup, .gt-tr-pax-popup {
    font-family: 'Noto Sans Georgian', sans-serif;
}

/* ── Nav search ─────────────────────────────────────────────────────── */
.gt-search-wrap {
    display:flex;align-items:center;
    max-width:0;overflow:hidden;
    transition:max-width .28s cubic-bezier(.4,0,.2,1),opacity .18s;
    opacity:0;
}
.gt-search-wrap.open { max-width:230px;opacity:1 }
.gt-search-form {
    display:flex;align-items:center;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.15);
    border-radius:20px;overflow:hidden;white-space:nowrap;
}
.gt-search-input {
    flex:1;border:none;outline:none;background:transparent;
    color:#f1f5f9;font-size:12.5px;padding:5px 11px;min-width:150px;
    font-family:'Noto Sans Georgian',sans-serif;
}
.gt-search-input::placeholder { color:#64748b }
.gt-search-sub {
    background:transparent;border:none;
    border-left:1px solid rgba(255,255,255,.1);
    color:#a78bfa;font-size:14px;padding:4px 10px;
    cursor:pointer;transition:color .15s;flex-shrink:0;line-height:1;
}
.gt-search-sub:hover { color:#c4b5fd }
.gt-search-btn {
    display:flex;align-items:center;justify-content:center;
    width:34px;height:34px;border-radius:50%;
    background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
    cursor:pointer;color:#94a3b8;flex-shrink:0;
    transition:background .15s,border-color .15s,color .15s;
}
.gt-search-btn:hover { background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.2);color:#c4b5fd }
.gt-search-btn svg { display:block }
html.gt-light .gt-search-form { background:rgba(0,0,0,.04);border-color:rgba(0,0,0,.12) }
html.gt-light .gt-search-input { color:#1e1b4b }
html.gt-light .gt-search-input::placeholder { color:#94a3b8 }
html.gt-light .gt-search-sub { border-color:rgba(0,0,0,.1);color:#7c3aed }
html.gt-light .gt-search-btn { background:rgba(0,0,0,.04);border-color:rgba(0,0,0,.1);color:#64748b }
html.gt-light .gt-search-btn:hover { color:#7c3aed;background:rgba(124,58,237,.07) }

/* ── Page-bg CSS variable (used by cover fade & other elements) ─────── */
:root                { --gt-page-bg: #0a0812 }
html.gt-light        { --gt-page-bg: #f5f3ff }

/* ── Sub-nav bar ──────────────────────────────────────────────────────── */
.gt-subnav{
    position:sticky;
    top:72px;
    z-index:100;
    background:rgba(10,8,18,.92);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    border-bottom:1px solid rgba(255,255,255,.07);
    transition:background .25s,border-color .25s;
}
.gt-subnav-inner{
    max-width:1280px;
    margin:0 auto;
    padding:0 24px;
    display:flex;
    align-items:center;
    gap:32px;
    height:48px;
}
.gt-subnav-links{
    display:flex;align-items:center;gap:4px;
    list-style:none;padding:0;margin:0;
}
.gt-subnav-link{
    display:flex;align-items:center;gap:6px;
    padding:5px 14px;border-radius:20px;
    font-size:13px;font-weight:600;color:#94a3b8;
    text-decoration:none;
    transition:color .15s,background .15s;
    white-space:nowrap;
}
.gt-subnav-link:hover{color:#c4b5fd;background:rgba(124,58,237,.1);text-decoration:none}
.gt-subnav-link--active{color:#a78bfa;background:rgba(124,58,237,.14)}
.gt-subnav-link span{font-size:14px;line-height:1}

/* ── Right cluster (login + theme) ───────────────────────────────────── */
.gt-subnav-right{
    margin-left:auto;
    display:flex;align-items:center;gap:8px;
    flex-shrink:0;
}

/* ── Login button ────────────────────────────────────────────────────── */
.gt-login-btn{
    display:flex;align-items:center;gap:6px;
    padding:5px 14px;border-radius:20px;
    font-size:13px;font-weight:600;color:#94a3b8;
    text-decoration:none;
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.09);
    white-space:nowrap;
    transition:color .15s,background .15s,border-color .15s;
}
.gt-login-btn:hover{color:#c4b5fd;background:rgba(124,58,237,.12);border-color:rgba(124,58,237,.3);text-decoration:none}
html.gt-light .gt-login-btn{color:#64748b;background:rgba(0,0,0,.03);border-color:rgba(0,0,0,.09)}
html.gt-light .gt-login-btn:hover{color:#7c3aed;background:rgba(124,58,237,.07);border-color:rgba(124,58,237,.2)}
@media(max-width:480px){.gt-login-btn-label{display:none}}

/* ── Theme toggle button ──────────────────────────────────────────────── */
.gt-theme-btn{
    display:flex;align-items:center;justify-content:center;
    width:34px;height:34px;border-radius:50%;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.1);
    cursor:pointer;font-size:16px;line-height:1;
    transition:background .15s,border-color .15s,transform .2s;
    flex-shrink:0;
}
.gt-theme-btn:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.2);transform:scale(1.1)}

/* show moon in dark mode, sun in light mode */
.gt-theme-icon--sun{ display:none }
.gt-theme-icon--moon{ display:block }
html.gt-light .gt-theme-icon--sun{ display:block }
html.gt-light .gt-theme-icon--moon{ display:none }

/* ── Light-mode: sub-nav bar ─────────────────────────────────────────── */
html.gt-light .gt-subnav{
    background:rgba(245,243,255,.95);
    border-bottom-color:rgba(124,58,237,.12);
}
html.gt-light .gt-subnav-link{ color:#94a3b8 }
html.gt-light .gt-subnav-link:hover{ color:#7c3aed;background:rgba(124,58,237,.07) }
html.gt-light .gt-subnav-link--active{ color:#7c3aed;background:rgba(124,58,237,.1) }
html.gt-light .gt-theme-btn{
    background:rgba(0,0,0,.04);
    border-color:rgba(0,0,0,.1);
}
html.gt-light .gt-theme-btn:hover{ background:rgba(0,0,0,.08) }

/* ── Light-mode: page backgrounds ────────────────────────────────────── */
html.gt-light .gt-page,
html.gt-light .gt-orgs-page,
html.gt-light .gt-org-page{ background:#f5f3ff }

/* glows — subtler on light bg */
html.gt-light .gt-glow-a{ background:rgba(124,58,237,.10) }
html.gt-light .gt-glow-b{ background:rgba(236,72,153,.07) }

/* ── Light-mode: heading ─────────────────────────────────────────────── */
html.gt-light .gt-heading h1,
html.gt-light .gt-orgs-heading h1,
html.gt-light .gt-org-name{ color:#1e1b4b }
html.gt-light .gt-heading-sub,
html.gt-light .gt-orgs-sub{ color:#64748b }
html.gt-light .gt-hero-badge,
html.gt-light .gt-orgs-badge,
html.gt-light .gt-org-badge{
    background:rgba(124,58,237,.07);
    border-color:rgba(124,58,237,.18);
    color:#7c3aed;
}
html.gt-light .gt-hero-count{ background:rgba(79,70,229,.08);color:#4f46e5 }

/* ── Light-mode: category filter buttons ────────────────────────────── */
html.gt-light .gt-cat-btn{
    background:rgba(0,0,0,.03);
    border-color:rgba(0,0,0,.09);
    color:#64748b;
}
html.gt-light .gt-cat-btn:hover{
    background:rgba(0,0,0,.06);
    border-color:rgba(0,0,0,.15);
    color:#1e293b;
}
html.gt-light .gt-cat-btn.active{
    background:color-mix(in srgb,var(--cc,#7c3aed) 10%,transparent);
    border-color:color-mix(in srgb,var(--cc,#7c3aed) 35%,transparent);
    color:var(--cc,#7c3aed);
}

/* ── Light-mode: section divider ─────────────────────────────────────── */
html.gt-light .gt-section-label span{ color:#7c3aed }
html.gt-light .gt-section-label::before,
html.gt-light .gt-section-label::after{ background:rgba(0,0,0,.08) }

/* ── Light-mode: event row ───────────────────────────────────────────── */
html.gt-light .gt-row-body{
    background:#fff;
    border-color:rgba(0,0,0,.07);
}
html.gt-light .gt-row-num{
    background:rgba(255,255,255,.9);
    border-color:rgba(0,0,0,.1);
    color:#1e293b;
}
html.gt-light .gt-row-title{ color:#1e1b4b }
html.gt-light .gt-row-title:hover{ color:#7c3aed }
html.gt-light .gt-row-organizer,
html.gt-light .gt-row-organizer a{ color:#94a3b8 }
html.gt-light .gt-row-organizer a:hover{ color:#7c3aed }
html.gt-light .gt-row-meta{ color:#94a3b8 }
html.gt-light .gt-row-meta-dot{ color:#cbd5e1 }
html.gt-light .gt-row-price-from{ color:#7c3aed }
html.gt-light .gt-row-btn{ color:#fff !important }
html.gt-light .gt-row-btn-dis{ background:rgba(0,0,0,.06);color:#94a3b8 }
html.gt-light .gt-empty{ color:#94a3b8 }

/* ── Light-mode: organizers grid ─────────────────────────────────────── */
html.gt-light .gt-org-card{
    background:#fff;
    border-color:rgba(0,0,0,.07);
}
html.gt-light .gt-org-card:hover{
    background:rgba(124,58,237,.03);
    border-color:rgba(124,58,237,.2);
}
html.gt-light .gt-org-card-name{ color:#1e1b4b }
html.gt-light .gt-org-card-meta{ color:#94a3b8 }
html.gt-light .gt-org-card-desc{ color:#64748b }
html.gt-light .gt-org-card-footer{ border-top-color:rgba(0,0,0,.06) }
html.gt-light .gt-org-card-count{ color:#94a3b8 }
html.gt-light .gt-org-card-link{ color:#7c3aed }
html.gt-light .gt-orgs-empty{ color:#94a3b8 }

/* ── Light-mode: organizer profile ───────────────────────────────────── */
html.gt-light .gt-org-desc{ color:#64748b }
html.gt-light .gt-org-website{
    background:rgba(124,58,237,.07);
    border-color:rgba(124,58,237,.18);
    color:#7c3aed;
}
html.gt-light .gt-org-back a{ color:#94a3b8 }
html.gt-light .gt-org-back a:hover{ color:#7c3aed }

@media(max-width:480px){
    .gt-subnav-inner{gap:8px;padding:0 16px}
}
</style>

<script>
(function(){
    var btn = document.getElementById('gtThemeBtn');
    if (!btn) return;
    btn.addEventListener('click', function(){
        var isLight = document.documentElement.classList.toggle('gt-light');
        localStorage.setItem('gt-theme', isLight ? 'light' : 'dark');
    });
})();
(function(){
    var btn   = document.getElementById('gtSearchBtn');
    var wrap  = document.getElementById('gtSearchWrap');
    var input = document.getElementById('gtSearchInput');
    if (!btn || !wrap) return;
    btn.addEventListener('click', function(e){
        e.stopPropagation();
        var opening = !wrap.classList.contains('open');
        wrap.classList.toggle('open');
        if (opening && input) setTimeout(function(){ input.focus(); }, 290);
    });
    document.addEventListener('click', function(e){
        if (!wrap.contains(e.target) && e.target !== btn)
            wrap.classList.remove('open');
    });
})();
</script>
