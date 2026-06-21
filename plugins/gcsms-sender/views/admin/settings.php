<?php
$pageTitle     = 'GCsmsSender — Settings';
$activeNav     = 'gcsmssender-settings';
$topbarActions = '';
?>
<style>
.gss-balance{display:inline-flex;align-items:center;gap:10px;background:var(--accent)18;border:1px solid var(--accent)44;color:var(--accent);border-radius:10px;padding:9px 20px;font-size:15px;font-weight:800}
.gss-ok{color:#10b981;font-weight:700}
.gss-err{color:#ef4444;font-weight:700;font-size:13px}
.gss-tip{font-size:12px;color:var(--muted);margin-top:5px}
.gss-smsno{display:flex;gap:10px}
.gss-smsno label{flex:1;display:flex;align-items:flex-start;gap:10px;border:1.5px solid var(--border);border-radius:10px;padding:12px 14px;cursor:pointer;transition:border-color .15s}
.gss-smsno label:has(input:checked){border-color:var(--accent);background:var(--accent)0d}
.gss-smsno input{margin-top:2px;flex-shrink:0;accent-color:var(--accent)}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:20px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

    <!-- Settings form -->
    <div class="card">
        <div class="card-header"><h3>API კავშირი</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/gcsmssender/settings">

                <div class="form-group">
                    <label class="form-label">API Key <span style="color:#ef4444">*</span></label>
                    <input type="text" name="api_key" class="form-input"
                           value="<?= e($sms->setting('api_key')) ?>"
                           placeholder="sender.ge-ზე მიღებული API გასაღები"
                           autocomplete="off">
                    <div class="gss-tip">
                        API გასაღები შეგიძლიათ მოიძიოთ
                        <a href="https://sender.ge" target="_blank" rel="noopener">sender.ge</a>-ზე.
                        Sender Title-ის გამოყენებისთვის საჭიროა support-ის მეშვეობით მოთხოვნა.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="margin-bottom:10px">SMS ტიპი (smsno) — ნაგულისხმევი</label>
                    <div class="gss-smsno">
                        <label>
                            <input type="radio" name="smsno" value="1"
                                   <?= $sms->setting('smsno', '2') === '1' ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700;font-size:13.5px">🏷 Sender Title-ით (smsno=1)</div>
                                <div class="gss-tip" style="margin-top:3px">მიმღები ხედავს დარეგისტრირებულ sender სახელს</div>
                            </div>
                        </label>
                        <label>
                            <input type="radio" name="smsno" value="2"
                                   <?= $sms->setting('smsno', '2') === '2' ? 'checked' : '' ?>>
                            <div>
                                <div style="font-weight:700;font-size:13.5px">📨 Sender Title-ის გარეშე (smsno=2)</div>
                                <div class="gss-tip" style="margin-top:3px">სტანდარტული SMS, sender სახელი არ გამოჩნდება</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:12px;align-items:center;margin-top:4px">
                    <button type="submit" class="btn btn-primary">შენახვა</button>
                    <?php if ($sms->setting('api_key') !== ''): ?>
                    <span class="gss-ok">✓ API გასაღები მითითებულია</span>
                    <?php else: ?>
                    <span class="gss-err">✕ API გასაღები არ არის მითითებული</span>
                    <?php endif ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Balance -->
        <div class="card">
            <div class="card-header"><h3>SMS ბალანსი</h3></div>
            <div class="card-body" style="text-align:center;padding:24px">
                <?php if ($balance !== null): ?>
                <div style="display:flex;flex-direction:column;gap:8px;align-items:center">
                    <div class="gss-balance">
                        <span>📩</span>
                        <span><?= number_format((float)($balance['balance'] ?? 0)) ?></span>
                    </div>
                    <div class="gss-tip">ხელმისაწვდომი ბალანსი</div>
                    <?php if (!empty($balance['overdraft'])): ?>
                    <div style="font-size:12px;color:#ef4444">
                        Overdraft: <?= number_format((float)$balance['overdraft']) ?>
                    </div>
                    <?php endif ?>
                </div>
                <?php elseif ($balErr): ?>
                <div class="gss-err"><?= e($balErr) ?></div>
                <?php else: ?>
                <div style="color:var(--muted);font-size:13px">შეიყვანეთ API გასაღები ბალანსის სანახავად.</div>
                <?php endif ?>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card">
            <div class="card-header"><h3>სწრაფი ბმულები</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                <a href="<?= e($base) ?>/manage/gcsmssender/send" class="btn btn-ghost" style="text-align:left">✉ SMS გაგზავნა</a>
                <a href="<?= e($base) ?>/manage/gcsmssender/logs" class="btn btn-ghost" style="text-align:left">📋 გაგზავნების ლოგი</a>
            </div>
        </div>

    </div>
</div>
