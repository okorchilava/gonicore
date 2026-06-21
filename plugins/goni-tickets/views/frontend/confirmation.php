<?php
$eventsSlug = $tickets->setting('events_page_slug', 'events');
$event      = $booking['event'] ?? [];
$confirmed  = $booking['status'] === 'confirmed';
?>
<style>
.gt-confirm-wrap{max-width:680px;margin:60px auto;padding:0 24px}
.gt-confirm-hero{text-align:center;padding:40px 24px;background:linear-gradient(135deg,#0f172a,#134e3c);border-radius:16px;color:#fff;margin-bottom:24px}
.gt-confirm-num{font-family:monospace;font-size:28px;font-weight:900;letter-spacing:2px;color:#34d399;margin-bottom:8px}
.gt-confirm-hero h1{font-size:26px;font-weight:900;margin-bottom:8px}
.gt-confirm-hero p{color:#94a3b8;font-size:14px}
.gt-info-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.gt-info-card-head{padding:14px 20px;background:var(--surface);border-bottom:1px solid var(--border);font-weight:700;font-size:14px}
.gt-info-row{display:flex;justify-content:space-between;padding:10px 20px;border-bottom:1px solid var(--border);font-size:13.5px}
.gt-info-row:last-child{border-bottom:none}
.gt-info-row span:first-child{color:var(--muted)}
.gt-info-row span:last-child{font-weight:600}
.gt-total-row{padding:14px 20px;display:flex;justify-content:space-between;background:var(--surface);font-size:16px;font-weight:900}
.gt-actions{display:flex;gap:12px;justify-content:center;margin-top:24px;flex-wrap:wrap}
.gt-pending-banner{background:#fef3c7;border:1.5px solid #f59e0b;border-radius:10px;padding:14px 20px;font-size:13.5px;color:#92400e;margin-bottom:16px;display:flex;gap:10px;align-items:start}
</style>

<div class="gt-confirm-wrap">

    <?php if (!$confirmed): ?>
    <div class="gt-pending-banner">
        <span style="font-size:20px">⏳</span>
        <div>
            <strong>Payment pending.</strong> Your booking is reserved but not yet confirmed.
            If you paid via BOG, please wait for confirmation or check your email.
        </div>
    </div>
    <?php endif ?>

    <div class="gt-confirm-hero">
        <div style="font-size:40px;margin-bottom:12px"><?= $confirmed ? '🎉' : '🎟' ?></div>
        <?php if ($confirmed): ?>
        <h1>You're all set!</h1>
        <p>Your tickets are confirmed. See you there!</p>
        <?php else: ?>
        <h1>Booking Received</h1>
        <p>We'll confirm your booking once payment is received.</p>
        <?php endif ?>
        <div class="gt-confirm-num"><?= e($booking['booking_number']) ?></div>
        <p style="font-size:12px;opacity:.7">Save this booking number for your records</p>
    </div>

    <!-- Event info -->
    <?php if ($event): ?>
    <div class="gt-info-card">
        <div class="gt-info-card-head">Event Details</div>
        <div class="gt-info-row"><span>Event</span><span><?= e($event['title']) ?></span></div>
        <div class="gt-info-row"><span>Date</span><span><?= e($tickets->formatDate($event['event_date'])) ?></span></div>
        <?php if ($event['venue']): ?>
        <div class="gt-info-row"><span>Venue</span><span><?= e($event['venue']) ?></span></div>
        <?php endif ?>
        <?php if ($event['location']): ?>
        <div class="gt-info-row"><span>Location</span><span><?= e($event['location']) ?></span></div>
        <?php endif ?>
    </div>
    <?php endif ?>

    <!-- Tickets -->
    <div class="gt-info-card">
        <div class="gt-info-card-head">Tickets</div>
        <?php foreach ($booking['tickets'] as $t): ?>
        <div class="gt-info-row">
            <span><?= e($t['ticket_type_name']) ?> × <?= (int)$t['quantity'] ?></span>
            <span><?= $tickets->formatPrice((float)$t['total']) ?></span>
        </div>
        <?php endforeach ?>
        <div class="gt-total-row">
            <span>Total</span>
            <span><?= $tickets->formatPrice((float)$booking['total']) ?></span>
        </div>
    </div>

    <!-- Contact -->
    <div class="gt-info-card">
        <div class="gt-info-card-head">Booking Details</div>
        <div class="gt-info-row"><span>Name</span><span><?= e($booking['customer_name']) ?></span></div>
        <div class="gt-info-row"><span>Email</span><span><?= e($booking['customer_email']) ?></span></div>
        <?php if ($booking['customer_phone']): ?>
        <div class="gt-info-row"><span>Phone</span><span><?= e($booking['customer_phone']) ?></span></div>
        <?php endif ?>
        <div class="gt-info-row"><span>Payment</span><span><?= e(ucfirst($booking['payment_method'])) ?></span></div>
        <div class="gt-info-row"><span>Status</span>
            <span style="color:<?= $confirmed?'#10b981':'#f59e0b' ?>"><?= $confirmed?'✓ Confirmed':'⏳ Pending' ?></span>
        </div>
    </div>

    <!-- Add to Google Calendar -->
    <?php if ($event && $confirmed): ?>
    <?php
    $gcalBase  = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
    $gcalDate  = date('Ymd\THis', strtotime($event['event_date']));
    $gcalEnd   = $event['event_end_date'] ? date('Ymd\THis', strtotime($event['event_end_date'])) : date('Ymd\THis', strtotime($event['event_date']) + 7200);
    $gcalTitle = urlencode($event['title']);
    $gcalLoc   = urlencode(implode(', ', array_filter([$event['venue'], $event['location']])));
    $gcalUrl   = $gcalBase . '&text=' . $gcalTitle . '&dates=' . $gcalDate . '/' . $gcalEnd . '&location=' . $gcalLoc;
    ?>
    <?php endif ?>

    <div class="gt-actions">
        <a href="<?= e($base) ?>/<?= $eventsSlug ?>" class="btn" style="background:var(--surface);color:var(--text);border:1px solid var(--border)">← More Events</a>
        <?php if ($event && $confirmed): ?>
        <a href="<?= e($gcalUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary">📅 Add to Calendar</a>
        <?php endif ?>
        <button onclick="window.print()" class="btn" style="background:var(--surface);color:var(--text);border:1px solid var(--border)">🖨 Print</button>
    </div>

</div>
