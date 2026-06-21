<?php
$pageTitle     = $isEdit ? 'GCPopup — პოპაპის რედაქტირება' : 'GCPopup — ახალი პოპაპი';
$activeNav     = 'gcpopup';
$topbarActions = '';

$p  = $popup  ?? [];
$its = $items ?? [];
$v  = static fn($key, $default = '') => htmlspecialchars((string)($p[$key] ?? $default), ENT_QUOTES);

// Preset icons for quick selection
$presetIcons = ['🚀','✅','🔔','🛡️','⭐','💎','🎯','🔑','💡','📱','💳','🎁','🔒','❤️','⚡','📊','🌟','✨','🏆','🔥'];
?>

<div style="display:flex;gap:8px;align-items:center;margin-bottom:20px">
    <a href="<?= e($base) ?>/manage/gcpopup" class="btn btn-ghost" style="font-size:13px">← პოპაპები</a>
</div>

<?php if ($error === 'name'): ?>
<div class="alert alert-error" style="margin-bottom:16px">⚠ შეიყვანე პოპაპის სახელი.</div>
<?php endif ?>

<form method="POST" action="<?= e($base) ?>/manage/gcpopup/save" id="gcpForm">
<?php if ($isEdit): ?>
<input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

<!-- ══════════════ LEFT COLUMN ══════════════ -->
<div>

<!-- ── General ──────────────────────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="font-weight:700;font-size:14px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:10px">📋 ზოგადი</div>

    <div style="margin-bottom:14px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">სახელი (შიდა) <span style="color:#ef4444">*</span></label>
        <input type="text" name="name" class="form-control" required
               value="<?= $v('name') ?>" placeholder="მაგ. Login Popup, Sale Offer...">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">სტატუსი</label>
            <label class="toggle-switch">
                <input type="checkbox" name="active" value="1" <?= (!$isEdit || (int)($p['active'] ?? 1)) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
            <span style="font-size:12.5px;color:var(--muted);margin-left:8px">ჩართული</span>
        </div>
        <div>
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">Sort Order</label>
            <input type="number" name="sort_order" class="form-control" min="0" max="999"
                   value="<?= (int)($p['sort_order'] ?? 0) ?>" style="max-width:90px">
        </div>
    </div>
</div>
</div>

<!-- ── Image ──────────────────────────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="font-weight:700;font-size:14px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:10px">🖼 სურათი</div>

    <div style="margin-bottom:12px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">სურათის URL</label>
        <input type="text" name="image_url" class="form-control" id="gcpImageUrl"
               value="<?= $v('image_url') ?>" placeholder="https://... ან /uploads/img.png"
               oninput="gcpPreviewImg()">
        <div style="font-size:12px;color:var(--muted);margin-top:3px">დატოვე ცარიელი — ნაჩვენებია მხოლოდ ფერადი წრე</div>
    </div>
    <div style="margin-bottom:12px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">Alt ტექსტი</label>
        <input type="text" name="image_alt" class="form-control" value="<?= $v('image_alt') ?>">
    </div>
    <div>
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">სურათის ფონის ფერი</label>
        <div style="display:flex;align-items:center;gap:8px">
            <input type="color" name="image_bg_color" value="<?= $v('image_bg_color', '#e0fdf4') ?>"
                   style="width:44px;height:36px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
            <input type="text" id="gcpImgBgTxt" class="form-control" style="max-width:110px"
                   value="<?= $v('image_bg_color', '#e0fdf4') ?>"
                   oninput="document.querySelector('[name=image_bg_color]').value=this.value"
                   onchange="document.querySelector('[name=image_bg_color]').value=this.value">
        </div>
        <!-- Presets -->
        <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
            <?php foreach (['#e0fdf4','#eff6ff','#fdf4ff','#fff7ed','#fef2f2','#f0f9ff','#fafafa'] as $c): ?>
            <button type="button" onclick="gcpSetImgBg('<?= $c ?>')"
                    style="width:24px;height:24px;border-radius:50%;background:<?= $c ?>;border:1.5px solid #cbd5e1;cursor:pointer"
                    title="<?= $c ?>"></button>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Preview thumbnail -->
    <div id="gcpImgPreview" style="margin-top:12px;display:<?= $v('image_url') ? 'flex' : 'none' ?>;justify-content:center;padding:14px;border-radius:10px;background:<?= $v('image_bg_color','#e0fdf4') ?>">
        <img id="gcpImgThumb" src="<?= $v('image_url') ?>" alt="" style="max-height:80px;max-width:100%;object-fit:contain;border-radius:8px">
    </div>
