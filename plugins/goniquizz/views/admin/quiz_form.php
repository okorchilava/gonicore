<?php
$pageTitle     = $isEdit ? 'GoniQuizz — Quiz რედაქტირება' : 'GoniQuizz — ახალი Quiz';
$activeNav     = $isEdit ? 'goniquizz-quizzes' : 'goniquizz-new';
$topbarActions = '<a href="' . e($base) . '/manage/goniquizz" class="btn btn-ghost" style="font-size:13px">← Quizzes</a>';

$q = $quiz ?? [];
$v = static fn(string $k, string $d = '') => e((string)($q[$k] ?? $d));
?>
<style>
.gqz-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.gqz-toggle input{opacity:0;width:0;height:0;position:absolute}
.gqz-slider{display:inline-block;width:48px;height:26px;background:var(--border);border-radius:26px;transition:.25s;position:relative}
.gqz-slider:before{position:absolute;content:'';height:20px;width:20px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
.gqz-toggle input:checked~.gqz-slider{background:#7c3aed}
.gqz-toggle input:checked~.gqz-slider:before{transform:translateX(22px)}
.gqz-toggle-row{display:flex;align-items:center;justify-content:space-between;gap:16px;background:var(--bg-2,rgba(0,0,0,.03));border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:14px}
.gqz-type-card{flex:1;border:2px solid var(--border);border-radius:10px;padding:14px 16px;cursor:pointer;display:flex;align-items:flex-start;gap:10px;transition:border-color .15s}
.gqz-type-card:has(input:checked){border-color:#7c3aed;background:#7c3aed0d}
.gqz-type-card input{flex-shrink:0;accent-color:#7c3aed;margin-top:2px}
</style>

<div style="max-width:600px">
<div class="card">
    <div class="card-header"><h3><?= $isEdit ? 'Quiz-ის რედაქტირება' : 'ახალი Quiz' ?></h3></div>
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/goniquizz/quizzes/save">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)($q['id'] ?? 0) ?>">
            <?php endif ?>

            <!-- Title -->
            <div class="form-group">
                <label class="form-label" for="gqzTitle">Quiz სათაური <span style="color:#ef4444">*</span></label>
                <input type="text" id="gqzTitle" name="title" class="form-input"
                       value="<?= $v('title') ?>" required autofocus
                       placeholder="მაგ: PHP-ს ცოდნის ტესტი, ევროპის დედაქალაქები..."
                       oninput="gqzAutoSlug()">
            </div>

            <!-- Slug -->
            <div class="form-group">
                <label class="form-label" for="gqzSlug">Slug <span style="color:#ef4444">*</span></label>
                <input type="text" id="gqzSlug" name="slug" class="form-input"
                       value="<?= $v('slug') ?>" required
                       placeholder="php-knowledge-test"
                       pattern="[a-z0-9\-]+"
                       oninput="gqzSlugManual=true">
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    გამოიყენება: <code style="font-size:11px;background:var(--border);padding:1px 5px;border-radius:4px"><?= e("goniquizz('slug')") ?></code>
                    და <code style="font-size:11px;background:var(--border);padding:1px 5px;border-radius:4px">/goniquizz/play?slug=slug</code>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="gqzDesc">აღწერა (სურვილისამებრ)</label>
                <textarea id="gqzDesc" name="description" class="form-input" rows="2"
                          placeholder="Quiz-ის მოკლე აღწერა — ნაჩვენები მომხმარებლისთვის"><?= $v('description') ?></textarea>
            </div>

            <!-- Type -->
            <div class="form-group">
                <label class="form-label">Quiz ტიპი</label>
                <div style="display:flex;gap:10px">
                    <label class="gqz-type-card">
                        <input type="radio" name="type" value="graded"
                               <?= ($q['type'] ?? 'graded') === 'graded' ? 'checked' : '' ?>>
                        <div>
                            <div style="font-weight:700;font-size:14px">🎯 Graded Quiz</div>
                            <div style="font-size:12px;color:var(--muted);margin-top:3px">კითხვებს აქვს სწორი პასუხი. მომხმარებელი ქულას იგებს.</div>
                        </div>
                    </label>
                    <label class="gqz-type-card">
                        <input type="radio" name="type" value="poll"
                               <?= ($q['type'] ?? '') === 'poll' ? 'checked' : '' ?>>
                        <div>
                            <div style="font-weight:700;font-size:14px">📊 Poll / Survey</div>
                            <div style="font-size:12px;color:var(--muted);margin-top:3px">სწორი პასუხი არ არის. ნაჩვენებია აგრეგირებული %.</div>
                        </div>
                    </label>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:8px 0 18px">

            <!-- Show results toggle -->
            <div class="gqz-toggle-row">
                <div>
                    <div style="font-weight:700;font-size:13.5px">შედეგის ჩვენება</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px">Quiz-ის შემდეგ მომხმარებელს შედეგი/ქულა ეჩვენება</div>
                </div>
                <label class="gqz-toggle">
                    <input type="hidden"   name="show_results" id="gqzShowResH" value="<?= $v('show_results', '1') ?>">
                    <input type="checkbox" id="gqzShowResCb"
                           <?= ($q['show_results'] ?? '1') !== '0' ? 'checked' : '' ?>
                           onchange="document.getElementById('gqzShowResH').value=this.checked?'1':'0'">
                    <span class="gqz-slider"></span>
                </label>
            </div>

            <!-- Allow retake toggle -->
            <div class="gqz-toggle-row">
                <div>
                    <div style="font-weight:700;font-size:13.5px">გავლა ხელახლა</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px">გათიშვის შემთხვევაში Cookie ბლოკავს ხელახლა გავლას</div>
                </div>
                <label class="gqz-toggle">
                    <input type="hidden"   name="allow_retake" id="gqzRetakeH" value="<?= $v('allow_retake', '0') ?>">
                    <input type="checkbox" id="gqzRetakeCb"
                           <?= ($q['allow_retake'] ?? '0') === '1' ? 'checked' : '' ?>
                           onchange="document.getElementById('gqzRetakeH').value=this.checked?'1':'0'">
                    <span class="gqz-slider"></span>
                </label>
            </div>

            <!-- Active toggle -->
            <div class="gqz-toggle-row">
                <div>
                    <div style="font-weight:700;font-size:13.5px">Quiz აქტიურია</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px">გათიშვა Quiz-ს front-end-ზე დამალავს</div>
                </div>
                <label class="gqz-toggle">
                    <input type="hidden"   name="active" id="gqzActiveH" value="<?= $v('active', '1') ?>">
                    <input type="checkbox" id="gqzActiveCb"
                           <?= ($q['active'] ?? '1') !== '0' ? 'checked' : '' ?>
                           onchange="document.getElementById('gqzActiveH').value=this.checked?'1':'0'">
                    <span class="gqz-slider"></span>
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:6px">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? '💾 განახლება → კითხვებზე' : '+ შექმნა → კითხვები' ?>
                </button>
                <a href="<?= e($base) ?>/manage/goniquizz" class="btn btn-ghost">გაუქმება</a>
                <?php if ($isEdit): ?>
                <form method="POST" action="<?= e($base) ?>/manage/goniquizz/quizzes/delete"
                      style="margin-left:auto"
                      onsubmit="return confirm('Quiz წაიშლება ყველა კითხვითა და შედეგით!')">
                    <input type="hidden" name="id" value="<?= (int)($q['id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 წაშლა</button>
                </form>
                <?php endif ?>
            </div>
        </form>
    </div>
</div>
</div>

<script>
var gqzSlugManual = <?= $isEdit ? 'true' : 'false' ?>;
function gqzAutoSlug() {
    if (gqzSlugManual) return;
    document.getElementById('gqzSlug').value = document.getElementById('gqzTitle').value
        .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}
document.getElementById('gqzSlug').addEventListener('input', function() {
    gqzSlugManual = true;
    this.value = this.value.toLowerCase().replace(/[^a-z0-9\-]+/g, '');
});
</script>
