<?php
$pageTitle     = 'GoniQuizz — შედეგები: ' . e((string)$quiz['title']);
$activeNav     = 'goniquizz-quizzes';
$topbarActions = '<a href="' . e($base) . '/manage/goniquizz" class="btn btn-ghost" style="font-size:13px">← Quizzes</a>';

$isGraded = $quiz['type'] === 'graded';
$attempts = (int)($stats['attempts'] ?? 0);
?>
<style>
.gqz-stat-sm{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:10px;padding:14px 18px;display:flex;flex-direction:column;gap:3px}
.gqz-stat-sm-val{font-size:22px;font-weight:800}
.gqz-stat-sm-lbl{font-size:12px;color:var(--muted)}
.gqz-score-badge{display:inline-flex;align-items:center;justify-content:center;width:38px;height:22px;border-radius:20px;font-size:11px;font-weight:700}
.gqz-score-hi{background:#d1fae5;color:#065f46}
.gqz-score-mid{background:#fef9c3;color:#854d0e}
.gqz-score-lo{background:#fef2f2;color:#991b1b}
.gqz-bar{height:10px;border-radius:10px;background:#e2e8f0;overflow:hidden;min-width:80px}
.gqz-bar-fill{height:100%;background:linear-gradient(90deg,#7c3aed,#a855f7);border-radius:10px;transition:width .5s}
</style>

<?php if ($cleared): ?>
<div class="alert alert-success" style="margin-bottom:16px">✓ შედეგები გასუფთავდა.</div>
<?php endif ?>

<!-- Quiz header -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px">
        <div style="flex:1">
            <div style="font-weight:800;font-size:16px"><?= e((string)$quiz['title']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
                <?= $isGraded ? '🎯 Graded Quiz' : '📊 Poll' ?> ·
                <a href="<?= e($base) ?>/manage/goniquizz/questions?quiz_id=<?= (int)$quiz['id'] ?>">კითხვები</a> ·
                <a href="<?= e($base) ?>/manage/goniquizz/quizzes/form?id=<?= (int)$quiz['id'] ?>">რედაქტირება</a>
            </div>
        </div>
        <?php if ($attempts > 0): ?>
        <form method="POST" action="<?= e($base) ?>/manage/goniquizz/results/clear"
              onsubmit="return confirm('ყველა შედეგი წაიშლება. გაგრძელება?')">
            <input type="hidden" name="quiz_id" value="<?= (int)$quiz['id'] ?>">
            <button type="submit" class="btn btn-ghost" style="color:#ef4444;font-size:13px">🗑 შედეგების გასუფთავება</button>
        </form>
        <?php endif ?>
    </div>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px">
    <div class="gqz-stat-sm"><span class="gqz-stat-sm-val"><?= number_format($attempts) ?></span><span class="gqz-stat-sm-lbl">🎯 Attempts</span></div>
    <?php if ($isGraded && $attempts > 0): ?>
    <div class="gqz-stat-sm"><span class="gqz-stat-sm-val"><?= number_format((float)($stats['avg_pct'] ?? 0), 1) ?>%</span><span class="gqz-stat-sm-lbl">📊 საშუალო ქულა</span></div>
    <div class="gqz-stat-sm"><span class="gqz-stat-sm-val" style="color:#10b981"><?= (int)($stats['max_pct'] ?? 0) ?>%</span><span class="gqz-stat-sm-lbl">⬆ მაქს</span></div>
    <div class="gqz-stat-sm"><span class="gqz-stat-sm-val" style="color:#ef4444"><?= (int)($stats['min_pct'] ?? 0) ?>%</span><span class="gqz-stat-sm-lbl">⬇ მინ</span></div>
    <?php endif ?>
</div>

<?php if ($attempts === 0): ?>
<div class="card"><div class="empty"><div class="empty-icon">📊</div><h3>შედეგი ჯერ არ არის</h3><p>Quiz ჯერ არავის გაუვლია.</p></div></div>

<?php elseif (!$isGraded): ?>
<!-- Poll results: bar chart per question -->
<?php foreach ($questions as $q):
    $qid = (int)$q['id'];
    $optRows = $pollResults[$qid] ?? [];
    $totalSel = array_sum(array_column($optRows, 'selections'));
?>
<div class="card" style="margin-bottom:14px">
    <div class="card-header"><h3 style="font-size:14px;font-weight:700"><?= e((string)$q['question']) ?></h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ($optRows as $opt):
            $sel = (int)$opt['selections'];
            $pct = $totalSel > 0 ? round($sel / $totalSel * 100) : 0;
        ?>
        <div>
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px">
                <span><?= e((string)$opt['option_text']) ?></span>
                <span style="font-weight:700;color:var(--accent)"><?= $pct ?>% <span style="font-size:11px;color:var(--muted);font-weight:400">(<?= $sel ?>)</span></span>
            </div>
            <div class="gqz-bar"><div class="gqz-bar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php endforeach ?>
    </div>
</div>
<?php endforeach ?>

<?php else: ?>
<!-- Graded: submissions table -->
<div class="card">
    <div class="card-header"><h3>ბოლო <?= count($submissions) ?> Attempt<?= count($submissions) !== 1 ? 's' : '' ?></h3></div>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>ID</th>
            <th style="text-align:center">ქულა</th>
            <th style="text-align:center">%</th>
            <th>თარიღი</th>
        </tr></thead>
        <tbody>
        <?php foreach ($submissions as $sub):
            $pct = (int)($sub['score_pct'] ?? 0);
            $cls = $pct >= 80 ? 'gqz-score-hi' : ($pct >= 50 ? 'gqz-score-mid' : 'gqz-score-lo');
        ?>
        <tr>
            <td style="font-family:monospace;font-size:12px;color:var(--muted)">#<?= (int)$sub['id'] ?></td>
            <td style="text-align:center;font-weight:700">
                <?= (int)$sub['score'] ?> / <?= (int)$sub['total'] ?>
            </td>
            <td style="text-align:center">
                <span class="gqz-score-badge <?= $cls ?>"><?= $pct ?>%</span>
            </td>
            <td style="font-size:12.5px;color:var(--muted)">
                <?= date('d.m.Y H:i', strtotime((string)$sub['created_at'])) ?>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif ?>