</div>
</div>

<!-- ── Content ────────────────────────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="font-weight:700;font-size:14px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:10px">📝 კონტენტი</div>

    <div style="margin-bottom:12px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">სათაური (Title)</label>
        <input type="text" name="title" class="form-control"
               value="<?= $v('title') ?>" placeholder="გაიარე ავტორიზაცია...">
    </div>
    <div style="margin-bottom:12px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">ქვე-სათაური (Subtitle)</label>
        <input type="text" name="subtitle" class="form-control"
               value="<?= $v('subtitle') ?>" placeholder="სურვილისამებრ">
    </div>
    <!-- Badge -->
    <div style="padding:14px;border:1.5px solid var(--border);border-radius:10px">
        <div style="font-weight:600;font-size:13px;margin-bottom:10px;color:#374151">🏷 ბეიჯი (Badge)</div>
        <div style="margin-bottom:10px">
            <input type="text" name="badge_text" class="form-control"
                   value="<?= $v('badge_text') ?>" placeholder="✓ გვენდობა 2 მილიონი მომხმარებელი">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div>
                <label style="font-size:12px;display:block;margin-bottom:4px;color:var(--muted)">ფონი</label>
                <input type="color" name="badge_color" value="<?= $v('badge_color','#d1fae5') ?>"
                       style="width:100%;height:34px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
            </div>
            <div>
                <label style="font-size:12px;display:block;margin-bottom:4px;color:var(--muted)">ტექსტის ფერი</label>
                <input type="color" name="badge_text_color" value="<?= $v('badge_text_color','#065f46') ?>"
                       style="width:100%;height:34px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
            </div>
        </div>
    </div>
</div>
</div>

