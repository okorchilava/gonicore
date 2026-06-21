<?php
$pageTitle     = 'Events';
$activeNav     = 'tickets-events';
$topbarActions = '<a href="' . e($base) . '/manage/tickets/events/new" class="btn btn-primary" style="font-size:13px">+ New Event</a>';
?>
<?php if (!empty($deleted)): ?>
<div id="gc-flash" data-msg="Event deleted." data-icon="success" style="display:none"></div>
<?php endif ?>

<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>All Events <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= number_format($total) ?>)</span></h3>
        <form method="GET" style="display:flex;gap:8px">
            <input type="text" name="q" value="<?= e($search) ?>" class="form-input" style="padding:6px 10px;font-size:13px;width:200px" placeholder="Search…">
            <button type="submit" class="btn btn-ghost" style="font-size:13px">Search</button>
        </form>
    </div>
    <?php if (empty($events)): ?>
    <div class="empty"><div class="empty-icon">🎟</div><h3>No events found</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Event</th><th>Date</th><th>Tickets</th><th>Revenue</th><th>Status</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($events as $ev):
        $stats = $tickets->statsForEvent((int)$ev['id']);
        $statusColor = ['published'=>'#10b981','draft'=>'#94a3b8','cancelled'=>'#ef4444'][$ev['status']] ?? '#94a3b8';
    ?>
    <tr>
        <td>
            <div style="font-weight:700"><?= e($ev['title']) ?></div>
            <div style="font-size:11.5px;color:var(--muted)"><?= e($ev['location']) ?></div>
        </td>
        <td style="font-size:13px;color:var(--muted);white-space:nowrap"><?= e($tickets->formatDate($ev['event_date'])) ?></td>
        <td style="font-size:13px"><?= $stats['sold'] ?><?= $stats['capacity'] !== null ? ' / '.$stats['capacity'] : '' ?></td>
        <td style="font-size:13px"><?= number_format($stats['revenue'], 2) ?></td>
        <td><span style="background:<?= $statusColor ?>22;color:<?= $statusColor ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase"><?= e($ev['status']) ?></span></td>
        <td style="white-space:nowrap">
            <a href="<?= e($base) ?>/manage/tickets/events/<?= (int)$ev['id'] ?>/edit" class="btn btn-ghost" style="font-size:12px">Edit</a>
            <form method="POST" action="<?= e($base) ?>/manage/tickets/events/<?= (int)$ev['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px"
                    onclick="gcConfirm(this,'Delete event?','All ticket types and bookings will be removed.','Delete')">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>"
           style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;<?= $i===$page?'background:var(--accent);color:#fff;border-color:var(--accent)':'' ?>">
            <?= $i ?>
        </a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
