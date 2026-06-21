<?php
$pageTitle     = 'GoniAppointment';
$activeNav     = 'appointment';
$topbarActions = '<a href="' . e($base) . '/manage/appointment/appointments" class="btn btn-primary" style="font-size:13px">All Appointments</a>';
?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <div class="card"><div class="card-body" style="text-align:center;padding:24px">
        <div style="font-size:32px;font-weight:900;color:var(--accent)"><?= number_format($stats['total']) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">Total Appointments</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:24px">
        <div style="font-size:32px;font-weight:900;color:#f59e0b"><?= number_format($stats['today']) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">Today</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:24px">
        <div style="font-size:32px;font-weight:900;color:#ef4444"><?= number_format($stats['pending']) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">Pending</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:24px">
        <div style="font-size:32px;font-weight:900;color:#10b981"><?= number_format($stats['revenue'], 2) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">Revenue</div>
    </div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <a href="<?= e($base) ?>/manage/appointment/services" class="card" style="text-decoration:none;display:flex;align-items:center;gap:16px;padding:20px">
        <span style="font-size:32px">💆</span>
        <div>
            <div style="font-weight:700;font-size:15px">Services</div>
            <div style="font-size:12px;color:var(--muted)">Manage your service catalog</div>
        </div>
    </a>
    <a href="<?= e($base) ?>/manage/appointment/staff" class="card" style="text-decoration:none;display:flex;align-items:center;gap:16px;padding:20px">
        <span style="font-size:32px">👤</span>
        <div>
            <div style="font-weight:700;font-size:15px">Staff</div>
            <div style="font-size:12px;color:var(--muted)">Manage staff and schedules</div>
        </div>
    </a>
</div>

<div class="card">
    <div class="card-header"><h3>Recent Appointments</h3></div>
    <?php if (empty($recent)): ?>
    <div class="empty">
        <div class="empty-icon">📅</div>
        <h3>No appointments yet</h3>
        <p style="color:var(--muted);margin-top:8px">Set up services and staff to start accepting bookings.</p>
    </div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>#</th><th>Customer</th><th>Service</th><th>Staff</th><th>Date & Time</th><th>Status</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($recent as $a): ?>
    <tr>
        <td style="font-family:monospace;font-size:12px"><?= e($a['appointment_number']) ?></td>
        <td>
            <div style="font-weight:600"><?= e($a['customer_name']) ?></div>
            <div style="font-size:12px;color:var(--muted)"><?= e($a['customer_email']) ?></div>
        </td>
        <td style="font-size:13px"><?= e($a['service']['name'] ?? '—') ?></td>
        <td style="font-size:13px"><?= e($a['staff']['name'] ?? '—') ?></td>
        <td style="white-space:nowrap;font-size:13px">
            <?= e($a['appointment_date']) ?><br>
            <span style="color:var(--muted)"><?= e(substr((string)$a['start_time'],0,5)) ?></span>
        </td>
        <td><?php
            $c = ['pending'=>'#f59e0b','confirmed'=>'#10b981','cancelled'=>'#ef4444','completed'=>'#4f46e5','no_show'=>'#94a3b8'][$a['status']] ?? '#94a3b8';
        ?><span style="background:<?= $c ?>22;color:<?= $c ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase"><?= e($a['status']) ?></span></td>
        <td><a href="<?= e($base) ?>/manage/appointment/appointments/<?= (int)$a['id'] ?>" class="btn btn-ghost" style="font-size:12px">View</a></td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>
