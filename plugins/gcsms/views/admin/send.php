<?php
$pageTitle     = 'GCSMS — Send SMS';
$activeNav     = 'gcsms-send';
$topbarActions = '';

$Georgian  = 402;
$Latin     = 918;
$geoSeg    = 67;  // chars per segment (Georgian/Unicode)
$latSeg    = 153; // chars per segment (Latin)
?>
<style>
.gs-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px}
.gs-tab{padding:10px 22px;font-size:13.5px;font-weight:700;cursor:pointer;border:none;background:transparent;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s}
.gs-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.gs-char-info{font-size:12px;color:var(--muted);margin-top:6px;display:flex;gap:12px}
.gs-char-count{font-weight:700}
.gs-result-ok{background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:16px 20px;margin-bottom:20px}
.gs-result-err{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:16px 20px;margin-bottom:20px;color:#b91c1c}
.gs-result-row{display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05)}
.gs-result-row:last-child{border-bottom:none}
.gs-result-key{color:var(--muted)}
.gs-result-val{font-weight:600}
</style>

<?php if (!$sms->isConfigured()): ?>
<div class="alert alert-warning" style="margin-bottom:20px">
    ⚠ GCSMS კონფიგურირებული არ არის.
    <a href="<?= e($base) ?>/manage/gcsms/settings">Settings-ში გადადი →</a>
</div>
<?php endif ?>

<!-- Result -->
<?php if ($result !== null): ?>
<?php if ($result['success'] ?? false): ?>
<div class="gs-result-ok">
    <div style="font-size:14px;font-weight:800;color:#15803d;margin-bottom:12px">✓ წარმატებით გაიგზავნა</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
        <?php if (isset($result['messageId'])): ?>
        <div class="gs-result-row"><span class="gs-result-key">Message ID</span><span class="gs-result-val"><?= e((string)$result['messageId']) ?></span></div>
        <?php endif ?>
        <?php if (isset($result['to'])): ?>
        <div class="gs-result-row"><span class="gs-result-key">ნომერი</span><span class="gs-result-val"><?= e((string)$result['to']) ?></span></div>
        <?php endif ?>
        <?php if (isset($result['balance'])): ?>
        <div class="gs-result-row"><span class="gs-result-key">დარჩენილი ბალანსი</span><span class="gs-result-val"><?= (int)$result['balance'] ?> SMS</span></div>
        <?php endif ?>
        <?php if (isset($result['segment'])): ?>
        <div class="gs-result-row"><span class="gs-result-key">სეგმენტები</span><span class="gs-result-val"><?= (int)$result['segment'] ?></span></div>
        <?php endif ?>
        <?php if (isset($result['encode'])): ?>
        <div class="gs-result-row"><span class="gs-result-key">Encoding</span><span class="gs-result-val"><?= e((string)$result['encode']) ?></span></div>
        <?php endif ?>
        <!-- Bulk -->
        <?php if (isset($result['totalCount'])): ?>
        <div class="gs-result-row"><span class="gs-result-key">სულ</span><span class="gs-result-val"><?= (int)$result['totalCount'] ?></span></div>
        <div class="gs-result-row"><span class="gs-result-key">წარმატებული</span><span class="gs-result-val" style="color:#10b981"><?= (int)$result['successCount'] ?></span></div>
        <div class="gs-result-row"><span class="gs-result-key">წარუმატებელი</span><span class="gs-result-val" style="color:<?= (int)$result['failedCount']>0 ? '#ef4444':'var(--muted)' ?>"><?= (int)$result['failedCount'] ?></span></div>
        <?php endif ?>
    </div>
</div>
<?php else: ?>
<div class="gs-result-err">
    ✕ <?= e(\GcSms\GcSmsAdminController::errorMessage((int)($result['errorCode'] ?? 0))) ?>
    <?php if (isset($result['error']) && $result['error']): ?>
    <div style="font-size:12px;margin-top:4px;opacity:.75"><?= e((string)$result['error']) ?></div>
    <?php endif ?>
</div>
<?php endif ?>
<?php endif ?>

<?php if ($error): ?>
<div class="gs-result-err">✕ <?= e($error) ?></div>
<?php endif ?>

<!-- Tabs -->
<div class="gs-tabs">
    <button type="button" class="gs-tab <?= $tab === 'single' ? 'active' : '' ?>" onclick="switchTab('single')">✉ ერთი ნომერი</button>
    <button type="button" class="gs-tab <?= $tab === 'bulk' ? 'active' : '' ?>" onclick="switchTab('bulk')">📨 Bulk — მრავალი ნომერი</button>
</div>

