<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('login.title')) ?> — <?= e($siteName) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --accent:    #10B27C;
    --accent-d:  #0E9C6C;
    --blue:      #0EA5E9;
    --text:      #0f172a;
    --muted:     #64748b;
    --border:    #e2e8f0;
    --surface:   #f8fafc;
    --radius:    12px;
    --font:      system-ui, -apple-system, 'Segoe UI', sans-serif;
}

body {
    font-family: var(--font);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
    color: var(--text);
}

/* ── Minimal topbar ─────────────────────────────── */
.login-topbar {
    position: absolute;
    top: 0; left: 0; width: 100%;
    padding: 28px 48px;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.login-brand { display: flex; align-items: center; text-decoration: none; }
.login-brand:hover { opacity: .85; }
.login-back {
    font-size: 13px;
    color: rgba(255,255,255,.5);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color .15s;
}
.login-back:hover { color: rgba(255,255,255,.85); }

/* ── Animated bg lines ──────────────────────────── */
.login-bg-lines {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
}
.flow-line {
    fill: none;
    stroke-width: 1.5px;
    stroke-linecap: round;
    opacity: 0.22;
    stroke-dasharray: 2200;
    stroke-dashoffset: 2200;
}
.flow-line.green { stroke: #10B27C; }
.flow-line.blue  { stroke: #0EA5E9; }

/* ── Center container ───────────────────────────── */
.login-wrap {
    position: relative;
    z-index: 1;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 140px 24px 64px;
}

/* ── Card ────────────────────────────────────────── */
.login-card {
    background: #fff;
    border-radius: 24px;
    width: 400px;
    height: 400px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 44px;
    box-shadow: 0 32px 80px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.04);
    flex-shrink: 0;
}

.login-card-title {
    font-size: 26px;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -.5px;
    margin-bottom: 6px;
}
.login-card-sub {
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 20px;
}

/* ── Error alert ────────────────────────────────── */
.login-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13.5px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── Floating label fields ───────────────────────── */
.field {
    position: relative;
    margin-bottom: 14px;
}
.field input {
    width: 100%;
    padding: 13px 14px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 15px;
    font-family: var(--font);
    color: var(--text);
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
    display: block;
}
.field input::placeholder { color: transparent; }
.field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(16,178,124,.12);
}
/* label — resting: centered inside field */
.field label {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 15px;
    font-weight: 400;
    color: #94a3b8;
    pointer-events: none;
    transition: top .2s ease, transform .2s ease, font-size .2s ease,
                color .2s ease, font-weight .2s ease, background .2s ease;
    background: transparent;
    padding: 0 4px;
    line-height: 1;
    white-space: nowrap;
}
/* floated state: sits ON the top border line */
.field input:focus + label,
.field input:not(:placeholder-shown) + label {
    top: 0;
    transform: translateY(-50%);
    font-size: 11px;
    font-weight: 600;
    color: var(--accent);
    background: #fff;
}
.field input:not(:focus):not(:placeholder-shown) + label {
    color: var(--muted);
}

/* ── Remember + forgot row ──────────────────────── */
.login-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    font-size: 13px;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    cursor: pointer;
    user-select: none;
}
.checkbox-label input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: var(--accent);
    cursor: pointer;
}
.forgot-link {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
}
.forgot-link:hover { text-decoration: underline; }

/* ── Submit button ──────────────────────────────── */
.btn-login {
    width: 100%;
    padding: 11px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, transform .1s;
    font-family: var(--font);
    letter-spacing: -.2px;
}
.btn-login:hover  { background: var(--accent-d); }
.btn-login:active { transform: scale(.99); }

