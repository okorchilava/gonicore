<?php
/**
 * Error Log Viewer
 * Variables: $user, $files (list<string>), $selected (string),
 *            $entries (list<array{time,level,message,trace}>), $level (string)
 */
$pageTitle = t('logs.title');
$activeNav = 'logs';

$levels = ['', 'error', 'warning', 'notice', 'deprecated', 'fatal'];

ob_start(); ?>
<?php if (!empty($files)): ?>
<form method="GET" action="<?= e($base) ?>/manage/logs" style="display:inline-flex;align-items:center;gap:8px">
    <select name="file" class="form-input" style="padding:5px 10px;font-size:13px;min-width:220px"
            onchange="this.form.submit()">
        <?php foreach ($files as $f): ?>
        <option value="<?= e($f) ?>" <?= $f === $selected ? 'selected' : '' ?>><?= e($f) ?></option>
        <?php endforeach ?>
    </select>
</form>
<?php if ($selected !== ''): ?>
<form method="POST" action="<?= e($base) ?>/manage/logs/clear" style="display:inline-flex;margin-left:8px">
    <input type="hidden" name="file" value="<?= e($selected) ?>">
    <button type="button" class="topbar-btn"
            onclick="gcConfirm(this,<?= e(json_encode(t('admin.are_you_sure'))) ?>,<?= e(json_encode(t('logs.clear') . ': ' . $selected)) ?>,<?= e(json_encode(t('logs.clear'))) ?>)">
        <span class="material-symbols-outlined mi-sm">delete</span> <?= e(t('logs.clear')) ?>
    </button>
</form>
<?php endif ?>
<form method="POST" action="<?= e($base) ?>/manage/logs/clear" style="display:inline-flex;margin-left:6px">
    <input type="hidden" name="file" value="*">
    <button type="button" class="topbar-btn" style="border-color:#ef4444;color:#ef4444"
            onclick="gcConfirm(this,<?= e(json_encode(t('admin.are_you_sure'))) ?>,<?= e(json_encode(t('logs.clear_all_confirm'))) ?>,<?= e(json_encode(t('logs.clear_all'))) ?>)">
        <span class="material-symbols-outlined mi-sm">delete_forever</span> <?= e(t('logs.clear_all')) ?>
    </button>
</form>
<?php endif ?>
<?php $topbarActions = ob_get_clean(); ?>

<style>
.log-filter{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px}
.log-chip{padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid var(--border);
    color:var(--muted);background:var(--surface);text-decoration:none;text-transform:capitalize}
.log-chip:hover{color:var(--text);text-decoration:none}
.log-chip.active{background:var(--accent);border-color:var(--accent);color:#fff}
.log-table{width:100%;border-collapse:collapse;font-size:13px}
.log-table th{background:var(--surface);padding:8px 12px;text-align:left;font-size:11px;font-weight:700;
    text-transform:uppercase;letter-spacing:.6px;color:var(--muted);border-bottom:1px solid var(--border)}
.log-table td{padding:8px 12px;border-bottom:1px solid var(--border);vertical-align:top}
.log-table tr:last-child td{border-bottom:none}
.log-table tr:hover td{background:var(--surface)}
.log-level{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase}
.log-level.error,.log-level.critical,.log-level.emergency,.log-level.fatal{background:#fee2e2;color:#dc2626}
.log-level.warning{background:#fef3c7;color:#d97706}
.log-level.notice,.log-level.info{background:#dbeafe;color:#2563eb}
.log-level.deprecated,.log-level.debug{background:#f3f4f6;color:#6b7280}
.log-time{font-family:'Fira Code',monospace;font-size:11.5px;color:var(--muted);white-space:nowrap}
.log-msg{line-height:1.5;word-break:break-word}
.log-trace{
    font-family:'Fira Code',monospace;font-size:11px;color:var(--muted);
    background:var(--bg);border-radius:var(--radius);padding:8px 10px;
    margin-top:6px;white-space:pre-wrap;word-break:break-all;display:none
}
.trace-toggle{display:inline-flex;align-items:center;gap:2px;font-size:11px;color:var(--accent);cursor:pointer;
    border:none;background:none;padding:2px 6px;border-radius:4px;margin-top:4px}
.trace-toggle:hover{background:var(--surface)}
.trace-toggle .material-symbols-outlined{font-size:15px}
.log-empty{text-align:center;padding:48px;color:var(--muted)}
.log-empty .material-symbols-outlined{font-size:48px;margin-bottom:8px;opacity:.7}
</style>

<?php if (empty($files)): ?>
<div class="card">
  <div class="card-body">
    <div class="log-empty">
      <span class="material-symbols-outlined">inbox</span>
      <p><?= e(t('logs.no_logs')) ?></p>
    </div>
  </div>
</div>
<?php else: ?>

<div class="log-filter">
  <?php foreach ($levels as $lv): ?>
  <a href="<?= e($base) ?>/manage/logs?file=<?= e(urlencode($selected)) ?>&amp;level=<?= e(urlencode($lv)) ?>"
     class="log-chip <?= $level === $lv ? 'active' : '' ?>">
     <?= $lv === '' ? e(t('logs.all_levels')) : e($lv) ?>
  </a>
  <?php endforeach ?>
</div>

<?php if (empty($entries)): ?>
<div class="card">
  <div class="card-body">
    <div class="log-empty">
      <span class="material-symbols-outlined">check_circle</span>
      <p><?= e(t('logs.no_entries')) ?></p>
    </div>
  </div>
</div>
<?php else: ?>

<div class="card" style="overflow:hidden">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
    <h3><?= e($selected) ?></h3>
    <span style="font-size:13px;color:var(--muted)"><?= count($entries) ?> <?= e(t('logs.entries')) ?></span>
  </div>
  <div class="table-wrap" style="max-height:75vh;overflow-y:auto">
    <table class="log-table">
      <thead>
        <tr>
          <th style="width:160px"><?= e(t('logs.time')) ?></th>
          <th style="width:90px"><?= e(t('logs.level')) ?></th>
          <th><?= e(t('logs.message')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $entry): ?>
        <tr>
          <td class="log-time"><?= e($entry['time']) ?></td>
          <td><span class="log-level <?= e($entry['level']) ?>"><?= e($entry['level']) ?></span></td>
          <td>
            <div class="log-msg"><?= e($entry['message']) ?></div>
            <?php if (!empty($entry['trace'])): ?>
            <button class="trace-toggle" type="button" onclick="toggleTrace(this)">
              <span class="material-symbols-outlined">chevron_right</span> <?= e(t('logs.trace')) ?>
            </button>
            <pre class="log-trace"><?= e($entry['trace']) ?></pre>
            <?php endif ?>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleTrace(btn){
  var pre=btn.nextElementSibling;
  var open=pre.style.display==='block';
  pre.style.display=open?'none':'block';
  var ic=btn.querySelector('.material-symbols-outlined');
  if(ic) ic.textContent=open?'chevron_right':'expand_more';
}
</script>

<?php endif ?>
<?php endif ?>