<!-- Single -->
<div id="gs-panel-single" style="<?= $tab !== 'single' ? 'display:none' : '' ?>">
<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/gcsms/send">
            <input type="hidden" name="tab" value="single">
            <div class="form-group">
                <label class="form-label">ტელეფონის ნომერი <span style="color:#ef4444">*</span></label>
                <input type="tel" name="phone" class="form-input"
                       placeholder="995555123456" value="<?= e($_POST['phone'] ?? '') ?>" required>
                <div class="gs-char-info">ფორმატი: 995 + ნომერი (მაგ: 995555123456)</div>
            </div>
            <div class="form-group">
                <label class="form-label">შეტყობინება <span style="color:#ef4444">*</span></label>
                <textarea name="text" id="gsTextSingle" class="form-input" rows="4"
                          placeholder="თქვენი SMS ტექსტი..." required
                          oninput="updateCharCount('gsTextSingle','gsSingleInfo')"><?= e($_POST['text'] ?? '') ?></textarea>
                <div class="gs-char-info" id="gsSingleInfo">
                    <span>სიმბოლოები: <span class="gs-char-count" id="gsSingleCount">0</span></span>
                    <span>სეგმენტები: <span class="gs-char-count" id="gsSingleSeg">1</span></span>
                    <span id="gsSingleLimit" style="color:var(--muted)"></span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" <?= !$sms->isConfigured() ? 'disabled' : '' ?>>
                📤 გაგზავნა
            </button>
        </form>
    </div>
</div>
</div>

<!-- Bulk -->
<div id="gs-panel-bulk" style="<?= $tab !== 'bulk' ? 'display:none' : '' ?>">
<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/gcsms/send">
            <input type="hidden" name="tab" value="bulk">
            <div class="form-group">
                <label class="form-label">ტელეფონის ნომრები <span style="color:#ef4444">*</span></label>
                <textarea name="phones" id="gsPhones" class="form-input" rows="5"
                          placeholder="995555123456&#10;995599111222&#10;995577333444"
                          oninput="updatePhoneCount()"
                          required><?= e($_POST['phones'] ?? '') ?></textarea>
                <div class="gs-char-info">
                    თითო ნომერი ახალ სტრიქონზე, ან მძიმით გამოყოფილი.
                    ნომრები: <span class="gs-char-count" id="gsBulkCount">0</span> / 1000
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">შეტყობინება <span style="color:#ef4444">*</span></label>
                <textarea name="text" id="gsTextBulk" class="form-input" rows="4"
                          placeholder="თქვენი SMS ტექსტი..." required
                          oninput="updateCharCount('gsTextBulk','gsBulkInfo')"><?= e($_POST['text'] ?? '') ?></textarea>
                <div class="gs-char-info" id="gsBulkInfo">
                    <span>სიმბოლოები: <span class="gs-char-count" id="gsBulkCharCount">0</span></span>
                    <span>სეგმენტები: <span class="gs-char-count" id="gsBulkSeg">1</span></span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Opt-out ნომერი <span style="font-weight:400;color:var(--muted)">(სურვილისამებრ)</span></label>
                <input type="tel" name="no_sms_number" class="form-input"
                       placeholder="995322XXXXXX" value="<?= e($_POST['no_sms_number'] ?? '') ?>">
                <div class="gs-char-info">
                    ნომერი, რომელზეც მიმღებებს შეუძლიათ უარი განაცხადონ SMS-ის მიღებაზე.
                </div>
            </div>
            <button type="submit" class="btn btn-primary" <?= !$sms->isConfigured() ? 'disabled' : '' ?>>
                📨 Bulk გაგზავნა
            </button>
        </form>
    </div>
</div>
</div>

<script>
function switchTab(t) {
    ['single','bulk'].forEach(function(x) {
        document.getElementById('gs-panel-' + x).style.display = x === t ? '' : 'none';
        document.querySelectorAll('.gs-tab').forEach(function(b, i) {
            b.classList.toggle('active', (i === 0 && t === 'single') || (i === 1 && t === 'bulk'));
        });
    });
}
function isLatin(str) {
    return !/[^ -]/.test(str);
}
function updateCharCount(textareaId, infoId) {
    var ta  = document.getElementById(textareaId);
    var val = ta.value;
    var len = val.length;
    var lat = isLatin(val);
    var max = lat ? 918 : 402;
    var segSize = lat ? 153 : 67;
    var segs = Math.max(1, Math.ceil(len / segSize));

    var suffix = textareaId === 'gsTextSingle' ? 'Single' : 'Bulk';
    var countEl = document.getElementById('gs' + suffix + 'Count') ||
                  document.getElementById('gs' + suffix + 'CharCount');
    var segEl   = document.getElementById('gs' + suffix + 'Seg');
    var limEl   = document.getElementById('gs' + suffix + 'Limit');

    if (countEl) countEl.textContent = len;
    if (segEl)   segEl.textContent   = segs;
    if (limEl)   limEl.textContent   = 'მაქს: ' + max + ' სიმბოლო (' + (lat ? 'Latin' : 'Georgian') + ')';

    ta.style.borderColor = len > max ? '#ef4444' : '';
}
function updatePhoneCount() {
    var raw  = document.getElementById('gsPhones').value;
    var list = raw.split(/[\r\n,;]+/).filter(function(s){ return s.trim() !== ''; });
    document.getElementById('gsBulkCount').textContent = list.length;
    document.getElementById('gsBulkCount').style.color = list.length > 1000 ? '#ef4444' : '';
}
// Init counts
['gsTextSingle','gsTextBulk'].forEach(function(id){
    var el = document.getElementById(id);
    if (el && el.value) el.dispatchEvent(new Event('input'));
});
updatePhoneCount();
</script>
