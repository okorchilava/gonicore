<?php
$pageTitle     = 'GCCounter — Counter Groups';
$activeNav     = 'gccounter';
$topbarActions = '<a href="' . e($base) . '/manage/gccounter/form" class="btn btn-primary" style="font-size:13px">+ ახალი Counter Group</a>';
?>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ Counter Group შენახულია.</div>
<?php endif ?>
<?php if ($deleted): ?>
<div class="alert alert-success" style="margin-bottom:14px">🗑 Counter Group წაიშალა.</div>
<?php endif ?>

<?php if (empty($groups)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:50px 20px;color:var(--muted)">
        <div style="font-size:44px;margin-bottom:14px">🔢</div>
        <div style="font-size:16px;font-weight:700;margin-bottom:6px">Counter Group-ი ჯერ არ არის</div>
        <div style="font-size:13.5px;margin-bottom:22px">შექმენი პირველი — შეიყვანე რიცხვები, სახელები, ფერები.</div>
        <a href="<?= e($base) ?>/manage/gccounter/form" class="btn btn-primary">+ ახალი Counter Group</a>
    </div>
</div>
<?php else: ?>

<div style="display:flex;flex-direction:column;gap:14px">
<?php foreach ($groups as $g):
    $itemCount = (int)($g['item_count'] ?? 0);
    $cols      = (int)($g['columns']    ?? 4);
    $dur       = (int)($g['duration_ms'] ?? 2000);
    $slug      = (string)($g['slug'] ?? '');
?>
<div class="card">
    <div class="card-body" style="padding:18px 22px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">

            <div style="display:flex;align-items:center;gap:14px">
                <div style="width:44px;height:44px;background:linear-gradient(135deg,#7c3aed,#6366f1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;flex-shrink:0">🔢</div>
                <div>
                    <div style="font-weight:700;font-size:15px"><?= e((string)$g['name']) ?></div>
                    <div style="font-size:12.5px;color:var(--muted);margin-top:3px;display:flex;gap:12px;flex-wrap:wrap">
                        <span>📋 <?= $itemCount ?> counter</span>
                        <span>⊞ <?= $cols ?> col.</span>
                        <span>⏱ <?= $dur ?>ms</span>
                        <span style="font-family:monospace;background:var(--border);padding:1px 7px;border-radius:4px;font-size:11.5px">
                            id="<?= (int)$g['id'] ?>"
                        </span>
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <!-- Shortcode copy -->
                <button class="btn btn-ghost" style="font-size:12px;font-family:monospace"
                        onclick="gccCopy(this,'[gccounter id=&quot;<?= (int)$g['id'] ?>&quot;]')"
                        title="Shortcode-ის კოპირება">
                    [gccounter id="<?= (int)$g['id'] ?>"] 📋
                </button>
                <a href="<?= e($base) ?>/manage/gccounter/form?id=<?= (int)$g['id'] ?>"
                   class="btn btn-ghost" style="font-size:13px">✏ რედ.</a>
                <form method="POST" action="<?= e($base) ?>/manage/gccounter/delete"
                      style="margin:0" onsubmit="return confirm('<?= e((string)$g['name']) ?> — წაიშლება?')">
                    <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                    <button type="submit" class="btn btn-ghost" style="font-size:13px;color:#ef4444">🗑</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach ?>
</div>

<div style="margin-top:16px;font-size:12.5px;color:var(--muted)">
    სულ <?= count($groups) ?> group. გამოყენება თემაში: <code>gccounter(1)</code> ან shortcode: <code>[gccounter id="1"]</code>
</div>
<?php endif ?>

<script>
function gccCopy(btn, text) {
    var decoded = text.replace(/&quot;/g, '"');
    navigator.clipboard ? navigator.clipboard.writeText(decoded) : (function(){var t=document.createElement('textarea');t.value=decoded;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);})();
    var orig = btn.innerHTML;
    btn.innerHTML = '✅ Copied!';
    setTimeout(function(){ btn.innerHTML = orig; }, 1600);
}
</script>