<!-- ── Feature items ──────────────────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:10px">
        <div style="font-weight:700;font-size:14px">⚡ Feature სიის ელემენტები</div>
        <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="gcpAddItem()">+ დამატება</button>
    </div>

    <!-- Quick icon picker -->
    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:14px;padding:10px;background:var(--bg-subtle,#f8fafc);border-radius:8px">
        <div style="width:100%;font-size:11.5px;color:var(--muted);margin-bottom:4px">სწრაფი icon-ები (დააჭირე + დაამატე item):</div>
        <?php foreach ($presetIcons as $ic): ?>
        <button type="button" onclick="gcpAddItem('<?= htmlspecialchars($ic, ENT_QUOTES) ?>')"
                style="padding:4px 7px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;font-size:1rem"
                title="<?= htmlspecialchars($ic, ENT_QUOTES) ?>"><?= $ic ?></button>
        <?php endforeach ?>
    </div>

    <div id="gcpItems">
        <?php if (empty($its)): ?>
        <!-- placeholder row (empty state, will be replaced) -->
        <div style="text-align:center;padding:14px;color:var(--muted);font-size:13px" id="gcpItemsEmpty">
            დამატებული ელემენტი არ არის — დააჭირე "+ დამატება"
        </div>
        <?php else: ?>
        <?php foreach ($its as $i => $item): ?>
        <div class="gcp-item-row" style="display:grid;grid-template-columns:56px 1fr 30px;gap:8px;align-items:center;margin-bottom:8px">
            <input type="text" name="items[<?= $i ?>][icon]" class="form-control" style="text-align:center;font-size:1.1rem;padding:6px 4px"
                   value="<?= htmlspecialchars((string)$item['icon'], ENT_QUOTES) ?>" placeholder="🔥" maxlength="10">
            <input type="text" name="items[<?= $i ?>][text]" class="form-control" style="font-size:13px"
                   value="<?= htmlspecialchars((string)$item['text'], ENT_QUOTES) ?>" placeholder="ელემენტის ტექსტი..." required>
            <button type="button" onclick="this.closest('.gcp-item-row').remove();gcpReindex()"
                    style="width:30px;height:30px;border:none;background:none;color:#ef4444;cursor:pointer;font-size:16px;padding:0">×</button>
        </div>
        <?php endforeach ?>
        <?php endif ?>
    </div>
</div>
</div>

</div><!-- /left column -->

<!-- ══════════════ RIGHT COLUMN ══════════════ -->
<div>

<!-- ── Button & Footer ───────────────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="font-weight:700;font-size:14px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:10px">🔵 ღილაკი (CTA)</div>

    <div style="margin-bottom:12px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">ტექსტი</label>
        <input type="text" name="btn_text" class="form-control" value="<?= $v('btn_text') ?>" placeholder="შესვლა">
    </div>
    <div style="margin-bottom:12px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">URL (ლინკი)</label>
        <input type="text" name="btn_url" class="form-control" value="<?= $v('btn_url') ?>" placeholder="/login">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
        <div>
            <label style="font-size:12px;display:block;margin-bottom:4px;color:var(--muted)">ღილაკის ფერი</label>
            <input type="color" name="btn_color" value="<?= $v('btn_color','#2563eb') ?>"
                   style="width:100%;height:34px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
        </div>
        <div>
            <label style="font-size:12px;display:block;margin-bottom:4px;color:var(--muted)">ტექსტის ფერი</label>
            <input type="color" name="btn_text_color" value="<?= $v('btn_text_color','#ffffff') ?>"
                   style="width:100%;height:34px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
        </div>
    </div>

    <div style="border-top:1px solid var(--border);padding-top:16px">
        <div style="font-weight:700;font-size:13.5px;margin-bottom:12px;color:#374151">🔗 Footer ტექსტი</div>
        <div style="margin-bottom:10px">
            <label style="font-size:12px;display:block;margin-bottom:4px;color:var(--muted)">ტექსტი</label>
            <input type="text" name="footer_text" class="form-control"
                   value="<?= $v('footer_text') ?>" placeholder="არ გაქვს ანგარიში?">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div>
                <label style="font-size:12px;display:block;margin-bottom:4px;color:var(--muted)">ლინკის ტექსტი</label>
                <input type="text" name="footer_link_text" class="form-control"
                       value="<?= $v('footer_link_text') ?>" placeholder="შექმენი">
            </div>
            <div>
                <label style="font-size:12px;display:block;margin-bottom:4px;color:var(--muted)">ლინკის URL</label>
                <input type="text" name="footer_link_url" class="form-control"
                       value="<?= $v('footer_link_url') ?>" placeholder="/register">
            </div>
        </div>
    </div>
</div>
</div>

<!-- ── Trigger ────────────────────────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="font-weight:700;font-size:14px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:10px">⚙ Trigger (გამოჩენის პირობა)</div>

    <div style="margin-bottom:14px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:8px">გამოჩენის მეთოდი</label>
        <?php
        $triggers = [
            'load'   => ['⏱', 'გვერდის ჩატვირთვა', 'auto-show N წამის შემდეგ'],
            'scroll' => ['📜', 'გადახვევა (Scroll)', 'auto-show X%-ის გადახვევის შემდეგ'],
            'exit'   => ['🚪', 'Exit Intent', 'mouse-ი ბრაუზერს გარეთ გავიდა'],
            'manual' => ['🖱', 'Manual', 'მხოლოდ gcpShow(id) ან Shortcode-ით'],
        ];
        $curTrigger = $p['trigger_type'] ?? 'load';
        foreach ($triggers as $val => [$ico, $name, $desc]):
        $sel = $curTrigger === $val;
        ?>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:9px 12px;margin-bottom:6px;
                      border:1.5px solid <?= $sel ? 'var(--primary)' : 'var(--border)' ?>;border-radius:9px;
                      background:<?= $sel ? '#eff6ff' : '' ?>">
            <input type="radio" name="trigger_type" value="<?= $val ?>"
                   <?= $sel ? 'checked' : '' ?> onchange="gcpTriggerChange(this.value)">
            <span style="font-size:1.1rem"><?= $ico ?></span>
            <div>
                <div style="font-weight:600;font-size:13px"><?= $name ?></div>
                <div style="font-size:11.5px;color:var(--muted)"><?= $desc ?></div>
            </div>
        </label>
        <?php endforeach ?>
    </div>

    <!-- Delay (load trigger) -->
    <div id="gcpDelayWrap" style="display:<?= $curTrigger === 'load' ? 'block' : 'none' ?>;margin-bottom:4px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">⏱ შეყოვნება (წამი)</label>
        <div style="display:flex;align-items:center;gap:10px">
            <input type="range" name="trigger_delay" min="0" max="30" step="1"
                   value="<?= (int)($p['trigger_delay'] ?? 3) ?>"
                   oninput="document.getElementById('gcpDelayVal').textContent=this.value+'წ'"
                   style="flex:1">
            <span id="gcpDelayVal" style="font-weight:700;color:#2563eb;min-width:28px"><?= (int)($p['trigger_delay'] ?? 3) ?>წ</span>
        </div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:3px">0 = მყისიერი ჩატვირთვა</div>
    </div>

    <!-- Scroll % (scroll trigger) -->
    <div id="gcpScrollWrap" style="display:<?= $curTrigger === 'scroll' ? 'block' : 'none' ?>;margin-bottom:4px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">📜 გადახვევის % (1–99)</label>
        <div style="display:flex;align-items:center;gap:10px">
            <input type="range" name="trigger_scroll" min="1" max="99" step="1"
                   value="<?= (int)($p['trigger_scroll'] ?? 50) ?>"
                   oninput="document.getElementById('gcpScrollVal').textContent=this.value+'%'"
                   style="flex:1">
            <span id="gcpScrollVal" style="font-weight:700;color:#2563eb;min-width:32px"><?= (int)($p['trigger_scroll'] ?? 50) ?>%</span>
        </div>
    </div>
</div>
</div>

<!-- ── Display frequency & pages ─────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="font-weight:700;font-size:14px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:10px">📅 ჩვენების სიხშირე</div>

    <?php
    $freqs = [
        'always'       => ['ყოველ ჯერ',           'popup ჩნდება ყოველ ჯერ'],
        'once_session' => ['სესიაში ერთხელ',       'ახალ ჩანართში ერთხელ (sessionStorage)'],
        'once_day'     => ['დღეში ერთხელ',         '24 საათში ერთხელ (localStorage)'],
        'once_ever'    => ['მხოლოდ ერთხელ სულ',    'ნახვის შემდეგ მეტ არასდროს (localStorage)'],
    ];
    $curFreq = $p['show_frequency'] ?? 'once_session';
    foreach ($freqs as $val => [$name, $desc]):
    $sel = $curFreq === $val;
    ?>
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 10px;margin-bottom:5px;
                  border:1.5px solid <?= $sel ? 'var(--primary)' : 'var(--border)' ?>;border-radius:8px;
                  background:<?= $sel ? '#eff6ff' : '' ?>">
        <input type="radio" name="show_frequency" value="<?= $val ?>" <?= $sel ? 'checked' : '' ?>>
        <div>
            <div style="font-weight:600;font-size:13px"><?= $name ?></div>
            <div style="font-size:11.5px;color:var(--muted)"><?= $desc ?></div>
        </div>
    </label>
    <?php endforeach ?>

    <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:14px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:5px">📄 სამიზნე გვერდები (URL patterns)</label>
        <textarea name="target_pages" class="form-control" rows="3"
                  placeholder="/shop&#10;/products/*&#10;/promo-page"
                  style="font-size:12.5px;font-family:monospace;resize:vertical"><?= htmlspecialchars((string)($p['target_pages'] ?? ''), ENT_QUOTES) ?></textarea>
        <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
            ერთი URL თითო ხაზში. <code>*</code> wildcard-ია. ცარიელი = ყველა გვერდზე.
        </div>
    </div>
</div>
</div>

<!-- ── Style ──────────────────────────────────── -->
<div class="card" style="margin-bottom:16px">
<div class="card-body" style="padding:22px 24px">
    <div style="font-weight:700;font-size:14px;margin-bottom:18px;border-bottom:1px solid var(--border);padding-bottom:10px">🎨 სტილი</div>

    <!-- Width -->
    <div style="margin-bottom:16px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">სიგანე (px)</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach ([360=>'ვიწრო',420=>'საშ.',500=>'ფართო',600=>'ძ.ფართო'] as $w => $lbl):
            $sel = (int)($p['popup_width'] ?? 420) === $w;
            ?>
            <label style="cursor:pointer;padding:6px 14px;border:1.5px solid <?= $sel ? 'var(--primary)' : 'var(--border)' ?>;
                          border-radius:8px;font-size:12.5px;background:<?= $sel ? '#eff6ff' : '' ?>">
                <input type="radio" name="popup_width" value="<?= $w ?>" <?= $sel ? 'checked' : '' ?> style="display:none">
                <?= $lbl ?> (<?= $w ?>)
            </label>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Animation -->
    <div style="margin-bottom:16px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">ანიმაცია</label>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
            <?php
            $anims = ['slide'=>['↑','Slide Up'],'fade'=>['👁','Fade'],'zoom'=>['🔍','Zoom']];
            $curAnim = $p['animation'] ?? 'slide';
            foreach ($anims as $val => [$ico, $name]):
            $sel = $curAnim === $val;
            ?>
            <label style="cursor:pointer;text-align:center;padding:10px 6px;border:1.5px solid <?= $sel ? 'var(--primary)' : 'var(--border)' ?>;
                          border-radius:9px;background:<?= $sel ? '#eff6ff' : '' ?>">
                <input type="radio" name="animation" value="<?= $val ?>" <?= $sel ? 'checked' : '' ?> style="display:none">
                <div style="font-size:1.4rem"><?= $ico ?></div>
                <div style="font-size:12px;font-weight:600;margin-top:4px"><?= $name ?></div>
            </label>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Overlay opacity -->
    <div style="margin-bottom:16px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Overlay-ს გამჭვირვალობა</label>
        <div style="display:flex;align-items:center;gap:10px">
            <input type="range" name="overlay_opacity" min="0" max="90" step="5"
                   value="<?= (int)($p['overlay_opacity'] ?? 60) ?>"
                   oninput="document.getElementById('gcpOpacVal').textContent=this.value+'%'"
                   style="flex:1">
            <span id="gcpOpacVal" style="font-weight:700;color:#2563eb;min-width:36px"><?= (int)($p['overlay_opacity'] ?? 60) ?>%</span>
        </div>
    </div>

    <!-- Toggles -->
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border)">
            <div style="font-size:13px">× Overlay-ს click-ზე დახურვა</div>
            <label class="toggle-switch" style="flex-shrink:0">
                <input type="checkbox" name="close_on_overlay" value="1"
                       <?= (!$isEdit || (int)($p['close_on_overlay'] ?? 1)) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0">
            <div style="font-size:13px">✕ Close ღილაკი (მარჯვენა ზედა კუთხე)</div>
            <label class="toggle-switch" style="flex-shrink:0">
                <input type="checkbox" name="show_close_btn" value="1"
                       <?= (!$isEdit || (int)($p['show_close_btn'] ?? 1)) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>
