<?php
$confirmed   = in_array($appt['status'], ['confirmed','completed'], true);
$statusColor = ['pending'=>'#f59e0b','confirmed'=>'#10b981','cancelled'=>'#ef4444','completed'=>'#4f46e5','no_show'=>'#94a3b8'][$appt['status']] ?? '#94a3b8';
?>
<style>
.ga-c-wrap{max-width:640px;margin:56px auto;padding:0 20px}
.ga-c-hero{text-align:center;padding:40px 24px;background:linear-gradient(135deg,#0f172a,#1e1b4b);border-radius:16px;color:#fff;margin-bottom:24px;position:relative;overflow:hidden}
.ga-c-num{font-family:monospace;font-size:26px;font-weight:900;letter-spacing:2px;color:#34d399;margin:16px 0 4px}
.ga-c-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ga-c-head{padding:13px 18px;background:var(--surface);border-bottom:1px solid var(--border);font-weight:700;font-size:14px}
.ga-c-row{display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid var(--border);font-size:13.5px}
.ga-c-row:last-child{border-bottom:none}
.ga-c-row span:first-child{color:var(--muted)}
.ga-c-row span:last-child{font-weight:600}
.ga-c-actions{display:flex;gap:10px;justify-content:center;margin-top:24px;flex-wrap:wrap}
.ga-c-pending{background:#fef3c7;border:1.5px solid #f59e0b;border-radius:10px;padding:14px 18px;font-size:13.5px;color:#92400e;margin-bottom:16px;display:flex;gap:10px;align-items:start}
</style>

<div class="ga-c-wrap">

<?php if (!$confirmed): ?>
<div class="ga-c-pending">
    <span style="font-size:22px;line-height:1">⏳</span>
    <div><strong>Booking pending.</strong> Your appointment is reserved. We'll confirm it shortly — check your email for updates.</div>
</div>
<?php endif ?>

<div class="ga-c-hero">
    <svg style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:.04" xmlns="http://www.w3.org/2000/svg">
        <pattern id="gp2" width="60" height="60" patternUnits="userSpaceOnUse">
            <path d="M60 0L0 60M30 0L0 30M60 30L30 60" stroke="#fff" stroke-width=".5"/>
        </pattern>
        <rect width="100%" height="100%" fill="url(#gp2)"/>
    </svg>
    <div style="font-size:52px;margin-bottom:12px"><?= $confirmed ? '🎉' : '📅' ?></div>
    <?php if ($confirmed): ?>
    <h1 style="font-size:26px;font-weight:900;margin-bottom:6px">You're all set!</h1>
    <p style="color:#94a3b8;font-size:14px">Your appointment is confirmed. See you soon!</p>
    <?php else: ?>
    <h1 style="font-size:26px;font-weight:900;margin-bottom:6px">Booking Received</h1>
    <p style="color:#94a3b8;font-size:14px">We'll confirm your appointment shortly.</p>
    <?php endif ?>
    <div class="ga-c-num"><?= e($appt['appointment_number']) ?></div>
    <p style="font-size:11px;opacity:.5;margin-top:2px">Save this number for your records</p>
</div>

<div class="ga-c-card">
    <div class="ga-c-head">📋 Appointment Details</div>
    <div class="ga-c-row"><span>Service</span><span><?= e($appt['service']['name'] ?? '—') ?></span></div>
    <div class="ga-c-row"><span>Staff</span><span><?= e($appt['staff']['name'] ?? '—') ?></span></div>
    <div class="ga-c-row"><span>Date</span><span><?= e($appt['appointment_date']) ?></span></div>
    <div class="ga-c-row"><span>Time</span><span><?= e(substr((string)$appt['start_time'],0,5)) ?> – <?= e(substr((string)$appt['end_time'],0,5)) ?></span></div>
    <div class="ga-c-row"><span>Price</span><span><?= e($svc->formatPrice((float)$appt['price'])) ?></span></div>
    <div class="ga-c-row"><span>Status</span>
        <span style="color:<?= $statusColor ?>;font-weight:700"><?= e($svc->statusLabel($appt['status'])) ?></span>
    </div>
</div>

<div class="ga-c-card">
    <div class="ga-c-head">👤 Contact Details</div>
    <div class="ga-c-row"><span>Name</span><span><?= e($appt['customer_name']) ?></span></div>
    <div class="ga-c-row"><span>Email</span><span><?= e($appt['customer_email']) ?></span></div>
    <?php if ($appt['customer_phone']): ?>
    <div class="ga-c-row"><span>Phone</span><span><?= e($appt['customer_phone']) ?></span></div>
    <?php endif ?>
    <?php if ($appt['customer_note']): ?>
    <div class="ga-c-row" style="align-items:start">
        <span>Note</span>
        <span style="max-width:320px;text-align:right;font-weight:400;color:var(--muted)"><?= nl2br(e((string)$appt['customer_note'])) ?></span>
    </div>
    <?php endif ?>
</div>

<div class="ga-c-actions">
    <a href="<?= e($base) ?>/<?= e($bookSlug) ?>" class="btn" style="background:var(--surface);color:var(--text);border:1px solid var(--border)">← Book Another</a>
    <button onclick="window.print()" class="btn" style="background:var(--surface);color:var(--text);border:1px solid var(--border)">🖨 Print</button>
</div>

</div>
