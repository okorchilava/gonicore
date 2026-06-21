<?php
$pageTitle     = 'GCpolicy — Settings';
$activeNav     = 'gcpolicy-settings';
$topbarActions = '';

$s = $settings;
?>
<style>
/* Toggle switch */
.gcp-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer;flex-shrink:0}
.gcp-toggle input{opacity:0;width:0;height:0;position:absolute}
.gcp-slider{position:relative;display:inline-block;width:48px;height:26px;background:var(--border);border-radius:26px;transition:.25s;flex-shrink:0}
.gcp-slider:before{position:absolute;content:'';height:20px;width:20px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
.gcp-toggle input:checked~.gcp-slider{background:#7c3aed}
.gcp-toggle input:checked~.gcp-slider:before{transform:translateX(22px)}
/* Preview bar */
.gcp-prev-wrap{background:#1e293b;border-radius:0 0 10px 10px;padding:14px 18px;display:flex;align-items:center;gap:12px;min-height:58px}
.gcp-prev-text{flex:1;font-size:12.5px;color:#cbd5e1;line-height:1.5}
.gcp-prev-text a{color:#a78bfa;text-decoration:underline}
.gcp-prev-btns{display:flex;gap:8px;align-items:center;flex-shrink:0}
.gcp-prev-btn-decline{background:transparent;color:#94a3b8;border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:7px 14px;font-size:12.5px;font-weight:600;cursor:default;white-space:nowrap;font-family:inherit}
.gcp-prev-btn-accept{background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:12.5px;font-weight:700;cursor:default;white-space:nowrap;font-family:inherit}
/* Toggle row */
.gcp-toggle-row{display:flex;align-items:center;justify-content:space-between;gap:16px;background:var(--bg-2,rgba(0,0,0,.03));border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:16px}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:20px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

    <!-- ── Settings form ── -->
    <div class="card">
        <div class="card-header"><h3>ბანერის პარამეტრები</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/gcpolicy/settings">

                <!-- Enable / disable banner -->
                <div class="gcp-toggle-row">
                    <div>
                        <div style="font-weight:700;font-size:14px">Cookie ბანერი</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px">საიტის ყველა გვერდზე გამოჩნდება</div>
                    </div>
                    <label class="gcp-toggle">
                        <input type="hidden"   name="enabled" id="gcpEnabledHidden" value="<?= $s['enabled'] ?>">
                        <input type="checkbox" id="gcpEnabledCb"
                               <?= $s['enabled'] === '1' ? 'checked' : '' ?>
                               onchange="document.getElementById('gcpEnabledHidden').value=this.checked?'1':'0'">
                        <span class="gcp-slider"></span>
                    </label>
                </div>

                <!-- Text -->
                <div class="form-group">
                    <label class="form-label" for="gcpText">შეტყობინების ტექსტი</label>
                    <textarea name="text" id="gcpText" class="form-input" rows="2"
                              oninput="gcpPreview()"><?= e($s['text']) ?></textarea>
                </div>

                <!-- Link text + URL -->
                <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label" for="gcpLinkText">ბმულის ტექსტი</label>
                        <input type="text" name="link_text" id="gcpLinkText" class="form-input"
                               value="<?= e($s['link_text']) ?>" oninput="gcpPreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gcpLinkUrl">Cookie Policy — URL</label>
                        <input type="text" name="link_url" id="gcpLinkUrl" class="form-input"
                               value="<?= e($s['link_url']) ?>"
                               placeholder="https://example.com/cookie-policy"
                               oninput="gcpPreview()">
                    </div>
                </div>

                <!-- Accept button text + Position -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label" for="gcpBtnText">„მიღება" ღილაკის ტექსტი</label>
                        <input type="text" name="btn_text" id="gcpBtnText" class="form-input"
                               value="<?= e($s['btn_text']) ?>" oninput="gcpPreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gcpPos">პოზიცია</label>
                        <select name="position" id="gcpPos" class="form-input">
                            <option value="bottom" <?= $s['position'] === 'bottom' ? 'selected' : '' ?>>⬇ ქვემოთ (Bottom)</option>
                            <option value="top"    <?= $s['position'] === 'top'    ? 'selected' : '' ?>>⬆ ზემოთ (Top)</option>
                        </select>
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:8px 0 20px">

                <!-- Decline button toggle -->
                <div class="gcp-toggle-row">
                    <div>
                        <div style="font-weight:700;font-size:14px">„უარყოფა" ღილაკი</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px">მომხმარებელს შეეძლება ქუქიების გათიშვა</div>
                    </div>
                    <label class="gcp-toggle">
                        <input type="hidden"   name="show_decline" id="gcpDeclineHidden" value="<?= $s['show_decline'] ?>">
                        <input type="checkbox" id="gcpDeclineCb"
                               <?= $s['show_decline'] === '1' ? 'checked' : '' ?>
                               onchange="document.getElementById('gcpDeclineHidden').value=this.checked?'1':'0'; gcpPreview()">
                        <span class="gcp-slider"></span>
                    </label>
                </div>

                <!-- Decline text + expire days -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label" for="gcpDeclineText">„უარყოფა" ღილაკის ტექსტი</label>
                        <input type="text" name="decline_text" id="gcpDeclineText" class="form-input"
                               value="<?= e($s['decline_text']) ?>" oninput="gcpPreview()">
                        <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                            გამოჩნდება მხოლოდ „უარყოფა" ჩართვისას
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gcpExpireDays">Cookie ვადა (დღე)</label>
                        <input type="number" name="expire_days" id="gcpExpireDays" class="form-input"
                               value="<?= e($s['expire_days']) ?>" min="1" max="3650">
                        <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                            ნაგულისხმევი: 365 დღე (1 წელი)
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">შენახვა</button>
            </form>
        </div>
    </div>

    <!-- ── Right column ── -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Live preview -->
        <div class="card">
            <div class="card-header"><h3>გადახედვა</h3></div>
            <div class="gcp-prev-wrap">
                <span style="font-size:24px;flex-shrink:0;line-height:1">🍪</span>
                <span class="gcp-prev-text" id="gcpPrevText"></span>
                <div class="gcp-prev-btns" id="gcpPrevBtns">
                    <button type="button" class="gcp-prev-btn-decline" id="gcpPrevDecline" style="display:none"></button>
                    <button type="button" class="gcp-prev-btn-accept"  id="gcpPrevAccept"></button>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="card">
            <div class="card-header"><h3>ინფო</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:12px;font-size:13px;color:var(--muted)">
                <div>🍪 პასუხი ინახება ბრაუზერის Cookie-ში (<code style="font-size:11px;background:var(--border);padding:1px 6px;border-radius:4px">gc_consent=accepted</code> ან <code style="font-size:11px;background:var(--border);padding:1px 6px;border-radius:4px">declined</code>).</div>
                <div>⚡ Cookie PHP-ს მხრიდან მოწმდება — ბანერი საერთოდ არ ინექციამდება, თუ Cookie უკვე არსებობს (ყოველგვარი Flash-ის გარეშე).</div>
                <div>🔄 Cookie-ის გასაწმენდად (ბანერის ხელახლა საჩვენებლად) გამოძახე JS ფუნქცია: <code style="font-size:11px;background:var(--border);padding:1px 6px;border-radius:4px">gcCbReset()</code></div>
                <div>🌐 ბანერი ავტომატურად ემატება ყველა HTML გვერდს.</div>
                <div>🌙 ადაპტირდება მუქ და ნათელ თემზე.</div>
                <div>📱 მობილური ეკრანზე ბანერი ვერტიკალურია.</div>
            </div>
        </div>

    </div>

</div>

<script>
function gcpPreview() {
    var text        = document.getElementById('gcpText').value;
    var linkText    = document.getElementById('gcpLinkText').value;
    var linkUrl     = document.getElementById('gcpLinkUrl').value || '#';
    var btnText     = document.getElementById('gcpBtnText').value;
    var showDecline = document.getElementById('gcpDeclineCb').checked;
    var declineText = document.getElementById('gcpDeclineText').value;

    // Text + link
    var container = document.getElementById('gcpPrevText');
    container.innerHTML = '';
    container.appendChild(document.createTextNode(text + ' '));
    var a = document.createElement('a');
    a.href        = linkUrl;
    a.textContent = linkText;
    container.appendChild(a);
    container.appendChild(document.createTextNode('.'));

    // Accept button
    document.getElementById('gcpPrevAccept').textContent = btnText;

    // Decline button (show/hide)
    var decBtn = document.getElementById('gcpPrevDecline');
    decBtn.textContent   = declineText;
    decBtn.style.display = showDecline ? '' : 'none';
}
gcpPreview();
</script>
