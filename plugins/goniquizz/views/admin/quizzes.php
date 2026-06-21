<?php
$pageTitle     = 'GoniQuizz — Quizzes';
$activeNav     = 'goniquizz-quizzes';
$topbarActions = '<a href="' . e($base) . '/manage/goniquizz/quizzes/form" class="btn btn-primary" style="font-size:13px">+ ახალი Quiz</a>';
?>
<style>
.gqz-stat{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;padding:16px 20px}
.gqz-stat-val{font-size:26px;font-weight:800;line-height:1}
.gqz-stat-lbl{font-size:12px;color:var(--muted);margin-top:3px}
.gqz-type-graded{font-size:11px;font-weight:700;background:#ede9fe;color:#6d28d9;border-radius:20px;padding:2px 9px}
.gqz-type-poll{font-size:11px;font-weight:700;background:#dbeafe;color:#1d4ed8;border-radius:20px;padding:2px 9px}
.gqz-on{font-size:11px;font-weight:700;background:#d1fae5;color:#065f46;border-radius:20px;padding:2px 9px}
.gqz-off{font-size:11px;font-weight:700;background:#f1f5f9;color:#94a3b8;border-radius:20px;padding:2px 9px}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:20px">✓ Quiz შენახულია.</div>
<?php endif ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
    <div class="gqz-stat"><div class="gqz-stat-val"><?= (int)($stats['total_quizzes'] ?? 0) ?></div><div class="gqz-stat-lbl">📋 სულ Quizzes</div></div>
    <div class="gqz-stat"><div class="gqz-stat-val"><?= (int)($stats['active_quizzes'] ?? 0) ?></div><div class="gqz-stat-lbl">✅ Active</div></div>
    <div class="gqz-stat"><div class="gqz-stat-val"><?= (int)($stats['total_questions'] ?? 0) ?></div><div class="gqz-stat-lbl">❓ კითხვები</div></div>
    <div class="gqz-stat"><div class="gqz-stat-val"><?= number_format((int)($stats['total_attempts'] ?? 0)) ?></div><div class="gqz-stat-lbl">🎯 Attempts</div></div>
</div>

<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>Quizzes</h3>
        <a href="<?= e($base) ?>/manage/goniquizz/quizzes/form" class="btn btn-ghost" style="font-size:13px">+ ახალი Quiz</a>
    </div>

    <?php if (empty($quizzes)): ?>
    <div class="empty">
        <div class="empty-icon">🧠</div>
        <h3>Quiz არ არის</h3>
        <p>შექმენი პირველი Quiz ან Poll.</p>
        <a href="<?= e($base) ?>/manage/goniquizz/quizzes/form" class="btn btn-primary">Quiz-ის შექმნა</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>Quiz</th>
            <th>Slug</th>
            <th style="text-align:center">ტიპი</th>
            <th style="text-align:center">კითხვები</th>
            <th style="text-align:center">Attempts</th>
            <th style="text-align:center">Results</th>
            <th style="text-align:center">Status</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($quizzes as $q): ?>
        <tr>
            <td>
                <div style="font-weight:700;font-size:14px"><?= e((string)$q['title']) ?></div>
                <?php if ($q['description']): ?>
                <div style="font-size:12px;color:var(--muted);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e((string)$q['description']) ?></div>
                <?php endif ?>
            </td>
            <td><code style="font-size:12px;background:var(--border);padding:2px 7px;border-radius:5px"><?= e((string)$q['slug']) ?></code></td>
            <td style="text-align:center">
                <?= $q['type'] === 'poll'
                    ? '<span class="gqz-type-poll">📊 Poll</span>'
                    : '<span class="gqz-type-graded">🎯 Graded</span>' ?>
            </td>
            <td style="text-align:center">
                <a href="<?= e($base) ?>/manage/goniquizz/questions?quiz_id=<?= (int)$q['id'] ?>"
                   style="font-weight:700;color:var(--accent)"><?= (int)$q['question_count'] ?></a>
            </td>
            <td style="text-align:center">
                <a href="<?= e($base) ?>/manage/goniquizz/results?quiz_id=<?= (int)$q['id'] ?>"
                   style="font-weight:700;color:var(--accent)"><?= number_format((int)$q['attempt_count']) ?></a>
            </td>
            <td style="text-align:center;font-size:12px">
                <?= $q['show_results'] ? '👁 ჩართული' : '<span style="color:var(--muted)">—</span>' ?>
            </td>
            <td style="text-align:center">
                <form method="POST" action="<?= e($base) ?>/manage/goniquizz/quizzes/toggle" style="display:inline">
                    <input type="hidden" name="id"     value="<?= (int)$q['id'] ?>">
                    <input type="hidden" name="active" value="<?= (int)$q['active'] ?>">
                    <button type="submit" class="btn btn-ghost" style="padding:3px 8px;font-size:12px;border-radius:20px">
                        <?= $q['active'] ? '<span class="gqz-on">✓ Active</span>' : '<span class="gqz-off">Off</span>' ?>
                    </button>
                </form>
            </td>
            <td style="text-align:right">
                <div style="display:flex;gap:6px;justify-content:flex-end">
                    <a href="<?= e($base) ?>/manage/goniquizz/questions?quiz_id=<?= (int)$q['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="კითხვები">❓</a>
                    <a href="<?= e($base) ?>/manage/goniquizz/results?quiz_id=<?= (int)$q['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="შედეგები">📊</a>
                    <a href="<?= e($base) ?>/manage/goniquizz/quizzes/form?id=<?= (int)$q['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="რედაქტირება">✏</a>
                    <form method="POST" action="<?= e($base) ?>/manage/goniquizz/quizzes/delete"
                          onsubmit="return confirm('Quiz წაიშლება ყველა კითხვითა და შედეგით. გაგრძელება?')">
                        <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
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

<!-- Usage -->
<div class="card" style="margin-top:16px">
    <div class="card-header"><h3>გამოყენება თემებში</h3></div>
    <div class="card-body" style="font-size:13px;color:var(--muted)">
        <pre style="background:var(--bg-2,rgba(0,0,0,.04));border:1px solid var(--border);border-radius:8px;padding:12px 16px;font-size:13px;overflow-x:auto"><?= e("<?= goniquizz('quiz-slug') ?>\n<?= goniquizz('quiz-slug', 'გავლა →') ?>   // custom label") ?></pre>
        <div style="margin-top:10px">🔗 პირდაპირი ბმული: <code style="font-size:11px;background:var(--border);padding:1px 6px;border-radius:4px">/goniquizz/play?slug={slug}</code></div>
    </div>
</div>
