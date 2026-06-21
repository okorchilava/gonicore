<?php
$pageTitle     = 'Booking ' . e($booking['booking_number']);
$activeNav     = 'tickets-bookings';
$topbarActions = '';

$statusColors = [
    'pending'   => ['#f59e0b','#fef3c7'],
    'confirmed' => ['#10b981','#d1fae5'],
    'cancelled' => ['#ef4444','#fef2f2'],
    'refunded'  => ['#8b5cf6','#ede9fe'],
];
[$sc, $sbg] = $statusColors[$booking['status']] ?? ['#94a3b8','#f1f5f9'];
?>
<?php if (!empty($flash)): ?>
<div id="gc-flash" data-msg="<?= e($flash) ?>" data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($error)): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;max-width:1000px">

<div style="display:flex;flex-direction:column;gap:16px">
    <!-- Booking header -->
    <div class="card">
        <div class="card-header" style="justify-content:space-between">
            <div>
                <span style="font-family:monospace;font-size:20px;font-weight:900"><?= e($booking['booking_number']) ?></span>
                <span style="background:<?= $sbg ?>;color:<?= $sc ?>;font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;margin-left:10px;text-transform:uppercase"><?= e($booking['status']) ?></span>
            </div>
            <div style="font-size:12.5px;color:var(--muted)"><?= date('d M Y, H:i', strtotime($booking['created_at'])) ?></div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13.5px">
            <div><span style="color:var(--muted)">Event</span><br><strong><?= e($booking['event']['title'] ?? '—') ?></strong></div>
            <div><span style="color:var(--muted)">Date</span><br><strong><?= isset($booking['event']['event_date']) ? $tickets->formatDate($booking['event']['event_date']) : '—' ?></strong></div>
            <div><span style="color:var(--muted)">Payment</span><br><strong><?= e($booking['payment_method']) ?></strong>
                <span style="color:<?= $booking['payment_status']==='paid'?'#10b981':'#f59e0b' ?>;font-size:11.5px;margin-left:6px"><?= e($booking['payment_status']) ?></span>
            </div>
            <div><span style="color:var(--muted)">Transaction ID</span><br><code style="font-size:11.5px"><?= e($booking['transaction_id'] ?? '—') ?></code></div>
        </div>
    </div>

    <!-- Tickets -->
    <div class="card">
        <div class="card-header"><h3>Tickets</h3></div>
        <table class="data-table"><thead><tr>
            <th>Ticket Type</th><th>Qty</th><th>Unit Price</th><th>Total</th>
        </tr></thead><tbody>
        <?php foreach ($booking['tickets'] as $t): ?>
        <tr>
            <td><?= e($t['ticket_type_name']) ?></td>
            <td><?= (int)$t['quantity'] ?></td>
            <td><?= $tickets->formatPrice((float)$t['unit_price']) ?></td>
            <td style="font-weight:700"><?= $tickets->formatPrice((float)$t['total']) ?></td>
        </tr>
        <?php endforeach ?>
        </tbody></table>
        <div style="padding:14px;text-align:right;font-size:16px;font-weight:900">
            Total: <?= $tickets->formatPrice((float)$booking['total']) ?>
        </div>
    </div>

    <!-- Customer -->
    <div class="card">
        <div class="card-header"><h3>Customer</h3></div>
        <div class="card-body" style="font-size:13.5px;display:flex;flex-direction:column;gap:8px">
            <div><span style="color:var(--muted);width:100px;display:inline-block">Name</span> <strong><?= e($booking['customer_name']) ?></strong></div>
            <div><span style="color:var(--muted);width:100px;display:inline-block">Email</span> <a href="mailto:<?= e($booking['customer_email']) ?>"><?= e($booking['customer_email']) ?></a></div>
            <div><span style="color:var(--muted);width:100px;display:inline-block">Phone</span> <?= e($booking['customer_phone'] ?: '—') ?></div>
            <?php if ($booking['customer_note']): ?>
            <div><span style="color:var(--muted);width:100px;display:inline-block">Note</span> <?= e($booking['customer_note']) ?></div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Actions sidebar -->
<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header"><h3>Update Status</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/tickets/bookings/<?= (int)$booking['id'] ?>/status">
                <select name="status" class="form-select" style="margin-bottom:10px">
                    <?php foreach (['pending','confirmed','cancelled','refunded'] as $s): ?>
                    <option value="<?= $s ?>" <?= $s===$booking['status']?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach ?>
                </select>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:13px">Update</button>
            </form>
        </div>
    </div>

    <a href="<?= e($base) ?>/manage/tickets/bookings" class="btn btn-ghost" style="text-align:center;justify-content:center">← All Bookings</a>
    <a href="<?= e($base) ?>/manage/tickets/events/<?= (int)($booking['event']['id']??0) ?>/edit" class="btn btn-ghost" style="text-align:center;justify-content:center">View Event</a>
</div>

</div>
