<?php
$pageTitle     = 'Settings';
$activeNav     = 'appointment';
$topbarActions = '';
?>
<?php if ($saved): ?>
<div id="gc-flash" data-msg="Settings saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<div style="max-width:660px">
<form method="POST" action="<?= e($base) ?>/manage/appointment/settings">

<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>General</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        <div>
            <label class="form-label">Brand Name</label>
            <input type="text" name="brand_name" class="form-input" value="<?= e($svc->setting('brand_name', 'Book Appointment')) ?>">
            <div style="font-size:12px;color:var(--muted);margin-top:4px">Shown as the heading on the public booking page</div>
        </div>
        <div>
            <label class="form-label">Booking Page Slug</label>
            <div style="display:flex;align-items:center;gap:6px">
                <span style="color:var(--muted);font-size:13px">/</span>
                <input type="text" name="page_slug" class="form-input" value="<?= e($svc->setting('page_slug', 'book')) ?>" style="max-width:200px">
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px">URL path for the public booking page (e.g. <code>book</code> → <em>/book</em>)</div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>Currency</h3></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
            <label class="form-label">Currency Code</label>
            <input type="text" name="currency" class="form-input" value="<?= e($svc->setting('currency', 'GEL')) ?>" maxlength="5" placeholder="GEL">
        </div>
        <div>
            <label class="form-label">Currency Symbol</label>
            <input type="text" name="currency_symbol" class="form-input" value="<?= e($svc->setting('currency_symbol', '₾')) ?>" maxlength="5" placeholder="₾">
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>Booking Rules</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
            <div>
                <label class="form-label">Slot Interval (min)</label>
                <input type="number" name="slot_interval" class="form-input" min="15" step="15" value="<?= (int)$svc->setting('slot_interval', '30') ?>">
            </div>
            <div>
                <label class="form-label">Advance Days</label>
                <input type="number" name="advance_days" class="form-input" min="1" max="365" value="<?= (int)$svc->setting('advance_days', '30') ?>">
            </div>
            <div>
                <label class="form-label">Min Advance (hours)</label>
                <input type="number" name="min_advance_hours" class="form-input" min="0" value="<?= (int)$svc->setting('min_advance_hours', '1') ?>">
            </div>
        </div>
        <div style="font-size:12px;color:var(--muted)">
            <strong>Slot Interval:</strong> time between each bookable slot (must be ≤ service duration).
            <strong>Advance Days:</strong> how many days ahead customers can book.
            <strong>Min Advance:</strong> minimum hours before an appointment can be booked.
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary">Save Settings</button>

</form>
</div>