</div>
</div>

<!-- Save button -->
<button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:15px">
    <?= $isEdit ? '💾 ცვლილების შენახვა' : '✚ პოპაპის შექმნა' ?>
</button>

</div><!-- /right column -->
</div><!-- grid -->
</form>

<script>
var gcpItemIdx = <?= count($its) ?>;

function gcpAddItem(icon) {
    icon = icon || '';
    var empty = document.getElementById('gcpItemsEmpty');
    if (empty) { empty.remove(); }

    var div = document.createElement('div');
    div.className = 'gcp-item-row';
    div.style.cssText = 'display:grid;grid-template-columns:56px 1fr 30px;gap:8px;align-items:center;margin-bottom:8px';
    div.innerHTML = '<input type="text" name="items['+gcpItemIdx+'][icon]" class="form-control" '
        + 'style="text-align:center;font-size:1.1rem;padding:6px 4px" value="'+icon+'" placeholder="🔥" maxlength="10">'
        + '<input type="text" name="items['+gcpItemIdx+'][text]" class="form-control" style="font-size:13px" '
        + 'placeholder="ელემენტის ტექსტი..." required>'
        + '<button type="button" onclick="this.closest(\'.gcp-item-row\').remove();gcpReindex()" '
        + 'style="width:30px;height:30px;border:none;background:none;color:#ef4444;cursor:pointer;font-size:16px;padding:0">×</button>';
    document.getElementById('gcpItems').appendChild(div);
    gcpItemIdx++;
    // Focus text input
    div.querySelectorAll('input')[1].focus();
}

