<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Two-Factor Authentication — <?= e($siteName ?? 'GoniCore') ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --accent:  #10B27C;
    --accent-d:#0E9C6C;
    --text:    #0f172a;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --font:    system-ui, -apple-system, 'Segoe UI', sans-serif;
}
body {
    font-family: var(--font);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
    color: var(--text);
    padding: 24px;
}
.card {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    padding: 40px 40px 36px;
    box-shadow: 0 32px 80px rgba(0,0,0,.35);
}
.icon-wrap {
    width: 56px; height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, #059669, #10b981);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px;
    margin: 0 auto 20px;
}
h1 { font-size: 22px; font-weight: 800; text-align: center; margin-bottom: 6px; letter-spacing: -.4px; }
.sub { font-size: 13.5px; color: var(--muted); text-align: center; margin-bottom: 28px; line-height: 1.55; }
.error {
    background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
    border-radius: 8px; padding: 11px 14px; font-size: 13px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}
.code-input {
    width: 100%;
    padding: 14px;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 12px;
    text-align: center;
    border: 2px solid var(--border);
    border-radius: 10px;
    outline: none;
    font-family: 'Courier New', monospace;
    color: var(--text);
    transition: border-color .2s, box-shadow .2s;
    margin-bottom: 16px;
}
.code-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(16,178,124,.12);
}
.btn {
    width: 100%;
    padding: 13px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
    font-family: var(--font);
}
.btn:hover { background: var(--accent-d); }
.back {
    display: block;
    text-align: center;
    margin-top: 18px;
    font-size: 13px;
    color: rgba(255,255,255,.5);
    text-decoration: none;
    transition: color .15s;
}
.back:hover { color: rgba(255,255,255,.85); }
</style>
</head>
<body>

<div class="card">
    <div class="icon-wrap">🔐</div>
    <h1>Two-Factor Auth</h1>
    <p class="sub">Enter the 6-digit code from your authenticator app.</p>

    <?php if (!empty($error)): ?>
    <div class="error">
        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="7" stroke="#dc2626" stroke-width="1.5"/>
            <path d="M8 4.5v4M8 10.5v1" stroke="#dc2626" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <?= e($error) ?>
    </div>
    <?php endif ?>

    <form method="POST" action="<?= e($base ?? '') ?>/2fa/verify" autocomplete="off">
        <input
            type="text"
            name="code"
            class="code-input"
            inputmode="numeric"
            pattern="[0-9 ]*"
            maxlength="7"
            placeholder="000000"
            autofocus
            autocomplete="one-time-code"
            required
        >
        <button type="submit" class="btn">Verify</button>
    </form>
</div>

<a href="<?= e($base ?? '') ?>/login" class="back">← Back to login</a>

</body>
</html>
