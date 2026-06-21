<?php
$pageTitle     = 'Appointment ' . $appt['appointment_number'];
$activeNav     = 'appointment';
$topbarActions = '<a href="' . e($base) . '/manage/appointment/appointments" class="btn btn-ghost" style="font-size:13px">← All Appointments</a>';
?>
<?php if ($flash): ?>
<div id="gc-flash" data-msg="<?= e($flash) ?>" data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if ($error): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

<div style="display:flex;flex-direction:column;gap:16px">

    <div class="card">
        <div class="card-header" style="justify-content:space-between">
            <h3>Appointment Details</h3>
            <?php $c = $svc->statusColor($appt['status']); ?>
            <span style="background:<?= $c ?>22;color:<?= $c ?>;font-size:12px;font-weight:700;padding:3px 12px;border-radius:20px;text-transform:uppercase"><?= e($appt['status']) ?></span>
        </div>
        <div class="card-body">
            <table style="width:100%;font-size:14px;border-collapse:collapse">
                <?php
                $rows = [
                    'Number'       => '<span style="font-family:monospace;font-weight:700">'.e($appt['appointment_number']).'</span>',
                    'Service'      => e($appt['service']['name'] ?? '—'),
                    'Staff'        => e($appt['staff']['name'] ?? '—'),
                    'Date'         => e($appt['appointment_date']),
                    'Time'         => e(substr((string)$appt['start_time'],0,5)).' – '.e(substr((string)$appt['end_time'],0,5)),
                    'Duration'     => e((string)(isset($appt['service']['duration_minutes']) ? (int)$appt['service']['duration_minutes'].' min' : '—')),
                    'Price'        => e($svc->formatPrice((float)$appt['price'])),
                    'Payment'      => '<span style="color:'.($appt['payment_status']==='paid'?'#10b981':'#f59e0b').'">'.e(ucfirst($appt['payment_status'])).'</span>',
                    'Payment Method' => e(ucfirst(str_replace('_', ' ', (string)$appt['payment_method']))),
                ];
                foreach ($rows as $label => $value): ?>
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:10px 0;color:var(--muted);width:130px;font-size:13px"><?= $label ?></td>
                    <td style="padding:10px 0;font-weight:600"><?= $value ?></td>
                </tr>
                <?php endforeach ?>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Customer Information</h3></div>
        <div class="card-body">
            <table style="width:100%;font-size:14px;border-collapse:collapse">
                <?php
                $crows = [
                    'Name'  => e($appt['customer_name']),
                    'Email' => '<a href="mailto:'.e($appt['customer_email']).'">'.e($appt['customer_email']).'</a>',
                    'Phone' => $appt['customer_phone'] ? e($appt['customer_phone']) : '<span style="color:var(--muted)">—</span>',
                ];
                foreach ($crows as $l => $v): ?>
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:10px 0;color:var(--muted);width:80px;font-size:13px"><?= $l ?></td>
                    <td style="padding:10px 0"><?= $v ?></td>
                </tr>
                <?php endforeach ?>
            </table>
            <?php if ($appt['customer_note']): ?>
            <div style="margin-top:14px;padding:12px 14px;background:var(--surface);border-radius:8px;font-size:13px;border:1px solid var(--border)">
                <div style="font-weight:700;margin-bottom:4px;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Customer Note</div>
                <?= nl2br(e((string)$appt['customer_note'])) ?>
            </div>
            <?php endif ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Admin Note</h3></div>
        <form method="POST" action="<?= e($base) ?>/manage/appointment/appointments/<?= (int)$appt['id'] ?>/note">
        <div class="card-body">
            <textarea name="admin_note" class="form-input" rows="4"
                      placeholder="Internal notes — not visible to the customer…"><?= e((string)$appt['admin_note']) ?></textarea>
        </div>
        <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
            <button type="submit" class="btn btn-primary" style="font-size:13px">Save Note</button>
        </div>
        </form>
    </div>

</div>

<div>
    <div class="card">
        <div class="card-header"><h3>Update Status</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($svc->allStatuses() as $st):
                $sc        = $svc->statusColor($st);
                $isCurrent = $appt['status'] === $st;
            ?>
            <form method="POST" action="<?= e($base) ?>/manage/appointment/appointments/<?= (int)$appt['id'] ?>/status">
                <input type="hidden" name="status" value="<?= e($st) ?>">
                <button type="submit" class="btn" style="width:100%;font-size:13px;text-align:left;<?= $isCurrent ? "background:{$sc};color:#fff;border-color:{$sc}" : 'background:var(--surface);border:1px solid var(--border)' ?>">
                    <?= e($svc->statusLabel($st)) ?><?= $isCurrent ? ' ✓' : '' ?>
                </button>
            </form>
            <?php endforeach ?>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header"><h3>Meta</h3></div>
        <div class="card-body" style="font-size:12.5px;color:var(--muted);display:flex;flex-direction:column;gap:6px">
            <div>Created: <?= e($appt['created_at'] ?? '—') ?></div>
            <div>Updated: <?= e($appt['updated_at'] ?? '—') ?></div>
            <?php if ($appt['ip_address']): ?>
            <div>IP: <?= e($appt['ip_address']) ?></div>
            <?php endif ?>
        </div>
    </div>
</div>

</div>
