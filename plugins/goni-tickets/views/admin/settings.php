<?php
$pageTitle     = 'Ticket Settings';
$activeNav     = 'tickets-settings';
$topbarActions = '';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Settings saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<div style="max-width:600px">
<form method="POST" action="<?= e($base) ?>/manage/tickets/settings">
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Currency</h3></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Currency Code</label>
                <input type="text" name="currency" class="form-input" value="<?= e($tickets->setting('currency','GEL')) ?>" placeholder="GEL" maxlength="5">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Currency Symbol</label>
                <input type="text" name="currency_symbol" class="form-input" value="<?= e($tickets->setting('currency_symbol','₾')) ?>" placeholder="₾" maxlength="5">
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Pages</h3></div>
        <div class="card-body">
            <div class="form-group" style="margin:0">
                <label class="form-label">Events Page Slug</label>
                <input type="text" name="events_page_slug" class="form-input" value="<?= e($tickets->setting('events_page_slug','events')) ?>" placeholder="events">
                <div style="font-size:12px;color:var(--muted);margin-top:5px">Public URL: /events</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Notifications</h3></div>
        <div class="card-body">
            <div class="form-group" style="margin:0">
                <label class="form-label">From Email</label>
                <input type="email" name="from_email" class="form-input" value="<?= e($tickets->setting('from_email','')) ?>" placeholder="tickets@yoursite.com">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px">Save Settings</button>
</form>
</div>