function gcpReindex() {
    var rows = document.querySelectorAll('#gcpItems .gcp-item-row');
    rows.forEach(function(row, i) {
        row.querySelectorAll('input').forEach(function(inp) {
            inp.name = inp.name.replace(/items\[\d+\]/, 'items['+i+']');
        });
    });
    gcpItemIdx = rows.length;
}

function gcpTriggerChange(val) {
    document.getElementById('gcpDelayWrap').style.display  = val === 'load'   ? 'block' : 'none';
    document.getElementById('gcpScrollWrap').style.display = val === 'scroll' ? 'block' : 'none';
}

function gcpPreviewImg() {
    var src  = document.getElementById('gcpImageUrl').value.trim();
    var wrap = document.getElementById('gcpImgPreview');
    var img  = document.getElementById('gcpImgThumb');
    if (src) {
        img.src = src;
        wrap.style.display = 'flex';
    } else {
        wrap.style.display = 'none';
    }
}

function gcpSetImgBg(color) {
    document.querySelector('[name=image_bg_color]').value = color;
    document.getElementById('gcpImgBgTxt').value = color;
    document.getElementById('gcpImgPreview').style.background = color;
}

// Sync color picker ↔ text input for image_bg_color
document.querySelector('[name=image_bg_color]').addEventListener('input', function() {
    document.getElementById('gcpImgBgTxt').value = this.value;
    document.getElementById('gcpImgPreview').style.background = this.value;
});
</script>
