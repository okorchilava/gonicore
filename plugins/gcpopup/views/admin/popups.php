<?php
$pageTitle     = 'GCPopup — პოპაპები';
$activeNav     = 'gcpopup';
$topbarActions = '<a href="' . e($base) . '/manage/gcpopup/form" class="btn btn-primary" style="font-size:13px">+ ახალი პოპაპი</a>';
?>

<?php if ($saved):   ?><div class="alert alert-success" style="margin-bottom:14px">✓ პოპაპი შენახულია.</div><?php endif ?>
<?php if ($deleted): ?><div class="alert alert-success" style="margin-bottom:14px">🗑 პოპაპი წაიშალა.</div><?php endif ?>

<?php if (empty($popups)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:64px 20px;color:var(--muted)">
        <div style="font-size:52px;margin-bottom:14px">🪟</div>
        <div style="font-size:17px;font-weight:700;margin-bottom:6px">პოპაპი ჯერ არ შექმნილა</div>
        <div style="font-size:13.5px;margin-bottom:24px">შექმენი პირველი popup — სარეკლამო, ავტორიზაციის, შეთავაზების და სხვა.</div>
        <a href="<?= e($base) ?>/manage/gcpopup/form" class="btn btn-primary">+ ახალი პოპაპი</a>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px">
<?php
$triggerLabels = ['load'=>'⏱ გვერდის ჩატვირთვა','scroll'=>'📜 გადახვევა','exit'=>'🚪 Exit Intent','manual'=>'🖱 Manual'];
$freqLabels    = ['always'=>'ყოველ ჯერ','once_session'=>'სესია','once_day'=>'1×/დღე','once_ever'=>'1×/სულ'];
$animLabels    = ['slide'=>'Slide Up','fade'=>'Fade','zoom'=>'Zoom'];

foreach ($popups as $p):
    $id     = (int)$p['id'];
    $active = (bool)(int)$p['active'];
?>
<div class="card" style="border-top:3px solid <?= $active ? '#10b981' : '#9ca3af' ?>">
    <div class="card-body" style="padding:18px 20px">

        <!-- Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:15px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= e((string)$p['name']) ?>
                </div>
                <?php if ($p['title']): ?>
                <div style="font-size:12px;color:var(--muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= e((string)$p['title']) ?>
                </div>
                <?php endif ?>
            </div>
            <span style="flex-shrink:0;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;margin-left:8px;
                         background:<?= $active ? '#d1fae5' : '#f1f5f9' ?>;
                         color:<?= $active ? '#065f46' : '#64748b' ?>">
                <?= $active ? '● ჩართული' : '○ გამოთიშული' ?>
            </span>
        </div>

        <!-- Preview strip -->
        <div style="background:var(--bg-subtle,#f8fafc);border-radius:10px;padding:12px 14px;margin-bottom:12px">
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <!-- Trigger -->
                <span style="font-size:11.5px;background:#eff6ff;color:#1d4ed8;padding:3px 8px;border-radius:6px;font-weight:600">
                    <?= $triggerLabels[$p['trigger_type']] ?? $p['trigger_type'] ?>
                    <?php if ($p['trigger_type'] === 'load' && (int)$p['trigger_delay'] > 0): ?>
                    &nbsp;(<?= (int)$p['trigger_delay'] ?>წ)
                    <?php elseif ($p['trigger_type'] === 'scroll'): ?>
                    &nbsp;(<?= (int)$p['trigger_scroll'] ?>%)
                    <?php endif ?>
                </span>
                <!-- Frequency -->
                <span style="font-size:11.5px;background:#f0fdf4;color:#15803d;padding:3px 8px;border-radius:6px;font-weight:600">
                    🔁 <?= $freqLabels[$p['show_frequency']] ?? $p['show_frequency'] ?>
                </span>
                <!-- Animation -->
                <span style="font-size:11.5px;background:#faf5ff;color:#7c3aed;padding:3px 8px;border-radius:6px;font-weight:600">
                    ✨ <?= $animLabels[$p['animation']] ?? $p['animation'] ?>
                </span>
                <!-- Width -->
                <span style="font-size:11.5px;background:#fff7ed;color:#c2410c;padding:3px 8px;border-radius:6px;font-weight:600">
                    ↔ <?= (int)$p['popup_width'] ?>px
                </span>
            </div>

            <?php if ($p['btn_text']): ?>
            <div style="margin-top:9px">
                <span style="display:inline-block;padding:5px 14px;border-radius:8px;font-size:12px;font-weight:700;
                             background:<?= htmlspecialchars((string)$p['btn_color'],ENT_QUOTES) ?>;
                             color:<?= htmlspecialchars((string)$p['btn_text_color'],ENT_QUOTES) ?>">
                    <?= e((string)$p['btn_text']) ?>
                </span>
            </div>
            <?php endif ?>

            <?php if ($p['target_pages']): ?>
            <div style="margin-top:7px;font-size:11px;color:var(--muted)">
                📄 მხოლოდ: <code style="font-size:11px"><?= e(str_replace("\n", ', ', trim((string)$p['target_pages']))) ?></code>
            </div>
            <?php endif ?>
        </div>

        <!-- Shortcode -->
        <div style="margin-bottom:10px">
            <button class="btn btn-ghost" style="font-size:11.5px;font-family:monospace;width:100%"
                    onclick="gcpCopy(this,'[gcpopup id=&quot;<?= $id ?>&quot;]')"
                    title="Shortcode-ის კოპირება">
                [gcpopup id="<?= $id ?>"] 📋
            </button>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:6px">
            <a href="<?= e($base) ?>/manage/gcpopup/form?id=<?= $id ?>"
               class="btn btn-ghost" style="font-size:12px;flex:1">✏ რედ.</a>

            <form method="POST" action="<?= e($base) ?>/manage/gcpopup/toggle" style="margin:0;flex:1">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-ghost" style="font-size:12px;width:100%;color:<?= $active ? '#f59e0b' : '#10b981' ?>">
                    <?= $active ? '⏸ გამ.' : '▶ ჩართვა' ?>
                </button>
            </form>

            <form method="POST" action="<?= e($base) ?>/manage/gcpopup/delete" style="margin:0"
                  onsubmit="return confirm('<?= e((string)$p['name']) ?> — წაიშლება?')">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-ghost" style="font-size:12px;color:#ef4444">🗑</button>
            </form>
        </div>

    </div>
</div>
<?php endforeach ?>
</div>

<div style="margin-top:16px;font-size:12.5px;color:var(--muted)">
    Shortcode: <code>[gcpopup id="1"]</code> &nbsp;·&nbsp;
    PHP: <code>gcpopup(1)</code> / <code>gcpopup(1, 'ღილაკის ტექსტი')</code>
</div>

<?php endif ?>

<script>
function gcpCopy(btn, text) {
    var decoded = text.replace(/&quot;/g, '"');
    navigator.clipboard
        ? navigator.clipboard.writeText(decoded)
        : (function(){var t=document.createElement('textarea');t.value=decoded;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);})();
    var orig = btn.innerHTML;
    btn.innerHTML = '✅ Copied!';
    setTimeout(function(){ btn.innerHTML = orig; }, 1600);
}
</script>
