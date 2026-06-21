<?php
$pageTitle     = 'GCSMS — OTP Test';
$activeNav     = 'gcsms-otp';
$topbarActions = '';
?>
<style>
.gs-otp-steps{display:flex;align-items:center;gap:0;margin-bottom:28px}
.gs-step{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700}
.gs-step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0}
.gs-step.done .gs-step-num{background:#10b981;color:#fff}
.gs-step.active .gs-step-num{background:var(--accent);color:#fff}
.gs-step.pending .gs-step-num{background:var(--border);color:var(--muted)}
.gs-step.active{color:var(--text)}
.gs-step.pending{color:var(--muted)}
.gs-step-sep{flex:1;height:2px;background:var(--border);margin:0 10px;min-width:30px}
.gs-step-sep.done{background:#10b981}
.gs-result-ok{background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:14px 18px;margin-bottom:16px;color:#15803d;font-size:13.5px;font-weight:600}
.gs-result-err{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:14px 18px;margin-bottom:16px;color:#b91c1c;font-size:13.5px;font-weight:600}
</style>

<!-- Step indicator -->
<div class="gs-otp-steps">
    <div class="gs-step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : 'pending' ?>">
        <div class="gs-step-num"><?= $step > 1 ? '✓' : '1' ?></div>
        <span>ნომრის შეყვანა</span>
    </div>
    <div class="gs-step-sep <?= $step > 1 ? 'done' : '' ?>"></div>
    <div class="gs-step <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : 'pending') ?>">
        <div class="gs-step-num"><?= $step > 2 ? '✓' : '2' ?></div>
        <span>კოდის დადასტურება</span>
    </div>
    <div class="gs-step-sep <?= $step > 2 ? 'done' : '' ?>"></div>
    <div class="gs-step <?= $step === 3 ? 'active' : 'pending' ?>">
        <div class="gs-step-num">3</div>
        <span>შედეგი</span>
    </div>
</div>

<div class="card" style="max-width:480px">
    <div class="card-body">

    <?php if ($error): ?>
    <div class="gs-result-err">✕ <?= e($error) ?></div>
    <?php endif ?>

    <?php if ($step === 1): ?>
    <!-- Step 1: send OTP -->
    <p style="font-size:13.5px;color:var(--muted);margin-bottom:20px">
        GCSMS OTP API-ს ტესტირება. მიუთითეთ ნომერი — ერთჯერადი კოდი გაიგზავნება SMS-ით.
    </p>
    <form method="POST" action="<?= e($base) ?>/manage/gcsms/otp/send">
        <div class="form-group">
            <label class="form-label">ტელეფონის ნომერი</label>
            <input type="tel" name="phone" class="form-input"
                   placeholder="995555123456" required autofocus>
            <div style="font-size:12px;color:var(--muted);margin-top:5px">ფორმატი: 995XXXXXXXXX</div>
        </div>
        <button type="submit" class="btn btn-primary">📤 OTP გაგზავნა</button>
    </form>

    <?php elseif ($step === 2): ?>
    <!-- Step 2: verify -->
    <div style="font-size:13.5px;color:var(--muted);margin-bottom:20px">
        კოდი გაგზავნილია ნომერზე <strong><?= e($phone) ?></strong>. შეიყვანე SMS-ში მიღებული კოდი.
    </div>
    <form method="POST" action="<?= e($base) ?>/manage/gcsms/otp/verify">
        <input type="hidden" name="phone" value="<?= e($phone) ?>">
        <input type="hidden" name="hash"  value="<?= e($hash) ?>">
        <div class="form-group">
            <label class="form-label">კოდი</label>
            <input type="text" name="code" class="form-input"
                   placeholder="123456" required autofocus
                   inputmode="numeric" pattern="[0-9]*"
                   style="letter-spacing:4px;font-size:22px;font-weight:700;text-align:center">
        </div>
        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">✓ დადასტურება</button>
            <a href="<?= e($base) ?>/manage/gcsms/otp" class="btn btn-ghost">← თავიდან</a>
        </div>
    </form>

    <?php else: ?>
    <!-- Step 3: result -->
    <?php if ($result['success'] && ($result['verify'] ?? false)): ?>
    <div class="gs-result-ok">
        ✓ OTP წარმატებით დადასტურდა! ნომერი <strong><?= e($phone) ?></strong> ვალიდურია.
    </div>
    <?php else: ?>
    <div class="gs-result-err">
        ✕ OTP დადასტურება ვერ მოხერხდა.
        <?php if ($error): ?><br><?= e($error) ?><?php endif ?>
    </div>
    <?php endif ?>
    <a href="<?= e($base) ?>/manage/gcsms/otp" class="btn btn-ghost">← ახლიდან ცდა</a>
    <?php endif ?>

    </div>
</div>
