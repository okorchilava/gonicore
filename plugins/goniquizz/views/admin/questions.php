<?php
$pageTitle     = 'GoniQuizz — კითხვები: ' . e((string)$quiz['title']);
$activeNav     = 'goniquizz-quizzes';
$topbarActions =
    '<a href="' . e($base) . '/manage/goniquizz" class="btn btn-ghost" style="font-size:13px">← Quizzes</a> '
  . '<a href="' . e($base) . '/manage/goniquizz/questions/form?quiz_id=' . (int)$quiz['id']
  . '" class="btn btn-primary" style="font-size:13px">+ ახალი კითხვა</a>';
?>
<style>
.gqz-q-type{font-size:11px;font-weight:700;background:#ede9fe;color:#6d28d9;border-radius:20px;padding:2px 9px}
.gqz-q-multi{background:#dbeafe;color:#1d4ed8}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:16px">✓ კითხვა შენახულია.</div>
<?php endif ?>

<!-- Quiz header -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px">
        <div style="flex:1">
            <div style="font-weight:800;font-size:16px"><?= e((string)$quiz['title']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
                <?= $quiz['type'] === 'poll' ? '📊 Poll' : '🎯 Graded Quiz' ?>
                · Slug: <code style="font-size:11px;background:var(--border);padding:1px 5px;border-radius:4px"><?= e((string)$quiz['slug']) ?></code>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="<?= e($base) ?>/manage/goniquizz/quizzes/form?id=<?= (int)$quiz['id'] ?>"
               class="btn btn-ghost" style="font-size:13px">✏ Quiz-ის მონაცემები</a>
            <a href="<?= e($base) ?>/manage/goniquizz/results?quiz_id=<?= (int)$quiz['id'] ?>"
               class="btn btn-ghost" style="font-size:13px">📊 შედეგები</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>კითხვები <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($questions) ?>)</span></h3>
        <a href="<?= e($base) ?>/manage/goniquizz/questions/form?quiz_id=<?= (int)$quiz['id'] ?>"
           class="btn btn-ghost" style="font-size:13px">+ კითხვის დამატება</a>
    </div>

    <?php if (empty($questions)): ?>
    <div class="empty">
        <div class="empty-icon">❓</div>
        <h3>კითხვა არ არის</h3>
        <p>Quiz-ისთვის კითხვები ჯერ არ დაგიმატებია.</p>
        <a href="<?= e($base) ?>/manage/goniquizz/questions/form?quiz_id=<?= (int)$quiz['id'] ?>"
           class="btn btn-primary">პირველი კითხვის დამატება</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th style="width:42px">#</th>
            <th>კითხვა</th>
            <th style="text-align:center">ტიპი</th>
            <th style="text-align:center">ვარიანტები</th>
            <th style="text-align:center">Sort</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($questions as $i => $q): ?>
        <tr>
            <td style="font-weight:700;color:var(--muted)"><?= $i + 1 ?></td>
            <td>
                <div style="font-size:13.5px;font-weight:600;max-width:340px;line-height:1.4">
                    <?= e((string)$q['question']) ?>
                </div>
            </td>
            <td style="text-align:center">
                <?= $q['type'] === 'multiple'
                    ? '<span class="gqz-q-type gqz-q-multi">☑ Multiple</span>'
                    : '<span class="gqz-q-type">◉ Single</span>' ?>
            </td>
            <td style="text-align:center;font-weight:700;color:<?= (int)$q['option_count'] < 2 ? '#ef4444' : 'var(--accent)' ?>">
                <?= (int)$q['option_count'] ?>
                <?php if ((int)$q['option_count'] < 2): ?>
                <span style="font-size:11px;color:#ef4444;font-weight:400"> (min 2!)</span>
                <?php endif ?>
            </td>
            <td style="text-align:center;font-size:12.5px;color:var(--muted)"><?= (int)$q['sort_order'] ?></td>
            <td style="text-align:right">
                <div style="display:flex;gap:6px;justify-content:flex-end">
                    <a href="<?= e($base) ?>/manage/goniquizz/questions/form?id=<?= (int)$q['id'] ?>&quiz_id=<?= (int)$quiz['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px">✏</a>
                    <form method="POST" action="<?= e($base) ?>/manage/goniquizz/questions/delete"
                          onsubmit="return confirm('კითხვა წაიშლება ყველა ვარიანტთან ერთად?')">
                        <input type="hidden" name="id"      value="<?= (int)$q['id'] ?>">
                        <input type="hidden" name="quiz_id" value="<?= (int)$quiz['id'] ?>">
                        <button type="submit" class="btn btn-ghost"
                                style="font-size:12px;padding:4px 10px;color:#ef4444">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>
    <?php endif ?>
</div>

<?php if (!empty($questions)): ?>
<!-- Preview link -->
<div style="margin-top:14px;text-align:center">
    <a href="<?= e(GoniQuizz\GoniQuizzService::getBasePath()) ?>/goniquizz/play?slug=<?= urlencode((string)$quiz['slug']) ?>"
       target="_blank" class="btn btn-ghost" style="font-size:13px">
        🚀 Quiz-ის გადახედვა (ახალ ჩანართში) →
    </a>
</div>
<?php endif ?>
