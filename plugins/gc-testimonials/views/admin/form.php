<?php
/**
 * Admin: add / edit review.
 * Scope: $t, $base, $edit (array|null), $campaigns, $csrfToken, layout chrome.
 */
$isEdit    = is_array($edit ?? null);
$pageTitle = $isEdit ? $t('admin.edit_title') : $t('admin.add_title');

$v = [
    'id'               => (int) ($edit['id'] ?? 0),
    'client_name'      => (string) ($edit['client_name'] ?? ''),
    'client_role'      => (string) ($edit['client_role'] ?? ''),
    'campaign_id'      => (int) ($edit['campaign_id'] ?? 0),
    'testimonial_text' => (string) ($edit['testimonial_text'] ?? ''),
    'rating'           => (int) ($edit['rating'] ?? 5),
    'is_public'        => $isEdit ? (int) ($edit['is_public'] ?? 0) : 1,
];

ob_start(); ?>
<a href="<?= e($base) ?>/manage/testimonials" class="topbar-btn ghost"><span class="material-symbols-outlined mi-sm">arrow_back</span> <?= e($t('admin.back')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<form method="post" action="<?= e($base) ?>/manage/testimonials/save" style="max-width:760px">
  <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
  <div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:18px">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <label class="form-label"><?= e($t('admin.f_name')) ?> *</label>
          <input type="text" name="client_name" class="form-input" value="<?= e($v['client_name']) ?>" required maxlength="240">
        </div>
        <div>
          <label class="form-label"><?= e($t('admin.f_role')) ?></label>
          <input type="text" name="client_role" class="form-input" value="<?= e($v['client_role']) ?>" maxlength="240" placeholder="<?= e($t('admin.f_role_ph')) ?>">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <label class="form-label"><?= e($t('admin.f_campaign')) ?></label>
          <select name="campaign_id" class="form-input">
            <option value="0"><?= e($t('admin.f_general')) ?></option>
            <?php foreach ($campaigns as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= $v['campaign_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div>
          <label class="form-label"><?= e($t('admin.f_rating')) ?></label>
          <select name="rating" class="form-input">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <option value="<?= $i ?>" <?= $v['rating'] === $i ? 'selected' : '' ?>><?= $i ?> ★</option>
            <?php endfor ?>
          </select>
        </div>
      </div>

      <div>
        <label class="form-label"><?= e($t('admin.f_text')) ?> *</label>
        <textarea name="testimonial_text" class="form-input" rows="5" required maxlength="2000"><?= e($v['testimonial_text']) ?></textarea>
      </div>

      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600">
        <input type="checkbox" name="is_public" value="1" <?= $v['is_public'] === 1 ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--accent)">
        <?= e($t('admin.f_public')) ?>
      </label>
    </div>
    <div class="card-header" style="border-top:1px solid var(--border);border-bottom:none;justify-content:flex-end">
      <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined mi-sm">save</span> <?= e($t('admin.save')) ?></button>
    </div>
  </div>
</form>
