<?php
$pageTitle     = 'GoniSocial — გაზიარების ღილაკები';
$activeNav     = 'gonisocial-share';
$topbarActions = '<a href="' . e($base) . '/manage/gonisocial" class="btn btn-ghost" style="font-size:13px">← Dashboard</a>';

$s              = $settings;
$enabledNets    = array_filter(array_map('trim', explode(',', $s['share_networks'] ?? 'facebook,twitter,whatsapp,telegram,linkedin')));
$currentPos     = $s['share_position'] ?? 'floating-left';
$shareEnabled   = ($s['share_enabled']     ?? '1') === '1';
$hideMobile     = ($s['share_hide_mobile'] ?? '0') === '1';

$allNetworks    = \GoniSocial\GoniSocialService::SHARE_NETWORKS;
$netColors = [
    'facebook'  => '#1877F2','twitter'   => '#000000','whatsapp'  => '#25D366',
    'telegram'  => '#2AABEE','linkedin'  => '#0A66C2','reddit'    => '#FF4500',
    'viber'     => '#7360F2','pinterest' => '#E60023','copy'      => '#475569',
];
$netLabels = [
    'facebook'  => 'Facebook','twitter'   => 'Twitter / X','whatsapp'  => 'WhatsApp',
    'telegram'  => 'Telegram','linkedin'  => 'LinkedIn',   'reddit'    => 'Reddit',
    'viber'     => 'Viber',   'pinterest' => 'Pinterest',  'copy'      => 'ლინკის კოპირება',
];
?>
<style>
.gsc-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.gsc-switch input{opacity:0;width:0;height:0}
.gsc-switch-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#cbd5e1;transition:.2s;border-radius:24px}
.gsc-switch-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;transition:.2s;border-radius:50%}
.gsc-switch input:checked + .gsc-switch-slider{background:#7c3aed}
.gsc-switch input:checked + .gsc-switch-slider:before{transform:translateX(20px)}

.gsc-pos-card{border:2px solid var(--border);border-radius:12px;padding:14px 18px;cursor:pointer;transition:border-color .15s;display:flex;align-items:center;gap:12px}
.gsc-pos-card input[type=radio]{display:none}
.gsc-pos-card.selected{border-color:#7c3aed;background:#faf5ff}
.gsc-pos-card:hover{border-color:#a78bfa}

.gsc-net-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;border:2px solid transparent;cursor:pointer;font-size:13px;font-weight:600;transition:opacity .15s,border-color .15s;user-select:none;color:#fff}
.gsc-net-btn input[type=checkbox]{display:none}
.gsc-net-btn.off{opacity:.35;border-color:transparent}
.gsc-net-btn.on{opacity:1;border-color:#000}

.gsc-preview-wrap{position:relative;background:#f1f5f9;border-radius:12px;padding:20px;min-height:120px;overflow:hidden}
.gsc-preview-btn{display:inline-flex;align-items:center;justify-content:center;width:44px;height:40px;border-radius:0 6px 6px 0;color:#fff;font-size:18px}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<div style="max-width:760px">
<form method="POST" action="<?= e($base) ?>/manage/gonisocial/share/save" id="shareForm">

<!-- Enable -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px">
        <div>
            <div style="font-weight:700;font-size:14px">Share ღილაკები ჩართულია</div>
            <div style="font-size:12.5px;color:var(--muted);margin-top:2px">ყველა გვერდზე გაზიარების ვიჯეტი გამოჩნდება.</div>
        </div>
        <div>
            <input type="hidden" name="share_enabled" value="0">
            <label class="gsc-switch">
                <input type="checkbox" name="share_enabled" value="1" <?= $shareEnabled ? 'checked' : '' ?>>
                <span class="gsc-switch-slider"></span>
            </label>
        </div>
    </div>
</div>

<!-- Position -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>📍 პოზიცია</h3></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
            <?php foreach ([
                'floating-left'  => ['◀ Floating Left',   'ეკრანის მარცხენა კიდეზე, შუაში'],
                'floating-right' => ['Floating Right ▶',  'ეკრანის მარჯვენა კიდეზე, შუაში'],
                'bottom-bar'     => ['▼ Bottom Bar',       'გვერდის ქვედა ბარი'],
            ] as $posVal => [$posLabel, $posDesc]): ?>
            <label class="gsc-pos-card <?= $currentPos === $posVal ? 'selected' : '' ?>"
                   onclick="gscSelectPos(this)" data-val="<?= $posVal ?>">
                <input type="radio" name="share_position" value="<?= $posVal ?>" <?= $currentPos === $posVal ? 'checked' : '' ?>>
                <div>
                    <div style="font-weight:700;font-size:13.5px"><?= $posLabel ?></div>
                    <div style="font-size:11.5px;color:var(--muted);margin-top:2px"><?= $posDesc ?></div>
                </div>
            </label>
            <?php endforeach ?>
        </div>
    </div>
</div>

<!-- Networks -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>🌐 ქსელები</h3></div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:8px" id="netBtns">
            <?php foreach ($allNetworks as $net):
                $on = in_array($net, $enabledNets, true);
            ?>
            <label class="gsc-net-btn <?= $on ? 'on' : 'off' ?>"
                   style="background:<?= $netColors[$net] ?? '#475569' ?>"
                   onclick="gscToggleNet(this, event)">
                <input type="checkbox" name="share_networks[]"
                       value="<?= $net ?>" <?= $on ? 'checked' : '' ?>>
                <?= $netLabels[$net] ?? ucfirst($net) ?>
            </label>
            <?php endforeach ?>
        </div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:10px">
            დაჭერით ჩართეთ / გამორთეთ. ქსელების თანმიმდევრობა ზემოთ-ქვემოდ ფიქსირებულია.
        </div>
    </div>
</div>

<!-- Hide on mobile -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:14px 22px">
        <div>
            <div style="font-weight:700;font-size:14px">მობილურზე დამალვა</div>
            <div style="font-size:12.5px;color:var(--muted);margin-top:2px">640px-ზე ნაკლები — ვიჯეტი დაიმალება.</div>
        </div>
        <div>
            <input type="hidden" name="share_hide_mobile" value="0">
            <label class="gsc-switch">
                <input type="checkbox" name="share_hide_mobile" value="1" <?= $hideMobile ? 'checked' : '' ?>>
                <span class="gsc-switch-slider"></span>
            </label>
        </div>
    </div>
</div>

<!-- Preview -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>👁 Preview</h3></div>
    <div class="card-body">
        <div class="gsc-preview-wrap" id="previewWrap">
            <div id="previewInner" style="display:flex;gap:3px"></div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:12px;font-style:italic">
                * Preview-ი ასახავს ღილაკების სახეს (პოზიცია გვერდზე განსხვავებული იქნება).
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px">
    <button type="submit" class="btn btn-primary">💾 შენახვა</button>
    <a href="<?= e($base) ?>/manage/gonisocial" class="btn btn-ghost">გაუქმება</a>
</div>
</form>
</div>

<script>
var netColors = <?= json_encode($netColors, JSON_UNESCAPED_UNICODE) ?>;

function gscSelectPos(el) {
    document.querySelectorAll('.gsc-pos-card').forEach(function(c){ c.classList.remove('selected'); });
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;
    gscUpdatePreview();
}

function gscToggleNet(el, ev) {
    ev.preventDefault(); // prevent native label→checkbox double-flip
    var cb = el.querySelector('input[type=checkbox]');
    cb.checked = !cb.checked;
    el.classList.toggle('on',  cb.checked);
    el.classList.toggle('off', !cb.checked);
    gscUpdatePreview();
}

function gscUpdatePreview() {
    var pos    = document.querySelector('input[name="share_position"]:checked');
    var posVal = pos ? pos.value : 'floating-left';
    var nets   = [];
    document.querySelectorAll('input[name="share_networks[]"]:checked').forEach(function(cb){nets.push(cb.value);});

    var style, br;
    if (posVal === 'floating-left')  { style = 'display:flex;flex-direction:column;gap:3px'; br = '0 6px 6px 0'; }
    else if (posVal === 'floating-right') { style = 'display:flex;flex-direction:column;gap:3px'; br = '6px 0 0 6px'; }
    else { style = 'display:flex;flex-direction:row;flex-wrap:wrap;gap:3px'; br = '8px'; }

    var html = '';
    nets.forEach(function(n){
        var c = netColors[n] || '#475569';
        html += '<div style="width:44px;height:40px;background:' + c + ';border-radius:' + br + ';display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700">' + n.charAt(0).toUpperCase() + '</div>';
    });

    document.getElementById('previewInner').style.cssText = style;
    document.getElementById('previewInner').innerHTML = html || '<span style="color:var(--muted);font-size:12px">ვერცერთი ქსელი არ არის არჩეული</span>';
}

gscUpdatePreview();
</script>
