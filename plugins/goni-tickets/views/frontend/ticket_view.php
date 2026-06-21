<?php
$eventsSlug = $tickets->setting('events_page_slug', 'events');
$event      = $booking['event'] ?? [];
$confirmed  = $booking['status'] === 'confirmed';
$pending    = $booking['status'] === 'pending';
$cancelled  = in_array($booking['status'], ['cancelled', 'refunded'], true);

$statusColor = $confirmed ? '#34d399' : ($cancelled ? '#f87171' : '#fbbf24');
$statusLabel = $confirmed ? '✓ დადასტურებული' : ($cancelled ? '✗ გაუქმებული' : '⏳ მოლოდინში');

$ticketUrl = e($base) . '/tickets/view/' . e($booking['booking_number']);

$activeSubnav = '';
include __DIR__ . '/_subnav.php';
?>
<style>
.gt-tv-page{background:var(--gt-page-bg,#0a0812);min-height:80vh;position:relative;padding:40px 24px 80px}
.gt-glow{position:absolute;border-radius:50%;filter:blur(160px);pointer-events:none;z-index:0}
.gt-glow-a{width:700px;height:700px;background:rgba(124,58,237,.22);top:-200px;left:50%;transform:translateX(-60%)}
.gt-glow-b{width:500px;height:500px;background:rgba(236,72,153,.12);top:-160px;left:50%;transform:translateX(-5%)}

.gt-tv-wrap{position:relative;z-index:1;max-width:560px;margin:0 auto}

/* ── Ticket card ─────────────────────────────────────────────────────────── */
.gt-ticket{
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.09);
    border-radius:24px 0 24px 0;
    overflow:hidden;
    margin-bottom:20px;
}
.gt-ticket-top{
    padding:32px 32px 0;
}
.gt-ticket-event-img{
    width:100%;height:180px;object-fit:cover;
    border-radius:14px 0 14px 0;
    display:block;margin-bottom:20px;
}
.gt-ticket-event-ph{
    width:100%;height:160px;
    border-radius:14px 0 14px 0;
    display:flex;align-items:center;justify-content:center;
    font-size:64px;margin-bottom:20px;
}
.gt-ticket-status-bar{
    display:inline-flex;align-items:center;gap:6px;
    padding:4px 14px;border-radius:20px;
    font-size:11px;font-weight:800;letter-spacing:.5px;
    border:1px solid;margin-bottom:14px;
}
.gt-ticket-event-name{
    font-size:clamp(18px,3vw,26px);font-weight:900;
    color:#f1f5f9;line-height:1.2;letter-spacing:-.4px;
    margin-bottom:8px;
}
.gt-ticket-event-meta{
    font-size:12.5px;color:#94a3b8;
    display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px;
}

/* ── Tear line ────────────────────────────────────────────────────────────── */
.gt-ticket-tear{
    display:flex;align-items:center;
    padding:0 0;margin:0 -1px;
    position:relative;
    height:24px;
}
.gt-ticket-tear::before{
    content:'';flex:1;height:1px;
    border-top:2px dashed rgba(255,255,255,.1);
}
.gt-ticket-tear::after{
    content:'';flex:1;height:1px;
    border-top:2px dashed rgba(255,255,255,.1);
}
.gt-ticket-tear-circle{
    width:32px;height:32px;border-radius:50%;flex-shrink:0;
    background:var(--gt-page-bg,#0a0812);
    border:1px solid rgba(255,255,255,.09);
}
.gt-ticket-tear-circle--left{ margin-left:-17px }
.gt-ticket-tear-circle--right{ margin-right:-17px }

/* ── QR section ──────────────────────────────────────────────────────────── */
.gt-ticket-bottom{
    padding:24px 32px 32px;
    display:flex;align-items:center;gap:24px;
}
.gt-qr-wrap{
    flex-shrink:0;
    background:#fff;
    border-radius:12px;
    padding:10px;
    width:120px;height:120px;
    display:flex;align-items:center;justify-content:center;
}
.gt-qr-wrap canvas,.gt-qr-wrap img{display:block}
.gt-ticket-details{flex:1;min-width:0}
.gt-ticket-num{
    font-family:monospace;font-size:15px;font-weight:900;
    color:#a78bfa;letter-spacing:1px;margin-bottom:6px;
}
.gt-ticket-holder{font-size:13px;font-weight:700;color:#e2e8f0;margin-bottom:3px}
.gt-ticket-email{font-size:11.5px;color:#94a3b8;margin-bottom:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.gt-ticket-items{font-size:12.5px;color:#94a3b8;line-height:1.8}
.gt-ticket-total{font-size:16px;font-weight:900;color:#a78bfa;margin-top:8px}

/* ── Info cards ──────────────────────────────────────────────────────────── */
.gt-tv-card{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.07);
    border-radius:14px;
    overflow:hidden;
    margin-bottom:12px;
}
.gt-tv-card-head{padding:12px 20px;border-bottom:1px solid rgba(255,255,255,.06);font-size:11px;font-weight:800;color:#a78bfa;text-transform:uppercase;letter-spacing:1px}
.gt-tv-row{display:flex;justify-content:space-between;align-items:center;padding:10px 20px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px}
.gt-tv-row:last-child{border-bottom:none}
.gt-tv-row-label{color:#94a3b8}
.gt-tv-row-val{font-weight:600;color:#e2e8f0;text-align:right;max-width:60%}
.gt-tv-total{display:flex;justify-content:space-between;padding:14px 20px;font-size:15px;font-weight:900;border-top:1px solid rgba(255,255,255,.06)}
.gt-tv-total-val{color:#a78bfa}

/* ── Actions ─────────────────────────────────────────────────────────────── */
.gt-tv-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
.gt-tv-btn{display:inline-flex;align-items:center;gap:7px;padding:11px 20px;border-radius:30px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:opacity .15s}
.gt-tv-btn--primary{background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff}
.gt-tv-btn--ghost{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#94a3b8}
.gt-tv-btn:hover{opacity:.85}

/* ── Cancelled overlay ───────────────────────────────────────────────────── */
.gt-ticket--cancelled{opacity:.55;filter:grayscale(.6)}

/* ── Light mode ──────────────────────────────────────────────────────────── */
html.gt-light .gt-tv-page{background:#f5f3ff}
html.gt-light .gt-ticket{background:#fff;border-color:rgba(0,0,0,.07)}
html.gt-light .gt-ticket-event-name{color:#1e1b4b}
html.gt-light .gt-ticket-event-meta{color:#94a3b8}
html.gt-light .gt-ticket-tear::before,
html.gt-light .gt-ticket-tear::after{border-color:rgba(0,0,0,.1)}
html.gt-light .gt-ticket-tear-circle{background:#f5f3ff;border-color:rgba(0,0,0,.07)}
html.gt-light .gt-ticket-num{color:#7c3aed}
html.gt-light .gt-ticket-holder{color:#1e1b4b}
html.gt-light .gt-ticket-email{color:#94a3b8}
html.gt-light .gt-ticket-items{color:#64748b}
html.gt-light .gt-ticket-total{color:#7c3aed}
html.gt-light .gt-tv-card{background:#fff;border-color:rgba(0,0,0,.07)}
html.gt-light .gt-tv-card-head{color:#7c3aed}
html.gt-light .gt-tv-row{border-color:rgba(0,0,0,.04)}
html.gt-light .gt-tv-row-label{color:#94a3b8}
html.gt-light .gt-tv-row-val{color:#1e1b4b}
html.gt-light .gt-tv-total{border-color:rgba(0,0,0,.06)}
html.gt-light .gt-tv-total-val{color:#7c3aed}
html.gt-light .gt-tv-btn--ghost{background:rgba(0,0,0,.04);border-color:rgba(0,0,0,.1);color:#64748b}

@media(max-width:480px){
    .gt-ticket-top,.gt-ticket-bottom{padding:20px}
    .gt-ticket-bottom{flex-direction:column;align-items:center;text-align:center}
    .gt-ticket-email{white-space:normal}
    .gt-tv-actions{justify-content:center}
}

@media print{
    .gt-subnav,.gt-glow,.gt-tv-actions{display:none!important}
    .gt-tv-page{background:#fff!important;padding:0!important}
    .gt-ticket{background:#fff!important;border:1.5px solid #ddd!important;color:#000!important}
    .gt-ticket-event-name,.gt-ticket-holder,.gt-ticket-num{color:#000!important}
    .gt-ticket-event-meta,.gt-ticket-items,.gt-ticket-email{color:#555!important}
    .gt-ticket-tear-circle{background:#fff!important}
}
</style>

<div class="gt-tv-page">
    <div class="gt-glow gt-glow-a"></div>
    <div class="gt-glow gt-glow-b"></div>

    <div class="gt-tv-wrap">

        <!-- ── Ticket card ────────────────────────────────────────────────── -->
        <div class="gt-ticket<?= $cancelled ? ' gt-ticket--cancelled' : '' ?>">
            <div class="gt-ticket-top">

                <?php if ($event && !empty($event['image'])): ?>
                <img class="gt-ticket-event-img" src="<?= e($event['image']) ?>" alt="<?= e($event['title'] ?? '') ?>">
                <?php elseif ($event): ?>
                <div class="gt-ticket-event-ph" style="background:linear-gradient(135deg,#3b0764,#7c3aed)">🎭</div>
                <?php endif ?>

                <div class="gt-ticket-status-bar" style="color:<?= $statusColor ?>;border-color:<?= $statusColor ?>33;background:<?= $statusColor ?>18">
                    <?= $statusLabel ?>
                </div>

                <div class="gt-ticket-event-name"><?= e($event['title'] ?? 'ღონისძიება') ?></div>
                <div class="gt-ticket-event-meta">
                    <?php if ($event && $event['event_date']): ?>
                    <span>📅 <?= e($tickets->formatDate($event['event_date'])) ?></span>
                    <?php endif ?>
                    <?php if ($event && $event['venue']): ?>
                    <span>📍 <?= e($event['venue']) ?></span>
                    <?php endif ?>
                    <?php if ($event && $event['location']): ?>
                    <span>🗺 <?= e($event['location']) ?></span>
                    <?php endif ?>
                </div>
            </div>

            <!-- tear line -->
            <div class="gt-ticket-tear">
                <div class="gt-ticket-tear-circle gt-ticket-tear-circle--left"></div>
                <div class="gt-ticket-tear-circle gt-ticket-tear-circle--right"></div>
            </div>

            <!-- QR + details -->
            <div class="gt-ticket-bottom">
                <div class="gt-qr-wrap" id="gtQrWrap">
                    <div id="gtQrCode"></div>
                </div>
                <div class="gt-ticket-details">
                    <div class="gt-ticket-num"><?= e($booking['booking_number']) ?></div>
                    <div class="gt-ticket-holder"><?= e($booking['customer_name']) ?></div>
                    <div class="gt-ticket-email"><?= e($booking['customer_email']) ?></div>
                    <div class="gt-ticket-items">
                        <?php foreach ($booking['tickets'] as $t): ?>
                        <div><?= e($t['ticket_type_name']) ?> <span style="opacity:.6">×<?= (int)$t['quantity'] ?></span></div>
                        <?php endforeach ?>
                    </div>
                    <div class="gt-ticket-total"><?= $tickets->formatPrice((float)$booking['total']) ?></div>
                </div>
            </div>
        </div>

        <!-- ── Booking details ────────────────────────────────────────────── -->
        <div class="gt-tv-card">
            <div class="gt-tv-card-head">ბრონირების დეტალები</div>
            <div class="gt-tv-row"><span class="gt-tv-row-label">ბრონირების №</span><span class="gt-tv-row-val"><?= e($booking['booking_number']) ?></span></div>
            <div class="gt-tv-row"><span class="gt-tv-row-label">სტატუსი</span><span class="gt-tv-row-val" style="color:<?= $statusColor ?>"><?= $statusLabel ?></span></div>
            <div class="gt-tv-row"><span class="gt-tv-row-label">გადახდა</span><span class="gt-tv-row-val"><?= e(ucfirst($booking['payment_method'])) ?></span></div>
            <div class="gt-tv-row"><span class="gt-tv-row-label">თარიღი</span><span class="gt-tv-row-val"><?= e(date('d.m.Y', strtotime($booking['created_at']))) ?></span></div>
        </div>

        <div class="gt-tv-card">
            <div class="gt-tv-card-head">ბილეთები</div>
            <?php foreach ($booking['tickets'] as $t): ?>
            <div class="gt-tv-row">
                <span class="gt-tv-row-label"><?= e($t['ticket_type_name']) ?> × <?= (int)$t['quantity'] ?></span>
                <span class="gt-tv-row-val"><?= $tickets->formatPrice((float)$t['total']) ?></span>
            </div>
            <?php endforeach ?>
            <div class="gt-tv-total">
                <span>სულ</span>
                <span class="gt-tv-total-val"><?= $tickets->formatPrice((float)$booking['total']) ?></span>
            </div>
        </div>

        <!-- ── Actions ────────────────────────────────────────────────────── -->
        <div class="gt-tv-actions">
            <a href="<?= e($base) ?>/<?= e($eventsSlug) ?>" class="gt-tv-btn gt-tv-btn--ghost">← ღონისძიებები</a>
            <a href="<?= e($base) ?>/tickets/my-ticket" class="gt-tv-btn gt-tv-btn--ghost">🔍 სხვა ბილეთი</a>
            <button onclick="window.print()" class="gt-tv-btn gt-tv-btn--ghost">🖨 ბეჭდვა</button>
        </div>

    </div>
</div>

<!-- QR code library (client-side, no external data sent) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSe1W5pSMjBKc8kkdkpKGiAlnv0kYFExmBjA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function(){
    var el = document.getElementById('gtQrCode');
    if (!el || typeof QRCode === 'undefined') return;
    new QRCode(el, {
        text: '<?= e($ticketUrl) ?>',
        width:  100,
        height: 100,
        colorDark:  '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
})();
</script>
