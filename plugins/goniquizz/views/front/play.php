<?php
// Variables available: $quiz, $questions, $allOptions
// This view is wrapped in GoniQuizzFrontController::page()
$base   = \GoniQuizz\GoniQuizzService::getBasePath();
$h      = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$total  = count($questions);
?>
<!-- Quiz header card -->
<div class="gqz-card">
    <h1 class="gqz-title"><?= $h((string)$quiz['title']) ?></h1>
    <?php if (trim((string)$quiz['description'])): ?>
    <p class="gqz-desc"><?= $h((string)$quiz['description']) ?></p>
    <?php endif ?>
    <div style="margin-top:12px;font-size:12.5px;color:#94a3b8;display:flex;gap:16px;flex-wrap:wrap">
        <span>❓ <?= $total ?> კითხვა</span>
        <span><?= $quiz['type'] === 'poll' ? '📊 Poll' : '🎯 Graded Quiz' ?></span>
        <?php if ($quiz['allow_retake']): ?>
        <span>🔄 ხელახლა გავლა ნებადართულია</span>
        <?php endif ?>
    </div>
</div>

<!-- Progress bar (filled by JS) -->
<div style="background:#e2e8f0;border-radius:8px;height:6px;margin-bottom:20px;overflow:hidden">
    <div id="gqz-progress" style="height:100%;background:linear-gradient(90deg,#7c3aed,#a855f7);border-radius:8px;width:0;transition:width .4s"></div>
</div>
<div id="gqz-progress-label" style="text-align:center;font-size:12px;color:#94a3b8;margin-top:-14px;margin-bottom:16px">
    0 / <?= $total ?> კითხვა შევსებული
</div>

<!-- Quiz form -->
<form method="POST" action="<?= $h($base . '/goniquizz/submit') ?>" id="gqzForm">
    <input type="hidden" name="quiz_id" value="<?= (int)$quiz['id'] ?>">

    <?php foreach ($questions as $i => $q):
        $qid  = (int)$q['id'];
        $opts = $allOptions[$qid] ?? [];
        $isMulti = $q['type'] === 'multiple';
    ?>
    <div class="gqz-question" data-qi="<?= $i ?>">
        <div class="gqz-q-num">კითხვა <?= $i + 1 ?> / <?= $total ?></div>
        <div class="gqz-q-text"><?= $h((string)$q['question']) ?></div>

        <div class="gqz-opts">
            <?php foreach ($opts as $opt): ?>
            <label class="gqz-opt">
                <input type="<?= $isMulti ? 'checkbox' : 'radio' ?>"
                       name="<?= $isMulti ? 'answers[' . $qid . '][]' : 'answers[' . $qid . ']' ?>"
                       value="<?= (int)$opt['id'] ?>"
                       onchange="gqzProgress()"
                       class="gqz-inp">
                <span class="gqz-opt-text"><?= $h((string)$opt['option_text']) ?></span>
            </label>
            <?php endforeach ?>
        </div>
    </div>
    <?php endforeach ?>

    <!-- Error message -->
    <div id="gqz-error" style="display:none;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#b91c1c;font-size:13.5px">
        ⚠ გთხოვ ყველა კითხვას უპასუხო.
    </div>

    <div style="display:flex;justify-content:center;padding:8px 0 4px">
        <button type="submit" class="gqz-btn" style="font-size:16px;padding:15px 40px" id="gqzSubmit">
            ✅ შედეგის ნახვა
        </button>
    </div>
</form>

<script>
var gqzTotal = <?= $total ?>;

function gqzProgress() {
    var filled = 0;
    var questions = document.querySelectorAll('.gqz-question');
    questions.forEach(function(q) {
        var checked = q.querySelectorAll('input:checked');
        if (checked.length > 0) filled++;
    });
    var pct = gqzTotal > 0 ? Math.round(filled / gqzTotal * 100) : 0;
    document.getElementById('gqz-progress').style.width = pct + '%';
    document.getElementById('gqz-progress-label').textContent = filled + ' / ' + gqzTotal + ' კითხვა შევსებული';
    document.getElementById('gqz-error').style.display = 'none';
}

document.getElementById('gqzForm').addEventListener('submit', function(e) {
    var ok = true;
    document.querySelectorAll('.gqz-question').forEach(function(q) {
        if (!q.querySelectorAll('input:checked').length) ok = false;
    });
    if (!ok) {
        e.preventDefault();
        document.getElementById('gqz-error').style.display = 'block';
        document.getElementById('gqz-error').scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        document.getElementById('gqzSubmit').disabled = true;
        document.getElementById('gqzSubmit').textContent = '⏳ ...';
    }
});

gqzProgress();
</script>
