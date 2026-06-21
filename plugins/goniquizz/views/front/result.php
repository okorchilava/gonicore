<?php
// Variables: $sub, $quiz, $questions, $userAnswers, $allOptions, $pollResults
$base    = \GoniQuizz\GoniQuizzService::getBasePath();
$h       = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isGraded = ($quiz['type'] ?? '') === 'graded';
$pct      = (int)($sub['score_pct'] ?? 0);
$score    = (int)($sub['score'] ?? 0);
$total    = (int)($sub['total'] ?? 0);

// Score color + label
if (!$isGraded) {
    $badgeColor = '#7c3aed';
    $badgeLabel = '📊 შედეგი';
} elseif ($pct >= 80) {
    $badgeColor = '#10b981'; $badgeLabel = '🏆 შესანიშნავი!';
} elseif ($pct >= 60) {
    $badgeColor = '#f59e0b'; $badgeLabel = '👍 კარგი';
} elseif ($pct >= 40) {
    $badgeColor = '#f97316'; $badgeLabel = '💪 გააგრძელე';
} else {
    $badgeColor = '#ef4444'; $badgeLabel = '📚 ისე ვარ';
}
?>

<!-- Score badge -->
<div class="gqz-card" style="text-align:center;padding:36px 24px">
    <div style="font-size:13px;font-weight:700;color:#94a3b8;letter-spacing:.5px;margin-bottom:12px;text-transform:uppercase">
        <?= $h((string)($quiz['title'] ?? 'Quiz')) ?>
    </div>

    <?php if ($isGraded): ?>
    <!-- Graded score ring -->
    <div style="position:relative;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
        <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="10"/>
            <circle cx="60" cy="60" r="50" fill="none" stroke="<?= $h($badgeColor) ?>" stroke-width="10"
                    stroke-dasharray="<?= round($pct / 100 * 314, 1) ?> 314"
                    stroke-linecap="round"
                    transform="rotate(-90 60 60)"/>
        </svg>
        <div style="position:absolute;text-align:center">
            <div style="font-size:24px;font-weight:800;color:#1e293b"><?= $pct ?>%</div>
            <div style="font-size:11px;color:#94a3b8"><?= $score ?>/<?= $total ?></div>
        </div>
    </div>
    <div style="font-size:20px;font-weight:800;color:<?= $h($badgeColor) ?>;margin-bottom:4px"><?= $badgeLabel ?></div>
    <div style="font-size:14px;color:#64748b">
        <?= $total ?> კითხვიდან <strong><?= $score ?></strong> სწორად გიპასუხია
    </div>

    <?php else: ?>
    <!-- Poll completed -->
    <div style="font-size:56px;margin-bottom:16px">✅</div>
    <div style="font-size:20px;font-weight:800;color:#7c3aed;margin-bottom:6px">გმადლობ, შეავსე!</div>
    <div style="font-size:14px;color:#64748b">შენი პასუხები ჩაიწერა.</div>
    <?php endif ?>

    <?php if ($quiz['allow_retake'] ?? false): ?>
    <div style="margin-top:20px">
        <a href="<?= $h($base . '/goniquizz/play?slug=' . urlencode((string)$quiz['slug'])) ?>"
           class="gqz-btn-ghost">🔄 ხელახლა გავლა</a>
    </div>
    <?php endif ?>
</div>

<?php if ($isGraded && !empty($questions)): ?>
<!-- Graded: question-by-question review -->
<div style="margin-top:6px;font-size:14px;font-weight:700;color:#1e293b;padding:0 4px;margin-bottom:12px">
    📋 კითხვების მიმოხილვა
</div>

<?php foreach ($questions as $i => $q):
    $qid        = (int)$q['id'];
    $opts       = $allOptions[$qid] ?? [];
    $userIds    = $userAnswers[$qid] ?? [];
    $correctIds = array_column(array_filter($opts, fn($o) => (int)$o['is_correct']), 'id');
    $correctIds = array_map('intval', $correctIds);
    $userIds    = array_map('intval', $userIds);
    sort($correctIds); sort($userIds);
    $isCorrect  = $correctIds === $userIds;
?>
<div class="gqz-question">
    <div class="gqz-q-num">
        <?= $isCorrect ? '✓' : '✕' ?>
        კითხვა <?= $i + 1 ?> — <?= $isCorrect ? '<span style="color:#10b981">სწორი</span>' : '<span style="color:#ef4444">არასწორი</span>' ?>
    </div>
    <div class="gqz-q-text"><?= $h((string)$q['question']) ?></div>

    <div class="gqz-opts">
        <?php foreach ($opts as $opt):
            $oid       = (int)$opt['id'];
            $isCorr    = in_array($oid, $correctIds, true);
            $wasChosen = in_array($oid, $userIds,    true);
            $cls = '';
            if ($isCorr)                    $cls = 'gqz-correct';
            elseif ($wasChosen && !$isCorr) $cls = 'gqz-wrong';
        ?>
        <div class="gqz-opt <?= $cls ?>" style="cursor:default">
            <span style="font-size:16px;flex-shrink:0">
                <?php if ($isCorr):   ?>✓
                <?php elseif ($wasChosen): ?>✕
                <?php else:            ?>◦
                <?php endif ?>
            </span>
            <span class="gqz-opt-text"><?= $h((string)$opt['option_text']) ?></span>
            <?php if ($isCorr): ?>
            <span style="font-size:11px;color:#10b981;margin-left:auto;flex-shrink:0;font-weight:700">სწორი</span>
            <?php endif ?>
        </div>
        <?php endforeach ?>
    </div>
</div>
<?php endforeach ?>

<?php elseif (!$isGraded && !empty($pollResults)): ?>
<!-- Poll: aggregate results -->
<div style="margin-top:6px;font-size:14px;font-weight:700;color:#1e293b;padding:0 4px;margin-bottom:12px">
    📊 შედეგები (ყველა მონაწილე)
</div>

<?php foreach ($questions as $q):
    $qid     = (int)$q['id'];
    $optRows = $pollResults[$qid] ?? [];
    $totalSel = array_sum(array_column($optRows, 'selections'));
    $userIds  = array_map('intval', $userAnswers[$qid] ?? []);
?>
<div class="gqz-question">
    <div class="gqz-q-text"><?= $h((string)$q['question']) ?></div>
    <?php foreach ($optRows as $opt):
        $sel  = (int)$opt['selections'];
        $pctO = $totalSel > 0 ? round($sel / $totalSel * 100) : 0;
        $mine = in_array((int)$opt['option_id'], $userIds, true);
    ?>
    <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:5px">
            <span style="<?= $mine ? 'font-weight:700;color:#7c3aed' : '' ?>">
                <?= $mine ? '▶ ' : '' ?><?= $h((string)$opt['option_text']) ?>
                <?= $mine ? ' <span style="font-size:11px">(შენი)</span>' : '' ?>
            </span>
            <span style="font-weight:700;color:#7c3aed"><?= $pctO ?>%</span>
        </div>
        <div class="gqz-bar">
            <div class="gqz-bar-fill" style="width:<?= $pctO ?>%;<?= $mine ? 'background:linear-gradient(90deg,#6d28d9,#7c3aed)' : '' ?>"></div>
        </div>
    </div>
    <?php endforeach ?>
</div>
<?php endforeach ?>
<?php endif ?>

<!-- Back link -->
<div style="text-align:center;padding:12px 0">
    <a href="<?= $h($base . '/') ?>" class="gqz-btn-ghost" style="font-size:13px">← მთავარ გვერდზე</a>
</div>
