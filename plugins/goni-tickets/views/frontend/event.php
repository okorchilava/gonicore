<?php
$eventsSlug = $tickets->setting('events_page_slug', 'events');
$currency   = $tickets->setting('currency', 'GEL');

// Check if BOG is available
$bogAvailable = false;
try {
    $bogAvailable = class_exists('BogPayment\BogService')
        && gc_container()->get(\BogPayment\BogService::class)->isEnabled();
} catch (\Throwable) {}

$activeSubnav = 'events';
include __DIR__ . '/_subnav.php';
?>
<style>
.gt-event-hero{position:relative;min-height:360px;display:flex;align-items:flex-end;background:linear-gradient(135deg,#0f172a,#312e81);overflow:hidden}
.gt-event-hero-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.45}
.gt-event-hero-inner{position:relative;z-index:1;max-width:1200px;width:100%;margin:0 auto;padding:40px 24px}
.gt-event-hero-inner h1{font-size:clamp(26px,4vw,44px);font-weight:900;color:#fff;letter-spacing:-1px;margin-bottom:12px}
.gt-event-meta{display:flex;flex-wrap:wrap;gap:16px;color:#cbd5e1;font-size:14px}
.gt-event-meta span{display:flex;align-items:center;gap:6px}
.gt-layout{max-width:1200px;margin:0 auto;padding:40px 24px;display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:start}
.gt-content h2{font-size:22px;font-weight:800;margin-bottom:16px;color:var(--text)}
.gt-content p{font-size:15px;line-height:1.8;color:#374151;margin-bottom:1em}
.gt-tt-card{border:2px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:12px;transition:border-color .15s}
.gt-tt-card.selected{border-color:#10b27c;background:#f0fdf9}
.gt-tt-name{font-size:16px;font-weight:700;color:var(--text);margin-bottom:4px}
.gt-tt-desc{font-size:13px;color:var(--muted);margin-bottom:12px}
.gt-tt-row{display:flex;align-items:center;justify-content:space-between}
.gt-tt-price{font-size:20px;font-weight:900;color:#10b27c}
.gt-qty{display:flex;align-items:center;gap:8px}
.gt-qty button{width:30px;height:30px;border-radius:50%;border:1.5px solid var(--border);background:#fff;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s}
.gt-qty button:hover{border-color:#10b27c;color:#10b27c}
.gt-qty input{width:44px;text-align:center;border:1.5px solid var(--border);border-radius:8px;padding:4px;font-size:14px;font-weight:700}
.gt-total-bar{background:#f8fafc;border-radius:10px;padding:16px;margin-top:16px;display:flex;justify-content:space-between;align-items:center}
.gt-submit-btn{width:100%;padding:14px;background:#10b27c;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:800;cursor:pointer;margin-top:12px;transition:background .15s;font-family:var(--font)}
.gt-submit-btn:hover{background:#0e9c6c}
.gt-error{background:#fef2f2;border:1.5px solid #ef4444;border-radius:8px;padding:12px 16px;color:#b91c1c;font-size:13.5px;margin-bottom:16px}
.gt-payment-opt{display:flex;align-items:center;gap:10px;padding:12px 16px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:border-color .15s;margin-bottom:8px}
.gt-payment-opt:has(input:checked){border-color:#10b27c;background:#f0fdf9}
.gt-soldout-banner{background:#fef2f2;border:1.5px solid #ef4444;border-radius:10px;padding:20px;text-align:center;color:#b91c1c;font-weight:700;font-size:15px}
@media(max-width:768px){.gt-layout{grid-template-columns:1fr}}
</style>

<!-- Hero -->
<div class="gt-event-hero">
    <?php if ($event['image']): ?>
    <img src="<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>" class="gt-event-hero-img">
    <?php endif ?>
    <div class="gt-event-hero-inner">
        <div style="margin-bottom:12px">
            <a href="<?= e($base) ?>/<?= $eventsSlug ?>" style="color:#94a3b8;font-size:13px;text-decoration:none">← All Events</a>
        </div>
        <h1><?= e($event['title']) ?></h1>
        <div class="gt-event-meta">
            <span>📅 <?= e($tickets->formatDate($event['event_date'])) ?>
                <?php if ($event['event_end_date']): ?> — <?= e($tickets->formatDate($event['event_end_date'])) ?><?php endif ?>
            </span>
            <?php if ($event['venue']): ?><span>🏟 <?= e($event['venue']) ?></span><?php endif ?>
            <?php if ($event['location']): ?><span>📍 <?= e($event['location']) ?></span><?php endif ?>
        </div>
    </div>
</div>

<div class="gt-layout">

    <!-- Description -->
    <div class="gt-content">
        <?php if ($event['description']): ?>
        <h2>About this event</h2>
        <div><?= nl2br(e($event['description'])) ?></div>
        <?php endif ?>
    </div>

    <!-- Booking panel -->
    <div style="position:sticky;top:90px">
        <?php if (!empty($error)): ?>
        <div class="gt-error">⚠️ <?= e($error) ?></div>
        <?php endif ?>

        <?php if ($soldOut): ?>
        <div class="gt-soldout-banner">🎟 This event is sold out</div>

        <?php else: ?>
        <form method="POST" action="<?= e($base) ?>/<?= $eventsSlug ?>/<?= e($event['slug']) ?>/book" id="bookForm">

            <!-- Ticket selection -->
            <div style="margin-bottom:20px">
                <h3 style="font-size:15px;font-weight:800;margin-bottom:14px;color:var(--text)">Select Tickets</h3>
                <?php foreach ($ticketTypes as $tt):
                    $avail = $tickets->availableCount((int)$tt['id']);
                    $max   = min(
                        $avail ?? 20,
                        $tt['max_per_order'] ? (int)$tt['max_per_order'] : 20
                    );
                ?>
                <div class="gt-tt-card" id="tt-card-<?= (int)$tt['id'] ?>">
                    <div class="gt-tt-name"><?= e($tt['name']) ?></div>
                    <?php if ($tt['description']): ?>
                    <div class="gt-tt-desc"><?= e($tt['description']) ?></div>
                    <?php endif ?>
                    <div class="gt-tt-row">
                        <div class="gt-tt-price"><?= $tickets->formatPrice((float)$tt['price']) ?></div>
                        <div class="gt-qty">
                            <button type="button" onclick="changeQty(<?= (int)$tt['id'] ?>,-1)">−</button>
                            <input type="number" id="qty-<?= (int)$tt['id'] ?>" name="qty[<?= (int)$tt['id'] ?>]"
                                   value="0" min="0" max="<?= $max ?>"
                                   data-price="<?= (float)$tt['price'] ?>"
                                   onchange="updateTotal()" style="width:44px;text-align:center;border:1.5px solid var(--border);border-radius:8px;padding:4px;font-size:14px;font-weight:700">
                            <button type="button" onclick="changeQty(<?= (int)$tt['id'] ?>,1)">+</button>
                        </div>
                    </div>
                    <?php if ($avail !== null && $avail <= 10): ?>
                    <div style="font-size:11.5px;color:#ef4444;margin-top:8px">⚡ Only <?= $avail ?> left!</div>
                    <?php endif ?>
                </div>
                <?php endforeach ?>

                <div class="gt-total-bar">
                    <span style="font-size:14px;color:var(--muted)">Total</span>
                    <span id="totalDisplay" style="font-size:22px;font-weight:900;color:var(--text)">
                        0.00 <?= e($tickets->setting('currency_symbol','₾')) ?>
                    </span>
                </div>
            </div>

            <!-- Contact details -->
            <div style="margin-bottom:20px">
                <h3 style="font-size:15px;font-weight:800;margin-bottom:6px;color:var(--text)">Your Details</h3>
                <?php if ($currentUser): ?>
                <div style="font-size:12px;color:#10b981;margin-bottom:12px;display:flex;align-items:center;gap:5px">
                    <span>✓</span> ავტომატურად შევსებულია შენი პროფილიდან
                </div>
                <?php endif ?>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <input type="text" name="name" class="form-input" placeholder="Full Name *" required
                           value="<?= e((string)($currentUser['name'] ?? '')) ?>"
                           style="padding:11px 14px;font-size:14px">
                    <input type="email" name="email" class="form-input" placeholder="Email Address *" required
                           value="<?= e((string)($currentUser['email'] ?? '')) ?>"
                           style="padding:11px 14px;font-size:14px">
                    <input type="tel" name="phone" class="form-input" placeholder="Phone Number"
                           value="<?= e((string)($currentUser['phone'] ?? '')) ?>"
                           style="padding:11px 14px;font-size:14px">
                    <textarea name="note" class="form-input" placeholder="Note (optional)" rows="2" style="padding:11px 14px;font-size:14px;resize:none"></textarea>
                </div>
            </div>

            <!-- Payment -->
            <div style="margin-bottom:16px">
                <h3 style="font-size:15px;font-weight:800;margin-bottom:14px;color:var(--text)">Payment</h3>
                <label class="gt-payment-opt">
                    <input type="radio" name="payment_method" value="cash" checked>
                    <span style="font-size:20px">💵</span>
                    <div>
                        <div style="font-weight:700;font-size:14px">Cash on arrival</div>
                        <div style="font-size:12px;color:var(--muted)">Pay at the event entrance</div>
                    </div>
                </label>
                <?php if ($bogAvailable): ?>
                <label class="gt-payment-opt">
                    <input type="radio" name="payment_method" value="bog">
                    <span style="font-size:20px">🏦</span>
                    <div>
                        <div style="font-weight:700;font-size:14px">Bank of Georgia</div>
                        <div style="font-size:12px;color:var(--muted)">Card, internet banking, Google Pay</div>
                    </div>
                </label>
                <?php endif ?>
            </div>

            <button type="submit" class="gt-submit-btn">Book Now 🎟</button>
        </form>
        <?php endif ?>
    </div>

</div>

<script>
var prices = {};
document.querySelectorAll('[id^="qty-"]').forEach(function(inp) {
    prices[inp.id.replace('qty-','')] = parseFloat(inp.dataset.price);
});

function changeQty(id, delta) {
    var inp = document.getElementById('qty-' + id);
    var v   = Math.max(0, Math.min(parseInt(inp.max)||99, parseInt(inp.value||0) + delta));
    inp.value = v;
    updateCard(id, v);
    updateTotal();
}

function updateCard(id, qty) {
    var card = document.getElementById('tt-card-' + id);
    if (card) card.classList.toggle('selected', qty > 0);
}

document.querySelectorAll('[id^="qty-"]').forEach(function(inp) {
    inp.addEventListener('change', function() { updateCard(inp.id.replace('qty-',''), parseInt(inp.value||0)); });
});

function updateTotal() {
    var total = 0;
    document.querySelectorAll('[id^="qty-"]').forEach(function(inp) {
        total += (parseInt(inp.value||0)) * (parseFloat(inp.dataset.price)||0);
    });
    var sym = '<?= e($tickets->setting('currency_symbol','₾')) ?>';
    document.getElementById('totalDisplay').textContent = total.toFixed(2) + ' ' + sym;
}
</script>
