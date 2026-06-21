<?php
$pageTitle     = 'GCRating — Settings';
$activeNav     = 'gcrating';
$topbarActions = '';

$s = $settings; // alias
$retentionOptions = [
    '30'  => '30 დღე',
    '90'  => '90 დღე',
    '180' => '6 თვე',
    '365' => '1 წელი',
    '730' => '2 წელი',
    '0'   => 'არასდროს',
];
?>

<!-- ── Sub-nav ── -->
<div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
    <a href="<?= e($base) ?>/manage/gcrating" class="btn btn-ghost" style="font-size:13px">← Dashboard</a>
    <div style="width:1px;height:20px;background:var(--border)"></div>
    <a href="<?= e($base) ?>/manage/gcrating/pages" class="btn btn-ghost" style="font-size:13px">📄 გვერდები</a>
    <a href="<?= e($base) ?>/manage/gcrating/referrers" class="btn btn-ghost" style="font-size:13px">↗ წყაროები</a>
</div>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:16px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<!-- ── DB overview ── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px">
        <div style="font-weight:700;font-size:14px;margin-bottom:12px">🗄 მონაცემთა ბაზა</div>
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <div style="text-align:center">
                <div style="font-size:1.6rem;font-weight:800;color:#6366f1"><?= number_format((int)($totals['sessions'] ?? 0)) ?></div>
                <div style="font-size:12px;color:var(--muted)">სესია</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:1.6rem;font-weight:800;color:#10b981"><?= number_format((int)($totals['pageviews'] ?? 0)) ?></div>
                <div style="font-size:12px;color:var(--muted)">გვ. ნახვა</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Main settings form ── -->