/* ── Plugin slot (OAuth buttons) ─────────────────── */
.login-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 24px 0;
    font-size: 12px;
    color: var(--muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .6px;
}
.login-divider::before,
.login-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}
.login-plugins {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* ── Bottom note ────────────────────────────────── */
.login-footer-note {
    text-align: center;
    font-size: 12px;
    color: rgba(255,255,255,.4);
    margin-top: 28px;
}

@media (max-width: 460px) {
    .login-card { width: 100%; height: auto; padding: 40px 28px; }
    .login-topbar { padding: 18px 24px; }
}
</style>
</head>
<body>

<div class="login-bg-lines" aria-hidden="true"></div>

<header class="login-topbar">
    <a href="<?= e($base) ?>/" class="login-brand">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="170" height="85">
            <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#fff" stroke-width="5" opacity=".7"/>
            <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
            <text x="80" y="46" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="900" fill="#ffffff" letter-spacing="-0.5">Goni</text>
            <text x="80" y="74" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="300" fill="#10B27C" letter-spacing="-0.5">Core</text>
        </svg>
    </a>
    <a href="<?= e($base) ?>/" class="login-back">
        ← Back to site
    </a>
</header>

<div class="login-wrap">
    <div>
        <div class="login-card">
            <h1 class="login-card-title"><?= e(t('login.title')) ?></h1>
            <p class="login-card-sub"><?= e($siteName) ?></p>

            <form method="POST" action="<?= e($base) ?>/login" autocomplete="on">
                <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
                <input type="hidden" name="redirect" value="<?= e($_GET['redirect'] ?? '') ?>">

                <div class="field">
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        value="<?= e($oldInput ?? '') ?>"
                        placeholder=" "
                        autocomplete="username"
                        autofocus
                        required
                    >
                    <label for="identifier"><?= e(t('login.email')) ?></label>
                </div>

                <div class="field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder=" "
                        autocomplete="current-password"
                        required
                    >
                    <label for="password"><?= e(t('login.password')) ?></label>
                </div>

                <div class="login-meta">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" value="1">
                        <?= e(t('login.remember')) ?>
                    </label>
                </div>

                <button type="submit" class="btn-login"><?= e(t('login.submit')) ?></button>
            </form>

            <?php
            // Plugin slot — OAuth / SSO buttons
            ob_start();
            $hooks->emit('login_form_buttons', $base);
            $pluginButtons = ob_get_clean();
            ?>
            <?php if (trim($pluginButtons) !== ''): ?>
            <div class="login-divider">or continue with</div>
            <div class="login-plugins">
                <?= $pluginButtons ?>
            </div>
            <?php endif ?>
        </div>

        <p class="login-footer-note">
            &copy; <?= date('Y') ?> <?= e($siteName) ?> &mdash; Secure login
        </p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.all.min.js"></script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= e($base) ?>/sw.js').catch(function(){});
}
</script>
<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon: 'error',
    title: <?= json_encode(t('login.title'), JSON_UNESCAPED_UNICODE) ?>,
    text:  <?= json_encode((string)$error, JSON_UNESCAPED_UNICODE) ?>,
    confirmButtonColor: '#10B27C',
    customClass: { popup: 'gc-swal-popup' }
});
</script>
<?php endif ?>
<script>
(function () {
    var ns = 'http://www.w3.org/2000/svg';
    var sheet = document.createElement('style');
    document.head.appendChild(sheet);

    var container = document.querySelector('.login-bg-lines');
    var W = window.innerWidth;
    var H = window.innerHeight;

    var durations = [9, 7, 8.5, 8, 9.5, 7.5, 10, 6.5];
    var delays    = [0, 1.5, 3, 0.5, 2.5, 4, 1, 3.5];
    var colors    = ['#10B27C', '#0EA5E9'];

    function edgePoint(edge) {
        if (edge === 0) return [0,        Math.random() * H];
        if (edge === 1) return [W,        Math.random() * H];
        if (edge === 2) return [Math.random() * W, 0];
                        return [Math.random() * W, H];
    }
    function r(a, b) { return a + Math.random() * (b - a); }

    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    container.appendChild(svg);

    for (var i = 0; i < 8; i++) {
        var eA = Math.floor(Math.random() * 4);
        var eB; do { eB = Math.floor(Math.random() * 4); } while (eB === eA);

        var p0 = edgePoint(eA), p3 = edgePoint(eB);
        var cp1 = [r(W * 0.1, W * 0.6), r(H * 0.1, H * 0.9)];
        var cp2 = [r(W * 0.4, W * 0.9), r(H * 0.1, H * 0.9)];

        var d = 'M' + p0[0].toFixed(1) + ',' + p0[1].toFixed(1)
              + ' C' + cp1[0].toFixed(1) + ',' + cp1[1].toFixed(1)
              + ' '  + cp2[0].toFixed(1) + ',' + cp2[1].toFixed(1)
              + ' '  + p3[0].toFixed(1)  + ',' + p3[1].toFixed(1);

        var path = document.createElementNS(ns, 'path');
        path.setAttribute('d', d);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', colors[i % 2]);
        path.setAttribute('stroke-width', '1.5');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('opacity', '0.22');
        svg.appendChild(path);

        var len  = path.getTotalLength();
        var name = 'll' + i + '_' + Date.now();
        path.setAttribute('stroke-dasharray', len);
        sheet.textContent +=
            '@keyframes ' + name + '{0%{stroke-dashoffset:' + len + '}100%{stroke-dashoffset:' + (-len) + '}}';
        path.style.animation = name + ' ' + durations[i % 8] + 's linear ' + delays[i % 8] + 's infinite';
    }
})();
</script>
</body>
</html>
