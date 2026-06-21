<?php
$pageTitle     = 'GCSMS — Settings';
$activeNav     = 'gcsms-settings';
$topbarActions = '';
?>
<style>
.gs-balance-badge{display:inline-flex;align-items:center;gap:8px;background:var(--accent)18;border:1px solid var(--accent)44;color:var(--accent);border-radius:10px;padding:8px 18px;font-size:14px;font-weight:700}
.gs-status-ok{color:#10b981;font-weight:700}
.gs-status-err{color:#ef4444;font-weight:700}
.gs-tip{font-size:12.5px;color:var(--muted);margin-top:5px}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:20px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    <!-- Settings form -->
    <div class="card">
        <div class="card-header"><h3>API კავშირი</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/gcsms/settings">

                <div class="form-group">
                    <label class="form-label">API Key <span style="color:#ef4444">*</span></label>
                    <input type="text" name="api_key" class="form-input"
                           value="<?= e($sms->setting('api_key')) ?>"
                           placeholder="თქვენი API გასაღები gosms.ge-დან"
                           autocomplete="off">
                    <div class="gs-tip">
                        API გასაღები შეგიძლიათ მოიძიოთ
                        <a href="https://gosms.ge" target="_blank" rel="noopener">gosms.ge</a>-ზე.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Sender Name <span style="color:#ef4444">*</span></label>
                    <input type="text" name="sender_name" class="form-input"
                           value="<?= e($sms->setting('sender_name')) ?>"
                           placeholder="მაგ: MYSHOP"
                           maxlength="11">
                    <div class="gs-tip">
                        Sender Name-ი უნდა იყოს წინასწარ დარეგისტრირებული GoSMS-ზე.
                        მაქსიმუმ 11 სიმბოლო.
                    </div>
                </div>

                <div style="display:flex;gap:10px;align-items:center;margin-top:8px">
                    <button type="submit" class="btn btn-primary">შენახვა</button>
                    <?php if ($sms->setting('api_key') !== ''): ?>
                    <span class="gs-status-ok">✓ API გასაღები მითითებულია</span>
                    <?php else: ?>
                    <span class="gs-status-err">✕ API გასაღები არ არის მითითებული</span>
                    <?php endif ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right panel -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Balance -->
        <div class="card">
            <div class="card-header"><h3>SMS ბალანსი</h3></div>
            <div class="card-body" style="text-align:center;padding:24px">
                <?php if ($balance !== null): ?>
                <div class="gs-balance-badge">
                    <span style="font-size:20px">📩</span>
                    <span><?= number_format($balance) ?> SMS</span>
                </div>
                <div class="gs-tip" style="margin-top:10px;text-align:center">ხელმისაწვდომი კრედიტი</div>
                <?php elseif ($balErr): ?>
                <div class="gs-status-err" style="font-size:13px"><?= e($balErr) ?></div>
                <?php else: ?>
                <div style="color:var(--muted);font-size:13px">
                    შეიყვანეთ API გასაღები ბალანსის სანახავად.
                </div>
                <?php endif ?>
            </div>
        </div>

        <!-- Register sender -->
        <div class="card">
            <div class="card-header"><h3>Sender-ის რეგისტრაცია</h3></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:14px">
                    თუ Sender Name-ი ჯერ არ არის GoSMS-ზე დარეგისტრირებული,
                    შეგიძლიათ მოთხოვნა გაგზავნოთ აქვე.
                </p>
                <form method="POST" action="<?= e($base) ?>/manage/gcsms/sender" id="gsSenderForm">
                    <div style="display:flex;gap:8px">
                        <input type="text" name="name" class="form-input"
                               placeholder="MYSENDER" maxlength="11"
                               value="<?= e($sms->setting('sender_name')) ?>"
                               style="flex:1">
                        <button type="submit" class="btn btn-ghost">გაგზავნა</button>
                    </div>
                </form>
                <?php if (isset($senderResult)): ?>
                <div style="margin-top:10px;font-size:13px;<?= ($senderResult['success'] ?? false) ? 'color:#10b981' : 'color:#ef4444' ?>">
                    <?php if ($senderResult['success'] ?? false): ?>
                    ✓ მოთხოვნა გაგზავნილია. GoSMS-ი გადაამოწმებს და გაააქტიურებს.
                    <?php else: ?>
                    ✕ <?= e(\GcSms\GcSmsAdminController::errorMessage((int)($senderResult['errorCode'] ?? 0))) ?>
                    <?php endif ?>
                </div>
                <?php endif ?>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card">
            <div class="card-header"><h3>სწრაფი ბმულები</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                <a href="<?= e($base) ?>/manage/gcsms/send" class="btn btn-ghost" style="text-align:left">✉ SMS გაგზავნა</a>
                <a href="<?= e($base) ?>/manage/gcsms/otp" class="btn btn-ghost" style="text-align:left">🔑 OTP ტესტი</a>
                <a href="<?= e($base) ?>/manage/gcsms/logs" class="btn btn-ghost" style="text-align:left">📋 გაგზავნების ლოგი</a>
                <a href="<?= e($base) ?>/manage/gcsms/inbound" class="btn btn-ghost" style="text-align:left">📥 შემომავალი პასუხები</a>
            </div>
        </div>

    </div>
</div>

<?php
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$origin    = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$whToken   = $sms->webhookToken();
$whInbound = $origin . $base . '/gcsms/webhook/inbound';
$whStatus  = $origin . $base . '/gcsms/webhook/status';
?>
<!-- Webhooks -->
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3>Webhook-ები</h3></div>
    <div class="card-body">
        <p class="gs-tip" style="margin-top:0;margin-bottom:16px">
            ქვემოთ მოცემული ორი <strong>URL</strong> მიუთითეთ gosms.ge-ის პანელში.
            ტოკენი, რომელსაც <strong>gosms.ge გაძლევთ</strong>, ჩასვით აქ — ყველა შემომავალი
            webhook დამოწმდება ამ ტოკენით (<code>X-Webhook-Token</code> header).
        </p>

        <form method="POST" action="<?= e($base) ?>/manage/gcsms/settings">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Webhook ტოკენი <span style="color:#ef4444">*</span></label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <input type="text" name="webhook_token" class="form-input"
                           value="<?= e($whToken) ?>" placeholder="ჩასვით gosms.ge-ის მიერ მოცემული ტოკენი"
                           autocomplete="off" style="flex:1;min-width:240px;font-family:monospace">
                    <button type="submit" class="btn btn-primary">შენახვა</button>
                </div>
                <div class="gs-tip">
                    <?php if ($whToken !== ''): ?>
                    <span style="color:#10b981;font-weight:700">✓ ტოკენი მითითებულია</span> — webhook-ები აქტიურია.
                    <?php else: ?>
                    <span style="color:#ef4444;font-weight:700">✕ ტოკენი არ არის შეყვანილი</span> — webhook-ები დაბლოკილია, სანამ არ ჩასვამთ gosms.ge-ის ტოკენს.
                    <?php endif ?>
                </div>
            </div>
        </form>

        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0">

        <div class="form-group">
            <label class="form-label">მოკლე ნომრის უკუკავშირი (Inbound)</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="text" class="form-input" id="whInbound" readonly value="<?= e($whInbound) ?>"
                       style="flex:1;min-width:240px;font-family:monospace">
                <button type="button" class="btn btn-ghost" onclick="gsCopy('whInbound')">კოპირება</button>
            </div>
            <div class="gs-tip">POST · შემომავალი პასუხები (from, to, text, sendAt, noSms) → „შემომავალი" გვერდზე.</div>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">ჩაბარების სტატუსი (Delivery status)</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="text" class="form-input" id="whStatus" readonly value="<?= e($whStatus) ?>"
                       style="flex:1;min-width:240px;font-family:monospace">
                <button type="button" class="btn btn-ghost" onclick="gsCopy('whStatus')">კოპირება</button>
            </div>
            <div class="gs-tip">POST · messageId + status (DELIVERED/REJECTED/EXPIRED/DELETED/QUEUE) — ლოგებში სტატუსი განახლდება.</div>
        </div>
    </div>
</div>

<script>
function gsCopy(id){
    var el=document.getElementById(id); if(!el) return;
    el.select(); el.setSelectionRange(0,99999);
    try{ navigator.clipboard.writeText(el.value); }catch(e){ try{document.execCommand('copy');}catch(_){} }
    if(window.gcToast) gcToast('დაკოპირდა','success');
}
</script>
