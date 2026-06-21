<?php
$pageTitle     = 'Set Up Two-Factor Authentication';
$activeNav     = '2fa';
$topbarActions = '<a href="' . e($base) . '/manage/2fa" class="topbar-btn ghost">← Back</a>';
?>

<?php if (!empty($error)): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div style="max-width:700px">
<form method="POST" action="<?= e($base) ?>/manage/2fa/enable" autocomplete="off">
<div style="display:flex;flex-direction:column;gap:20px">

    <!-- Step 1 -->
    <div class="card">
        <div class="card-header"><h3><span style="background:var(--accent);color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;margin-right:8px">1</span>Install an authenticator app</h3></div>
        <div class="card-body" style="font-size:14px;color:var(--muted)">
            Download <strong style="color:var(--text)">Google Authenticator</strong>, <strong style="color:var(--text)">Authy</strong>,
            <strong style="color:var(--text)">Microsoft Authenticator</strong>, or any TOTP-compatible app on your phone.
        </div>
    </div>

    <!-- Step 2 -->
    <div class="card">
        <div class="card-header"><h3><span style="background:var(--accent);color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;margin-right:8px">2</span>Scan the QR code</h3></div>
        <div class="card-body">
            <div style="display:flex;gap:32px;align-items:flex-start;flex-wrap:wrap">
                <div>
                    <div id="qrcode" style="background:#fff;padding:10px;border-radius:10px;border:1px solid var(--border);display:inline-block"></div>
                </div>
                <div style="flex:1;min-width:220px">
                    <p style="font-size:13.5px;color:var(--muted);margin-bottom:12px">
                        Open your app → <em>Add account</em> → <em>Scan QR code</em>.
                    </p>
                    <p style="font-size:13px;color:var(--muted);margin-bottom:8px">Or enter the key manually:</p>
                    <div style="display:flex;align-items:center;gap:8px;background:var(--bg);border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-family:'Courier New',monospace;font-size:14px;font-weight:700;letter-spacing:2px;color:var(--text);word-break:break-all" id="secretBox">
                        <?= e(chunk_split((string)($secret ?? ''), 4, ' ')) ?>
                        <button type="button" onclick="copySecret()" title="Copy" style="background:none;border:none;cursor:pointer;color:var(--accent);font-size:16px;padding:2px;line-height:1;flex-shrink:0">⎘</button>
                    </div>
                    <div style="font-size:12px;color:var(--muted);margin-top:8px">
                        Account: <strong><?= e($siteName ?? 'GoniCore') ?></strong> &nbsp;·&nbsp; Algorithm: SHA1 &nbsp;·&nbsp; Period: 30s
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3 -->
    <div class="card">
        <div class="card-header"><h3><span style="background:var(--accent);color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;margin-right:8px">3</span>Confirm with a 6-digit code</h3></div>
        <div class="card-body">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Enter the code shown in your app</label>
                <input
                    type="text"
                    name="code"
                    class="form-input"
                    inputmode="numeric"
                    pattern="[0-9 ]*"
                    maxlength="7"
                    placeholder="000 000"
                    autofocus
                    autocomplete="one-time-code"
                    required
                    style="max-width:180px;font-size:22px;font-weight:700;letter-spacing:8px;text-align:center;font-family:'Courier New',monospace"
                >
            </div>
        </div>
    </div>

</div>
<div style="margin-top:20px">
    <button type="submit" class="btn btn-primary" style="padding:11px 28px;font-size:15px">Enable 2FA</button>
</div>
</form>
</div>

<script>
(function () {
    var otpauth = <?= json_encode($otpauth ?? '') ?>;
    var el = document.getElementById('qrcode');
    if (!el || !otpauth) return;

    // Try server-side PNG first (always works, no CDN needed)
    var img = new Image();
    img.src = <?= json_encode(($base ?? '') . '/manage/2fa/qr?d=' . rawurlencode($otpauth ?? '')) ?>;
    img.alt = 'QR Code';
    img.style.cssText = 'border-radius:8px;border:4px solid #fff;box-shadow:0 2px 12px rgba(0,0,0,.08);display:block';
    img.onerror = function () {
        // Fallback: load qrcode.js from CDN if server-side fails
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
        s.onload = function () {
            el.innerHTML = '';
            new QRCode(el, { text: otpauth, width: 160, height: 160,
                colorDark: '#0f172a', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M });
        };
        document.head.appendChild(s);
    };
    el.innerHTML = '';
    el.appendChild(img);
})();

function copySecret() {
    var text = '<?= preg_replace('/[^A-Z2-7]/', '', strtoupper((string)($secret ?? ''))) ?>';
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            var box = document.getElementById('secretBox');
            var prev = box.style.borderColor;
            box.style.borderColor = 'var(--accent)';
            setTimeout(function() { box.style.borderColor = prev; }, 1500);
        });
    }
}
</script>
