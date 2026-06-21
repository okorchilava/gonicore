<?php
$activeSubnav = '';
include __DIR__ . '/_subnav.php';
?>
<style>
.gt-auth-page{background:var(--gt-page-bg,#0a0812);min-height:80vh;display:flex;align-items:center;justify-content:center;padding:60px 24px;position:relative}
.gt-glow{position:absolute;border-radius:50%;filter:blur(160px);pointer-events:none;z-index:0}
.gt-glow-a{width:700px;height:700px;background:rgba(124,58,237,.22);top:-200px;left:50%;transform:translateX(-60%)}
.gt-glow-b{width:500px;height:500px;background:rgba(236,72,153,.12);top:-150px;left:50%;transform:translateX(-5%)}
.gt-auth-box{position:relative;z-index:1;width:100%;max-width:420px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:24px 0 24px 0;padding:44px 36px 40px;text-align:center}
.gt-auth-icon{font-size:44px;margin-bottom:16px}
.gt-auth-title{font-size:clamp(20px,3vw,26px);font-weight:900;color:#f1f5f9;letter-spacing:4px;margin-bottom:6px;font-family:'123Wave',sans-serif}
.gt-auth-sub{font-size:13px;color:#94a3b8;margin-bottom:28px}
.gt-auth-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.22);color:#fca5a5;border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:18px;text-align:left}
.gt-auth-field{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;text-align:left}
.gt-auth-label{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.7px}
.gt-auth-input{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:12px 16px;font-size:14px;color:#f1f5f9;outline:none;transition:border-color .2s;font-family:inherit;width:100%}
.gt-auth-input:focus{border-color:rgba(167,139,250,.5);background:rgba(255,255,255,.07)}
.gt-auth-btn{width:100%;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border:none;border-radius:12px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:6px;transition:opacity .15s}
.gt-auth-btn:hover{opacity:.88}
.gt-auth-footer{margin-top:20px;font-size:12.5px;color:#94a3b8}
.gt-auth-footer a{color:#a78bfa;text-decoration:none}
.gt-auth-footer a:hover{color:#c4b5fd}
html.gt-light .gt-auth-page{background:#f5f3ff}
html.gt-light .gt-auth-box{background:#fff;border-color:rgba(0,0,0,.08)}
html.gt-light .gt-auth-title{color:#1e1b4b}
html.gt-light .gt-auth-sub{color:#64748b}
html.gt-light .gt-auth-label{color:#94a3b8}
html.gt-light .gt-auth-input{background:rgba(0,0,0,.02);border-color:rgba(0,0,0,.1);color:#1e1b4b}
html.gt-light .gt-auth-input:focus{border-color:rgba(124,58,237,.4)}
html.gt-light .gt-auth-footer{color:#94a3b8}
</style>
<div class="gt-auth-page">
    <div class="gt-glow gt-glow-a"></div>
    <div class="gt-glow gt-glow-b"></div>
    <div class="gt-auth-box">
        <div class="gt-auth-icon">✨</div>
        <div class="gt-auth-title">რეგისტრაცია</div>
        <div class="gt-auth-sub">შექმენი გონი ტიქეთსის ანგარიში</div>
        <?php if (!empty($error)): ?>
        <div class="gt-auth-error">⚠ <?= e($error) ?></div>
        <?php endif ?>
        <form method="POST" action="<?= e($base) ?>/tickets/register" id="gtRegForm">
            <div class="gt-auth-field">
                <label class="gt-auth-label">სახელი</label>
                <input class="gt-auth-input" type="text" name="name" required
                       placeholder="შენი სახელი" autocomplete="name">
            </div>
            <div class="gt-auth-field">
                <label class="gt-auth-label">ელ-ფოსტა</label>
                <input class="gt-auth-input" type="email" name="email" required
                       autocomplete="email" placeholder="you@example.com">
            </div>
            <div class="gt-auth-field">
                <label class="gt-auth-label">ტელეფონი <span style="opacity:.6;font-weight:400;text-transform:none;letter-spacing:0">(სურვილისამებრ)</span></label>
                <input class="gt-auth-input" type="tel" name="phone"
                       autocomplete="tel" placeholder="+995 5XX XXX XXX">
            </div>
            <div class="gt-auth-field">
                <label class="gt-auth-label">პაროლი</label>
                <input class="gt-auth-input" type="password" name="password" id="gtRegPwd" required
                       autocomplete="new-password" placeholder="მინ. 6 სიმბოლო">
            </div>
            <div class="gt-auth-field">
                <label class="gt-auth-label">პაროლის დადასტურება</label>
                <input class="gt-auth-input" type="password" name="confirm_password" id="gtRegPwdConfirm" required
                       autocomplete="new-password" placeholder="გაიმეორე პაროლი">
                <div id="gtPwdMsg" style="font-size:11.5px;margin-top:4px;min-height:16px"></div>
            </div>
            <button type="submit" class="gt-auth-btn" id="gtRegSubmit">ანგარიშის შექმნა →</button>
        </form>
        <script>
        (function(){
            var p1  = document.getElementById('gtRegPwd');
            var p2  = document.getElementById('gtRegPwdConfirm');
            var msg = document.getElementById('gtPwdMsg');
            var btn = document.getElementById('gtRegSubmit');
            function check(){
                if (!p2.value) { msg.textContent=''; btn.disabled=false; return; }
                if (p1.value === p2.value) {
                    msg.textContent = '✓ პაროლი ემთხვევა';
                    msg.style.color = '#34d399';
                    btn.disabled = false;
                } else {
                    msg.textContent = '✕ პაროლი არ ემთხვევა';
                    msg.style.color = '#f87171';
                    btn.disabled = true;
                }
            }
            p1.addEventListener('input', check);
            p2.addEventListener('input', check);
        })();
        </script>
        <div class="gt-auth-footer">
            უკვე გაქვს ანგარიში?
            <a href="<?= e($base) ?>/tickets/login">შესვლა</a>
        </div>
    </div>
</div>
