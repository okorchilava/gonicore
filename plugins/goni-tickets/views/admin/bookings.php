<?php
$pageTitle     = 'Bookings';
$activeNav     = 'tickets-bookings';
$topbarActions = '';

$statusColors = [
    'pending'   => ['#f59e0b','#fef3c7'],
    'confirmed' => ['#10b981','#d1fae5'],
    'cancelled' => ['#ef4444','#fef2f2'],
    'refunded'  => ['#8b5cf6','#ede9fe'],
];
?>
<div class="card">
    <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h3>All Bookings <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= number_format($total) ?>)</span></h3>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <select name="event" class="form-select" style="font-size:13px;padding:6px 10px" onchange="this.form.submit()">
                <option value="0">All Events</option>
                <?php foreach ($events as $ev): ?>
                <option value="<?= (int)$ev['id'] ?>" <?= (int)$ev['id']===$eventId?'selected':'' ?>><?= e($ev['title']) ?></option>
                <?php endforeach ?>
            </select>
        </form>
    </div>

    <?php if (empty($items)): ?>
    <div class="empty"><div class="empty-icon">🎟</div><h3>No bookings yet</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Booking #</th><th>Event</th><th>Customer</th><th>Tickets</th><th>Total</th><th>Status</th><th>Date</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($items as $b):
        [$c, $bg] = $statusColors[$b['status']] ?? ['#94a3b8','#f1f5f9'];
        $ev = $tickets->getEvent((int)$b['event_id']);
    ?>
    <tr>
        <td style="font-family:monospace;font-size:12.5px;font-weight:700"><?= e($b['booking_number']) ?></td>
        <td style="font-size:13px"><?= e($ev['title'] ?? '—') ?></td>
        <td>
            <div style="font-size:13px;font-weight:600"><?= e($b['customer_name']) ?></div>
            <div style="font-size:11.5px;color:var(--muted)"><?= e($b['customer_email']) ?></div>
        </td>
        <td style="font-size:13px"><?= (int)($b['ticket_count'] ?? 0) ?></td>
        <td style="font-size:13px;font-weight:700"><?= $tickets->formatPrice((float)$b['total']) ?></td>
        <td><span style="background:<?= $bg ?>;color:<?= $c ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase"><?= e($b['status']) ?></span></td>
        <td style="font-size:12px;color:var(--muted);white-space:nowrap"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
        <td><a href="<?= e($base) ?>/manage/tickets/bookings/<?= (int)$b['id'] ?>" class="btn btn-ghost" style="font-size:12px">View</a></td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center">
        <?php for ($i=1;$i<=$pages;$i++): ?>
        <a href="?page=<?= $i ?>&event=<?= $eventId ?>"
           style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;<?= $i===$page?'background:var(--accent);color:#fff;border-color:var(--accent)':'' ?>">
           <?= $i ?>
        </a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
