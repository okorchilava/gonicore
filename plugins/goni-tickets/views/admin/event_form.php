<?php
$isNew         = $event === null;
$pageTitle     = $isNew ? 'New Event' : 'Edit Event: ' . e($event['title']);
$activeNav     = 'tickets-events';
$topbarActions = $isNew ? '' : '<a href="' . e($base) . '/manage/tickets/bookings?event=' . (int)$event['id'] . '" class="topbar-btn ghost">Bookings</a>';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Event saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;max-width:1100px">

<!-- Main form -->
<form id="eventForm" method="POST" action="<?= e($base) ?>/manage/tickets/events/<?= $isNew ? 'new' : (int)$event['id'].'/edit' ?>">

    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Event Details</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Title <span style="color:#ef4444">*</span></label>
                <input type="text" name="title" class="form-input" value="<?= e((string)($event['title']??'')) ?>"
                       required oninput="autoSlug(this.value)" placeholder="Event title">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" id="slugField" class="form-input" value="<?= e((string)($event['slug']??'')) ?>" placeholder="auto-generated">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Short Description</label>
                <input type="text" name="short_description" class="form-input" value="<?= e((string)($event['short_description']??'')) ?>" placeholder="One-line summary">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Full Description</label>
                <textarea name="description" class="form-input" rows="6" style="resize:vertical"><?= e((string)($event['description']??'')) ?></textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Date &amp; Location</h3></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Start Date &amp; Time <span style="color:#ef4444">*</span></label>
                <input type="datetime-local" name="event_date" class="form-input"
                       value="<?= e(isset($event['event_date']) ? date('Y-m-d\TH:i', strtotime($event['event_date'])) : '') ?>" required>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">End Date &amp; Time</label>
                <input type="datetime-local" name="event_end_date" class="form-input"
                       value="<?= e(isset($event['event_end_date']) ? date('Y-m-d\TH:i', strtotime((string)$event['event_end_date'])) : '') ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Venue / Place</label>
                <input type="text" name="venue" class="form-input" value="<?= e((string)($event['venue']??'')) ?>" placeholder="Concert Hall, Stadium…">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Location / City</label>
                <input type="text" name="location" class="form-input" value="<?= e((string)($event['location']??'')) ?>" placeholder="Tbilisi, Georgia">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Cover Image</h3></div>
        <div class="card-body">
            <input type="text" name="image" class="form-input" value="<?= e((string)($event['image']??'')) ?>"
                   placeholder="https://…" oninput="previewImg(this.value)">
            <div id="imgPreview" style="margin-top:10px;<?= empty($event['image']) ? 'display:none' : '' ?>">
                <img id="imgPreviewEl" src="<?= e((string)($event['image']??'')) ?>"
                     style="max-height:160px;border-radius:8px;border:1px solid var(--border)">
            </div>
        </div>
    </div>

    <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px">
            <?= $isNew ? 'Create Event' : 'Save Changes' ?>
        </button>
        <a href="<?= e($base) ?>/manage/tickets/events" class="btn btn-ghost" style="margin-left:8px;font-size:14px">Cancel</a>
    </div>
</form>

