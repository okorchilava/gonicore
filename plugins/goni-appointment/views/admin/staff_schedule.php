<?php
$pageTitle     = 'Schedule: ' . $member['name'];
$activeNav     = 'appointment';
$topbarActions = '<a href="' . e($base) . '/manage/appointment/staff/' . (int)$member['id'] . '/edit" class="btn btn-ghost" style="font-size:13px">← Back to Staff</a>';
?>
<?php if ($saved): ?>
<div id="gc-flash" data-msg="Schedule saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<div class="card" style="max-width:700px">
    <div class="card-header">
        <div>
            <h3>Weekly Schedule</h3>
            <div style="font-size:13px;color:var(--muted);margin-top:2px"><?= e($member['name']) ?></div>
        </div>
    </div>
    <form method="POST" action="<?= e($base) ?>/manage/appointment/staff/<?= (int)$member['id'] ?>/schedule">
    <div class="card-body">
        <div style="display:grid;grid-template-columns:110px 1fr 1fr 110px;gap:8px;padding:8px 12px;background:var(--surface);border-radius:8px;font-size:11.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px">
            <div>Day</div><div>Opens at</div><div>Closes at</div><div style="text-align:center">Day Off</div>
        </div>
        <?php for ($d = 0; $d < 7; $d++):
            $h     = $hours[$d] ?? [];
            $start = isset($h['start_time']) ? substr((string)$h['start_time'], 0, 5) : '09:00';
            $end   = isset($h['end_time'])   ? substr((string)$h['end_time'], 0, 5)   : '18:00';
            $off   = !empty($h['is_day_off']);
        ?>
        <div style="display:grid;grid-template-columns:110px 1fr 1fr 110px;gap:8px;align-items:center;padding:10px 12px;border-radius:8px;border:1px solid var(--border);margin-bottom:6px;transition:opacity .2s;<?= $off ? 'opacity:.5' : '' ?>" id="row<?= $d ?>">
            <div style="font-weight:700;font-size:14px"><?= e($svc->dayName($d)) ?></div>
            <div>
                <input type="time" name="hours[<?= $d ?>][start]" class="form-input" value="<?= e($start) ?>"
                       style="padding:6px 10px;font-size:13px" <?= $off ? 'disabled' : '' ?>>
            </div>
            <div>
                <input type="time" name="hours[<?= $d ?>][end]" class="form-input" value="<?= e($end) ?>"
                       style="padding:6px 10px;font-size:13px" <?= $off ? 'disabled' : '' ?>>
            </div>
            <div style="text-align:center">
                <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;font-weight:500">
                    <input type="checkbox" name="hours[<?= $d ?>][day_off]" value="1"
                           <?= $off ? 'checked' : '' ?>
                           onchange="toggleDay(this,<?= $d ?>)"
                           style="width:16px;height:16px">
                    Off
                </label>
            </div>
        </div>
        <?php endfor ?>
    </div>
    <div style="padding:16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px">
        <a href="<?= e($base) ?>/manage/appointment/staff/<?= (int)$member['id'] ?>/edit" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Schedule</button>
    </div>
    </form>
</div>

<script>
function toggleDay(cb, d) {
    var row    = document.getElementById('row' + d);
    var inputs = row.querySelectorAll('input[type=time]');
    inputs.forEach(function(i){ i.disabled = cb.checked; });
    row.style.opacity = cb.checked ? '.5' : '';
}
</script>
