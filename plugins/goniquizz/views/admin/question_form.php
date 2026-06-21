<?php
$pageTitle     = $isEdit ? 'GoniQuizz — კითხვის რედაქტირება' : 'GoniQuizz — ახალი კითხვა';
$activeNav     = 'goniquizz-quizzes';
$quizId        = (int)($quiz['id'] ?? $question['quiz_id'] ?? 0);
$topbarActions = '<a href="' . e($base) . '/manage/goniquizz/questions?quiz_id=' . $quizId . '" class="btn btn-ghost" style="font-size:13px">← კითხვები</a>';

$q    = $question ?? [];
$opts = $options ?? [];
$curType = (string)($q['type'] ?? 'single');
?>
<style>
.gqz-opt-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.gqz-opt-row .form-input{flex:1}
.gqz-opt-correct{display:flex;align-items:center;gap:5px;font-size:13px;white-space:nowrap;cursor:pointer;flex-shrink:0}
.gqz-opt-correct input{accent-color:#7c3aed;width:16px;height:16px}
.gqz-opt-remove{background:none;border:none;color:#ef4444;font-size:18px;cursor:pointer;padding:0 4px;flex-shrink:0;line-height:1}
.gqz-opt-remove:hover{color:#b91c1c}
.gqz-type-card{display:flex;align-items:flex-start;gap:10px;border:2px solid var(--border);border-radius:10px;padding:12px 14px;cursor:pointer;transition:border-color .15s;flex:1}
.gqz-type-card:has(input:checked){border-color:#7c3aed;background:#7c3aed0d}
.gqz-type-card input{flex-shrink:0;accent-color:#7c3aed;margin-top:2px}
</style>

<div style="max-width:640px">
<div class="card">
    <div class="card-header">
        <h3>
            <?= $isEdit ? 'კითხვის რედაქტირება' : 'ახალი კითხვა' ?>
            <?php if ($quiz): ?>
            <span style="font-size:12px;color:var(--muted);font-weight:400;margin-left:8px">→ <?= e((string)$quiz['title']) ?></span>
            <?php endif ?>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/goniquizz/questions/save">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id"      value="<?= (int)($q['id'] ?? 0) ?>">
            <?php endif ?>
            <input type="hidden" name="quiz_id" value="<?= $quizId ?>">

            <!-- Question text -->
            <div class="form-group">
                <label class="form-label" for="gqzQText">კითხვა <span style="color:#ef4444">*</span></label>
                <textarea id="gqzQText" name="question" class="form-input" rows="3"
                          required autofocus
                          placeholder="მაგ: რომელი ენა გამოიყენება PHP-ის backend-ისთვის?"><?= e((string)($q['question'] ?? '')) ?></textarea>
            </div>

            <!-- Type -->
            <div class="form-group">
                <label class="form-label">პასუხის ტიპი</label>
                <div style="display:flex;gap:10px">
                    <label class="gqz-type-card">
                        <input type="radio" name="type" value="single" id="gqzTypeSingle"
                               <?= $curType !== 'multiple' ? 'checked' : '' ?>
                               onchange="gqzTypeChange('single')">
                        <div>
                            <div style="font-weight:700;font-size:13.5px">◉ Single</div>
                            <div style="font-size:12px;color:var(--muted);margin-top:2px">ერთი სწორი — radio button</div>
                        </div>
                    </label>
                    <label class="gqz-type-card">
                        <input type="radio" name="type" value="multiple" id="gqzTypeMulti"
                               <?= $curType === 'multiple' ? 'checked' : '' ?>
                               onchange="gqzTypeChange('multiple')">
                        <div>
                            <div style="font-weight:700;font-size:13.5px">☑ Multiple</div>
                            <div style="font-size:12px;color:var(--muted);margin-top:2px">რამდენიმე სწორი — checkbox</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Sort order -->
            <div class="form-group" style="max-width:160px">
                <label class="form-label" for="gqzSort">რიგის ნომერი (sort)</label>
                <input type="number" id="gqzSort" name="sort_order" class="form-input"
                       value="<?= (int)($q['sort_order'] ?? 0) ?>" min="0" max="999">
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">პატარა = პირველი</div>
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:4px 0 20px">

            <!-- Options -->
            <div class="form-group">
                <label class="form-label">
                    პასუხის ვარიანტები
                    <span id="gqzCorrectHint" style="font-size:11.5px;color:var(--muted);font-weight:400;margin-left:6px">
                        <?= $curType === 'multiple' ? '(მოსამი სწორი ვარიანტი)' : '(ერთი სწორი ვარიანტი)' ?>
                    </span>
                </label>

                <div id="gqz-opts">
                    <?php foreach ($opts as $i => $opt): ?>
                    <div class="gqz-opt-row">
                        <input type="text" name="options[<?= $i ?>][text]" class="form-input"
                               value="<?= e((string)$opt['option_text']) ?>"
                               placeholder="ვარიანტი..." required>
                        <label class="gqz-opt-correct">
                            <input type="checkbox" name="options[<?= $i ?>][is_correct]" value="1"
                                   <?= $opt['is_correct'] ? 'checked' : '' ?>>
                            სწორი
                        </label>
                        <button type="button" class="gqz-opt-remove"
                                onclick="this.closest('.gqz-opt-row').remove()" title="წაშლა">✕</button>
                    </div>
                    <?php endforeach ?>
                </div>

                <button type="button" class="btn btn-ghost" style="font-size:13px;margin-top:8px"
                        onclick="gqzAddOpt()">
                    + ვარიანტის დამატება
                </button>
                <div style="font-size:11.5px;color:var(--muted);margin-top:6px">
                    ⚠ Graded quiz-ისთვის აუცილებლად მონიშნე სულ ცოტა ერთი „სწორი" ვარიანტი.
                    Poll-ისთვის checkbox-ი არ არის სავალდებულო.
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? '💾 განახლება' : '+ კითხვის შენახვა' ?>
                </button>
                <a href="<?= e($base) ?>/manage/goniquizz/questions?quiz_id=<?= $quizId ?>"
                   class="btn btn-ghost">გაუქმება</a>
                <?php if ($isEdit): ?>
                <form method="POST" action="<?= e($base) ?>/manage/goniquizz/questions/delete"
                      style="margin-left:auto"
                      onsubmit="return confirm('კითხვა წაიშლება ყველა ვარიანტთან ერთად?')">
                    <input type="hidden" name="id"      value="<?= (int)($q['id'] ?? 0) ?>">
                    <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                    <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 წაშლა</button>
                </form>
                <?php endif ?>
            </div>
        </form>
    </div>
</div>
</div>

<script>
var gqzOptIdx = <?= count($opts) ?>;

function gqzAddOpt() {
    var i = gqzOptIdx++;
    var d = document.createElement('div');
    d.className = 'gqz-opt-row';
    d.innerHTML =
        '<input type="text" name="options[' + i + '][text]" class="form-input" placeholder="ვარიანტი..." required>' +
        '<label class="gqz-opt-correct">' +
          '<input type="checkbox" name="options[' + i + '][is_correct]" value="1"> სწორი' +
        '</label>' +
        '<button type="button" class="gqz-opt-remove" onclick="this.closest(\'.gqz-opt-row\').remove()" title="წაშლა">✕</button>';
    document.getElementById('gqz-opts').appendChild(d);
    d.querySelector('input[type=text]').focus();
}

function gqzTypeChange(type) {
    var hint = document.getElementById('gqzCorrectHint');
    hint.textContent = type === 'multiple' ? '(რამდენიმე სწორი ვარიანტი)' : '(ერთი სწორი ვარიანტი)';
}

// Auto-add 4 blank options for new questions
<?php if (!$isEdit && empty($opts)): ?>
gqzAddOpt(); gqzAddOpt(); gqzAddOpt(); gqzAddOpt();
<?php endif ?>
</script>
