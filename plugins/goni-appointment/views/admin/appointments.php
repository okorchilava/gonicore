<?php
$pageTitle     = 'Appointments';
$activeNav     = 'appointment';
$topbarActions = '';
?>
<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:10px">
        <h3>Appointments <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= number_format($total) ?>)</span></h3>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
            <input type="date" name="date" value="<?= e($date) ?>" class="form-input" style="padding:6px 10px;font-size:13px">
            <select name="status" class="form-input" style="padding:6px 10px;font-size:13px">
                <option value="">All Statuses</option>
                <?php foreach ($svc->allStatuses() as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $st)) ?></option>
                <?php endforeach ?>
            </select>
            <select name="staff" class="form-input" style="padding:6px 10px;font-size:13px">
                <option value="">All Staff</option>
                <?php foreach ($staffList as $sm): ?>
                <option value="<?= (int)$sm['id'] ?>" <?= $staffId === (int)$sm['id'] ? 'selected' : '' ?>><?= e($sm['name']) ?></option>
                <?php endforeach ?>
            </select>
            <button type="submit" class="btn btn-ghost" style="font-size:13px">Filter</button>
            <?php if ($date || $status || $staffId): ?>
            <a href="<?= e($base) ?>/manage/appointment/appointments" class="btn btn-ghost" style="font-size:13px">Clear</a>
            <?php endif ?>
        </form>
    </div>
    <?php if (empty($items)): ?>
    <div class="empty"><div class="empty-icon">📅</div><h3>No appointments found</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>#</th><th>Customer</th><th>Service</th><th>Staff</th><th>Date & Time</th><th>Price</th><th>Status</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($items as $a): ?>
    <tr>
        <td style="font-family:monospace;font-size:12px"><?= e($a['appointment_number']) ?></td>
        <td>
            <div style="font-weight:600"><?= e($a['customer_name']) ?></div>
            <div style="font-size:12px;color:var(--muted)"><?= e($a['customer_phone'] ?: $a['customer_email']) ?></div>
        </td>
        <td style="font-size:13px"><?= e($a['service']['name'] ?? '—') ?></td>
        <td style="font-size:13px"><?= e($a['staff']['name'] ?? '—') ?></td>
        <td style="white-space:nowrap;font-size:13px">
            <?= e($a['appointment_date']) ?><br>
            <span style="color:var(--muted)"><?= e(substr((string)$a['start_time'], 0, 5)) ?>–<?= e(substr((string)$a['end_time'], 0, 5)) ?></span>
        </td>
        <td style="font-size:13px"><?= e($svc->formatPrice((float)$a['price'])) ?></td>
        <td><?php $c = $svc->statusColor($a['status']); ?>
            <span style="background:<?= $c ?>22;color:<?= $c ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase"><?= e($a['status']) ?></span>
        </td>
        <td><a href="<?= e($base) ?>/manage/appointment/appointments/<?= (int)$a['id'] ?>" class="btn btn-ghost" style="font-size:12px">View</a></td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
        <?php
        $qStr = ($date ? '&date='.urlencode($date) : '') . ($status ? '&status='.urlencode($status) : '') . ($staffId ? '&staff='.$staffId : '');
        for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?><?= $qStr ?>"
           style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;text-decoration:none;<?= $i===$page?'background:var(--accent);color:#fff;border-color:var(--accent)':'' ?>">
            <?= $i ?>
        </a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