<form method="POST" action="<?= e($base) ?>/manage/gcrating/settings">
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:22px 24px">
        <div style="font-weight:700;font-size:15px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px">
            ⚙ ძირითადი პარამეტრები
        </div>

        <!-- Enabled -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)">
            <div>
                <div style="font-weight:600;font-size:14px">📊 Tracking ჩართვა</div>
                <div style="font-size:12.5px;color:var(--muted);margin-top:3px">გამოიყენე ვიზიტორების სტატისტიკის ჩაწერა</div>
            </div>
            <label class="toggle-switch" style="flex-shrink:0">
                <input type="checkbox" name="enabled" value="1" <?= ($s['enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <!-- Exclude admin -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)">
            <div>
                <div style="font-weight:600;font-size:14px">👤 Admin-ის გამოტოვება</div>
                <div style="font-size:12.5px;color:var(--muted);margin-top:3px">ადმინ პანელში შესული მომხმარებლები არ ითვლებიან</div>
            </div>
            <label class="toggle-switch" style="flex-shrink:0">
                <input type="checkbox" name="exclude_admin" value="1" <?= ($s['exclude_admin'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <!-- Exclude bots -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)">
            <div>
                <div style="font-weight:600;font-size:14px">🤖 Bot-ების ფილტრი</div>
                <div style="font-size:12.5px;color:var(--muted);margin-top:3px">სკანერები, crawlers, headless ბრაუზერები ჩაიჭრება</div>
            </div>
            <label class="toggle-switch" style="flex-shrink:0">
                <input type="checkbox" name="exclude_bots" value="1" <?= ($s['exclude_bots'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <!-- Anonymize IP -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)">
            <div>
                <div style="font-weight:600;font-size:14px">🔒 IP-ის ანონიმიზება</div>
                <div style="font-size:12.5px;color:var(--muted);margin-top:3px">IP მისამართი არ ინახება — GDPR-თან შესაბამისი</div>
            </div>
            <label class="toggle-switch" style="flex-shrink:0">
                <input type="checkbox" name="anonymize_ip" value="1" <?= ($s['anonymize_ip'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>

        <!-- Retention -->
        <div style="padding:14px 0;border-bottom:1px solid var(--border)">
            <div style="font-weight:600;font-size:14px;margin-bottom:6px">🗑 მონაცემების შენახვის ვადა</div>
            <div style="font-size:12.5px;color:var(--muted);margin-bottom:10px">ძველი მონაცემი ავტომატურად წაიშლება</div>
            <select name="retention_days" class="form-control" style="max-width:220px">
                <?php foreach ($retentionOptions as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= ($s['retention_days'] ?? '365') === (string)$val ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
                <?php endforeach ?>
            </select>
        </div>

        <!-- Exclude IPs -->
        <div style="padding:14px 0">
            <div style="font-weight:600;font-size:14px;margin-bottom:6px">🚫 IP-ების გამოტოვება</div>
            <div style="font-size:12.5px;color:var(--muted);margin-bottom:10px">
                ჩამოიყვანე IP-ები (თითო ხაზზე ერთი): <code>192.168.1.1</code>
            </div>
            <textarea name="exclude_ips" class="form-control" rows="4"
                      style="max-width:400px;font-family:monospace;font-size:13px"
                      placeholder="192.168.1.1&#10;10.0.0.1"><?= e($s['exclude_ips'] ?? '') ?></textarea>
        </div>
    </div>
</div>

<div style="margin-bottom:20px">
    <button type="submit" class="btn btn-primary">💾 შენახვა</button>
</div>
</form>

<!-- ── Tracking info ── -->
<div class="card" style="margin-bottom:20px;border:1.5px solid #bfdbfe">
    <div class="card-body" style="padding:18px 20px;background:#eff6ff;border-radius:inherit">
        <div style="font-weight:700;font-size:14px;color:#1d4ed8;margin-bottom:10px">ℹ️ როგორ მუშაობს</div>
        <ul style="font-size:13px;color:#1e40af;margin:0;padding-left:20px;line-height:1.8">
            <li>JS სკრიპტი ავტომატურად ემატება ყველა საჯარო გვერდს <code>&lt;/body&gt;</code>-ის წინ</li>
            <li>სესიები: <code>sessionStorage</code> (ლეიფობს ჩანართის დახურვისას)</li>
            <li>უნიკ. ვიზიტ.: <code>localStorage</code> (განახლდება ბრაუზერის გასუფთავებისას)</li>
            <li>გვერდზე გატარებული დრო: <code>sendBeacon</code>-ით იგზავნება გვერდის დატოვებისას</li>
            <li>IP მისამართი <strong>არასდროს</strong> ინახება ბაზაში</li>
            <li>UTM პარამეტრები (<code>utm_source</code>, <code>utm_medium</code>, <code>utm_campaign</code>) ავტომატურად ითვლება</li>
        </ul>
    </div>
</div>

<!-- ── Danger zone ── -->
<div class="card" style="border:1.5px solid #fca5a5">
    <div class="card-body" style="padding:20px 24px">
        <div style="font-weight:700;font-size:14px;color:#ef4444;margin-bottom:10px">⚠️ Danger Zone</div>
        <div style="font-size:13px;color:var(--muted);margin-bottom:16px">
            ყველა სტატისტიკის წაშლა — ეს ქმედება <strong>შეუქცევადია</strong>!
            <br>სულ <?= number_format((int)($totals['sessions'] ?? 0)) ?> სესია და <?= number_format((int)($totals['pageviews'] ?? 0)) ?> გვ. ნახვა წაიშლება.
        </div>
        <details>
            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:#ef4444">🗑 ყველა მონაცემის წაშლა...</summary>
            <form method="POST" action="<?= e($base) ?>/manage/gcrating/clear"
                  style="margin-top:14px;padding:16px;background:#fff1f2;border:1px solid #fca5a5;border-radius:8px"
                  onsubmit="return confirm('ნამდვილად წავშალოთ ყველა სტატისტიკა?')">
                <div style="font-size:13px;margin-bottom:10px">
                    დასადასტურებლად ჩაწერე <code style="background:#fee2e2;padding:2px 6px;border-radius:4px">DELETE</code>:
                </div>
                <div style="display:flex;gap:10px;align-items:center">
                    <input type="text" name="confirm_clear" class="form-control"
                           style="max-width:160px;font-family:monospace;border-color:#fca5a5"
                           placeholder="DELETE" autocomplete="off">
                    <button type="submit" class="btn" style="background:#ef4444;color:#fff;border-color:#ef4444">
                        🗑 სრულად წაშლა
                    </button>
                </div>
            </form>
        </details>
    </div>
</div>
