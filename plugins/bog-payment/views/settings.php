<?php
$pageTitle     = 'BOG Payment Settings';
$activeNav     = 'bog-settings';
$topbarActions = '';
?>

<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Settings saved successfully." data-icon="success" style="display:none"></div>
<?php endif ?>

<div style="max-width:680px;display:flex;flex-direction:column;gap:16px">

<form method="POST" action="<?= e($base) ?>/manage/store/bog-settings">

    <!-- Enable/Disable -->
    <div class="card">
        <div class="card-header"><h3>Status</h3></div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:3px">Enable BOG Payment</div>
                    <div style="font-size:13px;color:var(--muted)">Show Bank of Georgia as a payment option at checkout.</div>
                </div>
                <label class="gc-toggle" style="flex-shrink:0">
                    <input type="hidden"   name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1"
                           <?= $bog->setting('enabled') === '1' ? 'checked' : '' ?>>
                    <span class="gc-toggle-track"><span class="gc-toggle-thumb"></span></span>
                </label>
            </div>
        </div>
    </div>

    <!-- Sandbox Mode -->
    <div class="card" style="border-color:<?= $bog->isSandbox() ? '#f59e0b' : 'var(--border)' ?>">
        <div class="card-header" style="<?= $bog->isSandbox() ? 'background:#fffbeb' : '' ?>">
            <h3>🧪 Sandbox / Test Mode</h3>
        </div>
        <div class="card-body">
            <?php if ($bog->isSandbox()): ?>
            <div style="background:#fef3c7;border:1.5px solid #f59e0b;border-radius:8px;padding:10px 14px;font-size:13px;color:#92400e;margin-bottom:16px;display:flex;align-items:center;gap:8px">
                <span style="font-size:16px">⚠️</span>
                <span><strong>Sandbox is active.</strong> Payments go to the BOG test environment — no real money is charged.</span>
            </div>
            <?php endif ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:3px">Enable Sandbox Mode</div>
                    <div style="font-size:13px;color:var(--muted)">
                        Use BOG test environment (<code style="font-size:12px">api-sandbox.bog.ge</code>).
                        Enter your sandbox credentials below. Disable for live payments.
                    </div>
                </div>
                <label class="gc-toggle" style="flex-shrink:0">
                    <input type="hidden"   name="sandbox" value="0">
                    <input type="checkbox" name="sandbox" value="1"
                           <?= $bog->setting('sandbox') === '1' ? 'checked' : '' ?>>
                    <span class="gc-toggle-track"><span class="gc-toggle-thumb"></span></span>
                </label>
            </div>
            <?php if ($bog->isSandbox()): ?>
            <div style="margin-top:16px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px 16px">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:10px">Test Cards</div>
                <table style="width:100%;font-size:12.5px;border-collapse:collapse">
                    <tr style="color:var(--muted);font-weight:600">
                        <th style="text-align:left;padding:4px 8px 4px 0">Card number</th>
                        <th style="text-align:left;padding:4px 8px">Network</th>
                        <th style="text-align:left;padding:4px 0">Result</th>
                    </tr>
                    <tr><td style="padding:4px 8px 4px 0;font-family:monospace">4000 0000 0000 0001</td><td style="padding:4px 8px">Visa</td><td style="padding:4px 0;color:#10b27c">✓ Success</td></tr>
                    <tr><td style="padding:4px 8px 4px 0;font-family:monospace">4000 0000 0000 0002</td><td style="padding:4px 8px">Visa</td><td style="padding:4px 0;color:#ef4444">✗ Insufficient funds</td></tr>
                    <tr><td style="padding:4px 8px 4px 0;font-family:monospace">5300 0000 0000 0001</td><td style="padding:4px 8px">Mastercard</td><td style="padding:4px 0;color:#10b27c">✓ Success</td></tr>
                    <tr><td style="padding:4px 8px 4px 0;font-family:monospace">5300 0000 0000 0002</td><td style="padding:4px 8px">Mastercard</td><td style="padding:4px 0;color:#ef4444">✗ Insufficient funds</td></tr>
                </table>
                <div style="font-size:11.5px;color:var(--muted);margin-top:8px">Any expiry date and CVV are accepted in sandbox.</div>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- API Credentials -->
    <div class="card">
        <div class="card-header"><h3 id="apiCredTitle">🔑 <?= $bog->isSandbox() ? 'Sandbox API Credentials' : 'API Credentials' ?></h3></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--muted);margin-bottom:16px">
                <?php if ($bog->isSandbox()): ?>
                Sandbox credentials — obtain from the
                <a href="https://businessmanager.bog.ge" target="_blank" rel="noopener">BOG Business Manager</a> test portal.
                <?php else: ?>
                Obtain your credentials from the
                <a href="https://api.bog.ge" target="_blank" rel="noopener">BOG merchant portal</a>.
                <?php endif ?>
            </p>
            <div class="form-group">
                <label class="form-label">Client ID</label>
                <input type="text" name="client_id" class="form-input"
                       value="<?= e($bog->setting('client_id')) ?>"
                       placeholder="your-client-id" autocomplete="off">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Client Secret</label>
                <input type="password" name="client_secret" class="form-input"
                       value="<?= e($bog->setting('client_secret')) ?>"
                       placeholder="••••••••" autocomplete="new-password">
                <div style="font-size:12px;color:var(--muted);margin-top:5px">
                    Keep this value secret — never share or expose it publicly.
                </div>
            </div>
        </div>
    </div>

    <!-- Settings -->
    <div class="card">
        <div class="card-header"><h3>⚙ Payment Settings</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select" style="max-width:220px">
                    <?php foreach (['GEL' => 'Georgian Lari (GEL)', 'USD' => 'US Dollar (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'British Pound (GBP)'] as $v => $l): ?>
                    <option value="<?= e($v) ?>" <?= $bog->setting('currency', 'GEL') === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Capture Mode</label>
                <select name="capture" class="form-select" style="max-width:300px">
                    <option value="automatic" <?= $bog->setting('capture','automatic')==='automatic'?'selected':'' ?>>Automatic — charge immediately</option>
                    <option value="manual"    <?= $bog->setting('capture','automatic')==='manual'   ?'selected':'' ?>>Manual (Preauth) — hold then approve/cancel</option>
                </select>
                <div style="font-size:12px;color:var(--muted);margin-top:5px">Manual = funds are blocked until you approve from the Transactions page.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Checkout Page Slug</label>
                <input type="text" name="checkout_slug" class="form-input"
                       value="<?= e($bog->setting('checkout_slug', 'checkout')) ?>"
                       placeholder="checkout" style="max-width:220px">
                <div style="font-size:12px;color:var(--muted);margin-top:5px">
                    Used for the "payment failed" redirect URL.
                </div>
            </div>
        </div>
    </div>

    <!-- Webhook info -->
    <div class="card">
        <div class="card-header"><h3>🔗 Webhook / Callback URL</h3></div>
        <div class="card-body">
            <p style="font-size:13.5px;color:var(--muted);margin-bottom:14px">
                Register this URL in your BOG merchant portal as the async callback endpoint:
            </p>
            <?php
            $cbScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $cbHost   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $cbUrl    = $cbScheme . '://' . $cbHost . rtrim((string)($base ?? ''), '/') . '/bog/callback';
            ?>
            <div style="background:var(--bg);border:1.5px solid var(--border);border-radius:8px;padding:12px 16px;font-family:'Courier New',monospace;font-size:13px;color:var(--text);word-break:break-all;display:flex;align-items:center;justify-content:space-between;gap:12px">
                <span id="callbackUrl"><?= e($cbUrl) ?></span>
                <button type="button" onclick="copyCallback()" style="background:none;border:none;cursor:pointer;color:var(--accent);font-size:15px;padding:2px;flex-shrink:0" title="Copy">⎘</button>
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:8px">
                BOG will POST signed JSON to this endpoint after each payment attempt.
            </div>
        </div>
    </div>

    <div>
        <!-- Notifications -->
    <div class="card">
        <div class="card-header"><h3>🔔 Notifications</h3></div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:3px">Admin email on successful payment</div>
                    <div style="font-size:13px;color:var(--muted)">Send admin notification each time a BOG payment is confirmed.</div>
                </div>
                <label class="gc-toggle" style="flex-shrink:0">
                    <input type="hidden"   name="notify_admin" value="0">
                    <input type="checkbox" name="notify_admin" value="1"
                           <?= $bog->setting('notify_admin','1') !== '0' ? 'checked' : '' ?>>
                    <span class="gc-toggle-track"><span class="gc-toggle-thumb"></span></span>
                </label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="padding:11px 28px;font-size:15px">Save Settings</button>
    </div>

</form>
</div>

<style>
.gc-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.gc-toggle input[type=checkbox]{position:absolute;opacity:0;width:0;height:0}
.gc-toggle input[type=hidden]+input[type=checkbox]{position:absolute}
.gc-toggle-track{width:40px;height:22px;background:#cbd5e1;border-radius:11px;transition:background .2s;position:relative}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track{background:var(--accent)}
.gc-toggle-thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track .gc-toggle-thumb{transform:translateX(18px)}
</style>

<script>
function copyCallback() {
    var text = document.getElementById('callbackUrl').textContent;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            gcToast('Copied to clipboard!', 'success', 2000);
        });
    }
}

document.querySelector('input[name="sandbox"][type="checkbox"]').addEventListener('change', function() {
    document.getElementById('apiCredTitle').textContent = this.checked
        ? '🔑 Sandbox API Credentials'
        : '🔑 API Credentials';
});
</script>