<!-- Sidebar -->
<div style="display:flex;flex-direction:column;gap:16px">

    <div class="card">
        <div class="card-header"><h3>Publish</h3></div>
        <div class="card-body">
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Category</label>
                <?php $dbCats = isset($tickets) ? $tickets->allCategories() : []; ?>
                <select name="category" form="eventForm" class="form-select">
                    <?php if (empty($dbCats)): ?>
                    <option value="other">🎟 სხვა</option>
                    <?php else: foreach ($dbCats as $cat): ?>
                    <option value="<?= e($cat['slug']) ?>" <?= (($event['category']??'other')===$cat['slug'])?'selected':'' ?>>
                        <?= e($cat['icon'].' '.$cat['label']) ?>
                    </option>
                    <?php endforeach; endif ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Organizer</label>
                <?php $dbOrgs = isset($tickets) ? $tickets->allOrganizers() : []; ?>
                <select name="organizer_id" form="eventForm" class="form-select">
                    <option value="">— none —</option>
                    <?php foreach ($dbOrgs as $org): ?>
                    <option value="<?= (int)$org['id'] ?>" <?= (((int)($event['organizer_id']??0))===(int)$org['id'])?'selected':'' ?>>
                        <?= e($org['name']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Status</label>
                <select name="status" form="eventForm" class="form-select">
                    <?php foreach (['draft','published','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($event['status']??'draft')===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="featured" value="1" form="eventForm" <?= !empty($event['featured'])?'checked':'' ?>>
                Featured event
            </label>
        </div>
    </div>

    <?php if (!$isNew): ?>
    <!-- Ticket Types -->
    <div class="card">
        <div class="card-header" style="justify-content:space-between">
            <h3>Ticket Types</h3>
            <button type="button" class="btn btn-ghost" style="font-size:12px"
                onclick="document.getElementById('addTTForm').classList.toggle('hidden')">+ Add</button>
        </div>

        <div id="addTTForm" class="hidden" style="padding:14px;border-bottom:1px solid var(--border)">
            <form method="POST" action="<?= e($base) ?>/manage/tickets/events/<?= (int)$event['id'] ?>/ticket-types/create">
                <div style="display:flex;flex-direction:column;gap:8px">
                    <input type="text" name="name" class="form-input" style="padding:6px 10px;font-size:13px" placeholder="Name (e.g. General)" required>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <input type="number" name="price" class="form-input" style="padding:6px 10px;font-size:13px" placeholder="Price" min="0" step="0.01" required>
                        <input type="number" name="quantity" class="form-input" style="padding:6px 10px;font-size:13px" placeholder="Qty (blank=∞)" min="1">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <input type="number" name="max_per_order" class="form-input" style="padding:6px 10px;font-size:13px" placeholder="Max/order" min="1">
                        <select name="status" class="form-select" style="padding:6px 10px;font-size:13px">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size:12px;padding:7px">Add Ticket Type</button>
                </div>
            </form>
        </div>

        <?php if (empty($ticketTypes)): ?>
        <div style="padding:16px;text-align:center;color:var(--muted);font-size:13px">No ticket types yet.</div>
        <?php else: ?>
        <?php foreach ($ticketTypes as $tt): ?>
        <div class="tt-row" id="tt-<?= (int)$tt['id'] ?>" style="padding:10px 14px;border-bottom:1px solid var(--border)">
            <div style="display:flex;justify-content:space-between;align-items:start">
                <div>
                    <div style="font-size:13px;font-weight:700"><?= e($tt['name']) ?></div>
                    <div style="font-size:11.5px;color:var(--muted)">
                        <?= number_format((float)$tt['price'],2) ?> ·
                        <?= $tt['quantity'] !== null ? $tt['sold'].'/'.$tt['quantity'] : $tt['sold'].' sold' ?>
                        <?= $tt['status']==='inactive' ? ' · <em>inactive</em>' : '' ?>
                    </div>
                </div>
                <div style="display:flex;gap:4px">
                    <button type="button" class="btn btn-ghost" style="font-size:11px;padding:3px 8px"
                        onclick="toggleTTEdit(<?= (int)$tt['id'] ?>)">Edit</button>
                    <form method="POST" action="<?= e($base) ?>/manage/tickets/ticket-types/<?= (int)$tt['id'] ?>/delete" style="display:inline">
                        <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 8px"
                            onclick="gcConfirm(this,'Delete ticket type?','','Delete')">✕</button>
                    </form>
                </div>
            </div>
            <div id="tt-edit-<?= (int)$tt['id'] ?>" style="display:none;margin-top:10px">
                <form method="POST" action="<?= e($base) ?>/manage/tickets/ticket-types/<?= (int)$tt['id'] ?>/update">
                    <div style="display:flex;flex-direction:column;gap:7px">
                        <input type="text" name="name" value="<?= e($tt['name']) ?>" class="form-input" style="padding:5px 9px;font-size:12px" required>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px">
                            <input type="number" name="price" value="<?= (float)$tt['price'] ?>" class="form-input" style="padding:5px 9px;font-size:12px" min="0" step="0.01" required>
                            <input type="number" name="quantity" value="<?= $tt['quantity'] !== null ? (int)$tt['quantity'] : '' ?>" class="form-input" style="padding:5px 9px;font-size:12px" placeholder="∞">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px">
                            <input type="number" name="max_per_order" value="<?= $tt['max_per_order'] ?? '' ?>" class="form-input" style="padding:5px 9px;font-size:12px" placeholder="Max/order">
                            <select name="status" class="form-select" style="padding:5px 9px;font-size:12px">
                                <option value="active" <?= $tt['status']==='active'?'selected':'' ?>>Active</option>
                                <option value="inactive" <?= $tt['status']==='inactive'?'selected':'' ?>>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size:12px;padding:5px">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach ?>
        <?php endif ?>
    </div>

    <?php if (isset($eventStats)): ?>
    <div class="card">
        <div class="card-body" style="padding:14px">
            <div style="font-size:13px;color:var(--muted)">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <span>Tickets sold</span>
                    <strong><?= $eventStats['sold'] ?><?= $eventStats['capacity']!==null?' / '.$eventStats['capacity']:'' ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span>Revenue</span>
                    <strong><?= number_format($eventStats['revenue'],2) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <?php endif ?>
    <?php endif ?>

</div><!-- /sidebar -->
</div>

<style>.hidden{display:none!important}</style>
<script>
function autoSlug(v) {
    var f = document.getElementById('slugField');
    if (f && !f.dataset.locked) {
        f.value = v.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/[\s]+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
    }
}
document.getElementById('slugField')?.addEventListener('input', function(){ this.dataset.locked = '1'; });
function previewImg(url) {
    var p = document.getElementById('imgPreview');
    var i = document.getElementById('imgPreviewEl');
    if (url && p && i) { i.src = url; p.style.display = ''; }
    else if (p) { p.style.display = 'none'; }
}
function toggleTTEdit(id) {
    var el = document.getElementById('tt-edit-' + id);
    if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
}
</script>
