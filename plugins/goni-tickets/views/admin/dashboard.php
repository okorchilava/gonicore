<?php
$pageTitle     = 'GoniTickets';
$activeNav     = 'tickets';
$topbarActions = '<a href="' . e($base) . '/manage/tickets/events/new" class="btn btn-primary" style="font-size:13px">+ New Event</a>';
?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
    <div class="card"><div class="card-body" style="text-align:center;padding:24px">
        <div style="font-size:32px;font-weight:900;color:var(--accent)"><?= number_format($stats['events']) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">Events</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:24px">
        <div style="font-size:32px;font-weight:900;color:var(--accent)"><?= number_format($stats['bookings']) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">Bookings</div>
    </div></div>
    <div class="card"><div class="card-body" style="text-align:center;padding:24px">
        <div style="font-size:32px;font-weight:900;color:#10b981"><?= number_format($stats['revenue'], 2) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">Revenue</div>
    </div></div>
</div>

<div class="card">
    <div class="card-header"><h3>Recent Events</h3></div>
    <?php if (empty($recent)): ?>
    <div class="empty"><div class="empty-icon">🎟</div><h3>No events yet</h3>
        <p><a href="<?= e($base) ?>/manage/tickets/events/new" class="btn btn-primary" style="margin-top:12px">Create your first event</a></p>
    </div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Event</th><th>Date</th><th>Status</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($recent as $ev): ?>
    <tr>
        <td><strong><?= e($ev['title']) ?></strong></td>
        <td style="color:var(--muted);font-size:13px"><?= e($tickets->formatDate($ev['event_date'])) ?></td>
        <td><?php
            $sc = ['published'=>'#10b981','draft'=>'#94a3b8','cancelled'=>'#ef4444'];
            $c  = $sc[$ev['status']] ?? '#94a3b8';
        ?><span style="background:<?= $c ?>22;color:<?= $c ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase"><?= e($ev['status']) ?></span></td>
        <td><a href="<?= e($base) ?>/manage/tickets/events/<?= (int)$ev['id'] ?>/edit" class="btn btn-ghost" style="font-size:12px">Edit</a></td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>
