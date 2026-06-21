<?php
$eventsSlug = $tickets->setting('events_page_slug', 'events');
$activeSubnav = '';
include __DIR__ . '/_subnav.php';
?>
<style>
.gt-acc-page{background:var(--gt-page-bg,#0a0812);min-height:80vh;position:relative;padding:48px 24px 80px}
.gt-glow{position:absolute;border-radius:50%;filter:blur(160px);pointer-events:none;z-index:0}
.gt-glow-a{width:700px;height:700px;background:rgba(124,58,237,.2);top:-200px;left:50%;transform:translateX(-60%)}
.gt-glow-b{width:500px;height:500px;background:rgba(236,72,153,.1);top:-150px;left:50%;transform:translateX(-5%)}
.gt-acc-wrap{position:relative;z-index:1;max-width:680px;margin:0 auto}

.gt-acc-profile{display:flex;align-items:center;gap:20px;margin-bottom:36px;padding:24px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:20px 0 20px 0}
.gt-acc-avatar{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#a855f7);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:900;color:#fff;flex-shrink:0}
.gt-acc-name{font-size:20px;font-weight:900;color:#f1f5f9;letter-spacing:-.3px}
.gt-acc-email{font-size:13px;color:#94a3b8;margin-top:2px}
.gt-acc-logout{margin-left:auto;display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:#94a3b8;border-radius:20px;padding:7px 16px;font-size:12px;font-weight:600;text-decoration:none;transition:color .15s,background .15s;flex-shrink:0}
.gt-acc-logout:hover{color:#fca5a5;background:rgba(239,68,68,.08)}

.gt-acc-section-head{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.gt-acc-section-head span{font-size:11px;font-weight:800;color:#a78bfa;text-transform:uppercase;letter-spacing:1.5px;white-space:nowrap}
.gt-acc-section-head::before,.gt-acc-section-head::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.06)}

.gt-acc-booking{display:flex;align-items:center;gap:16px;padding:18px 20px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:14px 0 14px 0;margin-bottom:10px;text-decoration:none;transition:background .15s,border-color .15s}
.gt-acc-booking:hover{background:rgba(124,58,237,.06);border-color:rgba(124,58,237,.2);text-decoration:none}
.gt-acc-booking-icon{font-size:28px;flex-shrink:0}
.gt-acc-booking-info{flex:1;min-width:0}
.gt-acc-booking-event{font-size:14px;font-weight:800;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gt-acc-booking-meta{font-size:11.5px;color:#94a3b8;margin-top:3px;display:flex;gap:8px;flex-wrap:wrap}
.gt-acc-booking-right{text-align:right;flex-shrink:0}
.gt-acc-booking-num{font-family:monospace;font-size:11px;color:#a78bfa;font-weight:700}
.gt-acc-booking-status{display:inline-block;font-size:10px;font-weight:800;padding:2px 8px;border-radius:10px;margin-top:4px;border:1px solid}
.gt-acc-empty{text-align:center;padding:40px;color:#94a3b8}

html.gt-light .gt-acc-page{background:#f5f3ff}
html.gt-light .gt-acc-profile{background:#fff;border-color:rgba(0,0,0,.07)}
html.gt-light .gt-acc-name{color:#1e1b4b}
html.gt-light .gt-acc-email{color:#94a3b8}
html.gt-light .gt-acc-booking{background:#fff;border-color:rgba(0,0,0,.07)}
html.gt-light .gt-acc-booking:hover{background:rgba(124,58,237,.04)}
html.gt-light .gt-acc-booking-event{color:#1e1b4b}
html.gt-light .gt-acc-section-head span{color:#7c3aed}
html.gt-light .gt-acc-section-head::before,
html.gt-light .gt-acc-section-head::after{background:rgba(0,0,0,.08)}
</style>

<div class="gt-acc-page">
    <div class="gt-glow gt-glow-a"></div>
    <div class="gt-glow gt-glow-b"></div>
    <div class="gt-acc-wrap">

        <div class="gt-acc-profile">
            <div class="gt-acc-avatar"><?= mb_strtoupper(mb_substr((string)($user['name']??'?'), 0, 1)) ?></div>
            <div>
                <div class="gt-acc-name"><?= e((string)($user['name']??'')) ?></div>
                <div class="gt-acc-email"><?= e((string)($user['email']??'')) ?></div>
            </div>
            <a href="<?= e($base) ?>/tickets/logout" class="gt-acc-logout">გასვლა ↗</a>
        </div>

        <div class="gt-acc-section-head"><span>ჩემი ბრონირებები</span></div>

        <?php if (empty($bookings)): ?>
        <div class="gt-acc-empty">
            <div style="font-size:40px;margin-bottom:12px">🎟</div>
            <p style="font-size:15px;color:#e2e8f0;font-weight:700;margin-bottom:6px">ჯერ ბრონირება არ გაქვს</p>
            <a href="<?= e($base) ?>/<?= e($eventsSlug) ?>" style="color:#a78bfa;font-size:13px">ღონისძიებების ნახვა →</a>
        </div>
        <?php else: ?>
        <?php foreach ($bookings as $b):
            $ev        = $b['event'] ?? [];
            $confirmed = $b['status'] === 'confirmed';
            $cancelled = in_array($b['status'], ['cancelled','refunded'], true);
            $sc        = $confirmed ? '#34d399' : ($cancelled ? '#f87171' : '#fbbf24');
            $sl        = $confirmed ? '✓ დადასტურებული' : ($cancelled ? '✗ გაუქმებული' : '⏳ მოლოდინში');
        ?>
        <a href="<?= e($base) ?>/tickets/view/<?= e($b['booking_number']) ?>" class="gt-acc-booking">
            <div class="gt-acc-booking-icon">🎭</div>
            <div class="gt-acc-booking-info">
                <div class="gt-acc-booking-event"><?= e($ev['title'] ?? 'ღონისძიება') ?></div>
                <div class="gt-acc-booking-meta">
                    <?php if ($ev && $ev['event_date']): ?>
                    <span>📅 <?= e($tickets->formatDate($ev['event_date'])) ?></span>
                    <?php endif ?>
                    <span><?= $tickets->formatPrice((float)$b['total']) ?></span>
                </div>
            </div>
            <div class="gt-acc-booking-right">
                <div class="gt-acc-booking-num"><?= e($b['booking_number']) ?></div>
                <div class="gt-acc-booking-status" style="color:<?= $sc ?>;border-color:<?= $sc ?>33;background:<?= $sc ?>18"><?= $sl ?></div>
            </div>
        </a>
        <?php endforeach ?>
        <?php endif ?>

    </div>
</div>
