<?php
$pageTitle     = 'GCsmsSender — SMS გაგზავნა';
$activeNav     = 'gcsmssender-send';
$topbarActions = '';

$geoMax = 335;   // 5 segments × 67 chars
$latMax = 765;   // 5 segments × 153 chars
?>
<style>
.gss-char-info{font-size:12px;color:var(--muted);margin-top:6px;display:flex;gap:12px;flex-wrap:wrap}
.gss-char-count{font-weight:700}
.gss-result-ok{background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:16px 20px;margin-bottom:20px}
.gss-result-err{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:16px 20px;margin-bottom:20px;color:#b91c1c;font-size:13.5px}
.gss-result-row{display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.06)}
.gss-result-row:last-child{border-bottom:none}
.gss-result-key{color:var(--muted)}
.gss-result-val{font-weight:700}
.gss-smsno-inline{display:flex;gap:8px}
.gss-smsno-inline label{display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;border:1px solid var(--border);border-radius:8px;padding:7px 12px;transition:border-color .15s}
.gss-smsno-inline label:has(input:checked){border-color:var(--accent);color:var(--accent)}
.gss-smsno-inline input{accent-color:var(--accent)}
</style>

<?php if (!$sms->isConfigured()): ?>
<div class="alert alert-warning" style="margin-bottom:20px">
    ⚠ GCsmsSender კონფიგურირებული არ არის.
    <a href="<?= e($base) ?>/manage/gcsmssender/settings">Settings-ში გადადი →</a>
</div>
<?php endif ?>

<!-- Result -->
<?php if ($result !== null): ?>
    <?php if ($result['httpCode'] === 200): ?>
    <div class="gss-result-ok">
        <div style="font-size:14px;font-weight:800;color:#15803d;margin-bottom:12px">✓ SMS წარმატებით გაიგზავნა</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
            <?php if (isset($result['result']['messageId'])): ?>
            <div class="gss-result-row">
                <span class="gss-result-key">Message ID</span>
                <span class="gss-result-val" style="font-family:monospace"><?= e((string)$result['result']['messageId']) ?></span>
            </div>
            <?php endif ?>
            <?php if (isset($result['result']['qnt'])): ?>
            <div class="gss-result-row">
                <span class="gss-result-key">სეგმენტები (qnt)</span>
                <span class="gss-result-val"><?= (int)$result['result']['qnt'] ?></span>
            </div>
            <?php endif ?>
            <?php if (isset($result['result']['statusId'])): ?>
            <div class="gss-result-row">
                <span class="gss-result-key">Status ID</span>
                <span class="gss-result-val"><?= (int)$result['result']['statusId'] ?></span>
            </div>
            <?php endif ?>
        </div>
    </div>
    <?php else: ?>
    <div class="gss-result-err">
        ✕ <?= e($error ?? \GcSmsSender\GcSmsSenderAdminController::httpMessage($result['httpCode'])) ?>
    </div>
    <?php endif ?>
<?php elseif ($error): ?>
<div class="gss-result-err">✕ <?= e($error) ?></div>
<?php endif ?>

<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/gcsmssender/send">

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">ტელეფონის ნომერი <span style="color:#ef4444">*</span></label>
                <input type="tel" name="phone" class="form-input"
                       placeholder="595123456" required autofocus
                       value="<?= e((string)($_POST['phone'] ?? '')) ?>">
                <div class="gss-char-info">
                    ფორმატი: 9-ნიშნა ქართული მობილური (მაგ: 595123456).
                    +995 ან 0 ავტომატურად მოიხსნება.
                </div>
            </div>

            <!-- SMS type -->
            <div class="form-group">
                <label class="form-label">SMS ტიპი</label>
                <div class="gss-smsno-inline">
                    <?php
                    $curSmsno = (string)($_POST['smsno'] ?? $sms->setting('smsno', '2'));
                    ?>
                    <label>
                        <input type="radio" name="smsno" value="1"
                               <?= $curSmsno === '1' ? 'checked' : '' ?>>
                        🏷 Sender Title-ით
                    </label>
                    <label>
                        <input type="radio" name="smsno" value="2"
                               <?= $curSmsno !== '1' ? 'checked' : '' ?>>
                        📨 სტანდარტული
                    </label>
                </div>
            </div>

            <!-- Message -->
            <div class="form-group">
                <label class="form-label">შეტყობინება <span style="color:#ef4444">*</span></label>
                <textarea name="text" id="gssText" class="form-input" rows="4"
                          placeholder="SMS ტექსტი..." required
                          oninput="gssCharCount()"><?= e((string)($_POST['text'] ?? '')) ?></textarea>
                <div class="gss-char-info" id="gssInfo">
                    <span>სიმბოლოები: <span class="gss-char-count" id="gssCnt">0</span></span>
                    <span>სეგმენტები: <span class="gss-char-count" id="gssSeg">1</span></span>
                    <span id="gssLimit" style="color:var(--muted)"></span>
                </div>
            </div>

            <!-- Priority -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px">
                    <input type="checkbox" name="priority" value="1"
                           <?= isset($_POST['priority']) && $_POST['priority']==='1' ? 'checked' : '' ?>
                           style="accent-color:var(--accent)">
                    <span>
                        <strong>Priority</strong> — გამოიყენე Subscription Check-ის გვერდის ავლით
                        <span style="font-size:11.5px;color:var(--muted);font-weight:400">(საჭიროებს backend კონფიგურაციას)</span>
                    </span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary"
                    <?= !$sms->isConfigured() ? 'disabled' : '' ?>>
                📤 გაგზავნა
            </button>
        </form>
    </div>
</div>

<script>
function gssIsLatin(s) {
    // Check if all chars are basic ASCII (no Georgian/Unicode)
    for (var i = 0; i < s.length; i++) {
        if (s.charCodeAt(i) > 127) return false;
    }
    return true;
}
function gssCharCount() {
    var ta  = document.getElementById('gssText');
    var len = ta.value.length;
    var lat = gssIsLatin(ta.value);
    var single = lat ? 160 : 70;
    var multi  = lat ? 153 : 67;
    var maxSeg = 5;
    var maxLen = lat ? <?= $latMax ?> : <?= $geoMax ?>;
    var segs   = len === 0 ? 1 : (len <= single ? 1 : Math.ceil(len / multi));

    document.getElementById('gssCnt').textContent  = len;
    document.getElementById('gssSeg').textContent  = segs;
    document.getElementById('gssLimit').textContent =
        'მაქს: ' + maxLen + ' სიმბოლო · ' + maxSeg + ' სეგმენტი (' + (lat ? 'Latin' : 'Georgian') + ')';

    ta.style.borderColor = len > maxLen ? '#ef4444' : '';
}
gssCharCount();
</script>
