<?php
$pageTitle     = 'Organizers';
$activeNav     = 'tickets-organizers';
$topbarActions = '<a href="' . e($base) . '/manage/tickets/organizers/new" class="btn btn-primary" style="font-size:13px">+ New Organizer</a>';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Organizer saved." data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($deleted)): ?>
<div id="gc-flash" data-msg="Organizer deleted." data-icon="success" style="display:none"></div>
<?php endif ?>

<div class="card">
    <div class="card-header"><h3>All Organizers <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($organizers) ?>)</span></h3></div>
    <?php if (empty($organizers)): ?>
    <div class="empty"><div class="empty-icon">👤</div><h3>No organizers yet</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th style="width:48px"></th><th>Name</th><th>Slug</th><th>Website</th><th>Events</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($organizers as $org): ?>
    <tr>
        <td style="text-align:center">
            <?php if (!empty($org['logo'])): ?>
            <img src="<?= e($org['logo']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
            <?php else: ?>
            <span style="font-size:24px">👤</span>
            <?php endif ?>
        </td>
        <td><strong><?= e($org['name']) ?></strong>
            <?php if (!empty($org['description'])): ?>
            <div style="font-size:11.5px;color:var(--muted);margin-top:2px"><?= e(mb_strimwidth($org['description'],0,60,'…')) ?></div>
            <?php endif ?>
        </td>
        <td><code style="font-size:12px;color:var(--muted)"><?= e($org['slug']) ?></code></td>
        <td>
            <?php if (!empty($org['website'])): ?>
            <a href="<?= e($org['website']) ?>" target="_blank" style="font-size:12px;color:var(--primary)"><?= e(parse_url($org['website'], PHP_URL_HOST) ?: $org['website']) ?></a>
            <?php else: ?><span style="color:var(--muted);font-size:12px">—</span><?php endif ?>
        </td>
        <td style="color:var(--muted);font-size:13px"><?= count($tickets->eventsForOrganizer((int)$org['id'])) ?></td>
        <td style="white-space:nowrap">
            <a href="<?= e($base) ?>/manage/tickets/organizers/<?= (int)$org['id'] ?>/edit" class="btn btn-ghost" style="font-size:12px">Edit</a>
            <form method="POST" action="<?= e($base) ?>/manage/tickets/organizers/<?= (int)$org['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px"
                    onclick="gcConfirm(this,'Delete organizer?','Events linked to this organizer will lose the association.','Delete')">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>
