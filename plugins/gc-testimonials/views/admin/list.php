<?php
/**
 * Admin: reviews list.
 * Scope: $t (plugin translator), $base, $items, $pendingCount, $csrfToken, layout chrome.
 */
$pageTitle = $t('admin.title');

ob_start(); ?>
<a href="<?= e($base) ?>/manage/testimonials?action=add" class="topbar-btn"><span class="material-symbols-outlined mi-sm">add</span> <?= e($t('admin.add')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<style>
.gct-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.gct-tabs a{padding:9px 18px;border-radius:10px;font-weight:600;font-size:13.5px;color:var(--muted);text-decoration:none;border:1px solid var(--border);background:var(--surface)}
.gct-tabs a:hover{color:var(--text)}
.gct-tabs a.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.gct-stars{color:#f59e0b;letter-spacing:1px;font-size:14px;white-space:nowrap}
.gct-rev-text{max-width:420px;color:var(--muted);font-size:12.5px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.gct-row-user{display:flex;align-items:center;gap:10px}
</style>

<div class="gct-tabs">
  <a href="<?= e($base) ?>/manage/testimonials" class="active"><?= e($t('admin.tab_reviews')) ?></a>
  <a href="<?= e($base) ?>/manage/testimonials?tab=campaigns"><?= e($t('admin.tab_campaigns')) ?></a>
</div>

<div class="card" style="overflow:hidden">
  <div class="card-header">
    <h3><?= e($t('admin.reviews')) ?>
      <?php if ($pendingCount > 0): ?>
        <span class="badge draft" style="margin-left:6px"><?= (int) $pendingCount ?> <?= e($t('admin.pending')) ?></span>
      <?php endif ?>
    </h3>
  </div>

  <?php if (empty($items)): ?>
    <div class="empty">
      <div class="empty-icon material-symbols-outlined" style="font-size:42px">reviews</div>
      <h3><?= e($t('admin.empty_title')) ?></h3>
      <p style="color:var(--muted);margin-bottom:16px"><?= e($t('admin.empty_text')) ?></p>
      <a href="<?= e($base) ?>/manage/testimonials?action=add" class="btn btn-primary"><span class="material-symbols-outlined mi-sm">add</span> <?= e($t('admin.add')) ?></a>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table" style="width:100%;border-collapse:collapse;font-size:13.5px">
      <thead>
        <tr>
          <th style="text-align:left;padding:12px 16px"><?= e($t('admin.col_author')) ?></th>
          <th style="text-align:left;padding:12px 16px"><?= e($t('admin.col_review')) ?></th>
          <th style="text-align:left;padding:12px 16px"><?= e($t('admin.col_campaign')) ?></th>
          <th style="text-align:left;padding:12px 16px"><?= e($t('admin.col_rating')) ?></th>
          <th style="text-align:left;padding:12px 16px"><?= e($t('admin.col_status')) ?></th>
          <th style="text-align:right;padding:12px 16px"><?= e($t('admin.col_actions')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $row): ?>
        <tr style="border-top:1px solid var(--border)">
          <td style="padding:12px 16px">
            <div class="gct-row-user">
              <?= \GCTestimonials\TestimonialsService::avatar((string) $row['client_name'], '34px') ?>
              <span style="font-weight:600"><?= e((string) $row['client_name']) ?></span>
            </div>
          </td>
          <td style="padding:12px 16px"><div class="gct-rev-text"><?= e((string) $row['testimonial_text']) ?></div></td>
          <td style="padding:12px 16px"><span class="badge archived"><?= e((string) ($row['campaign_name'] ?? $t('admin.general'))) ?></span></td>
          <td style="padding:12px 16px"><span class="gct-stars"><?= e(\GCTestimonials\TestimonialsService::stars((int) $row['rating'])) ?></span></td>
          <td style="padding:12px 16px">
            <?php if ((int) $row['is_public'] === 1): ?>
              <span class="badge published"><?= e($t('admin.live')) ?></span>
            <?php else: ?>
              <span class="badge draft"><?= e($t('admin.pending')) ?></span>
            <?php endif ?>
          </td>
          <td style="padding:12px 16px;text-align:right;white-space:nowrap">
            <form method="post" action="<?= e($base) ?>/manage/testimonials/toggle" style="display:inline">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button type="submit" class="btn btn-ghost" style="padding:5px 9px" title="<?= e((int) $row['is_public'] === 1 ? $t('admin.hide') : $t('admin.publish')) ?>">
                <span class="material-symbols-outlined mi-sm"><?= (int) $row['is_public'] === 1 ? 'visibility_off' : 'visibility' ?></span>
              </button>
            </form>
            <a href="<?= e($base) ?>/manage/testimonials?action=edit&id=<?= (int) $row['id'] ?>" class="btn btn-ghost" style="padding:5px 9px" title="<?= e($t('admin.edit')) ?>"><span class="material-symbols-outlined mi-sm">edit</span></a>
            <form method="post" action="<?= e($base) ?>/manage/testimonials/delete" style="display:inline">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button type="button" class="btn btn-danger" style="padding:5px 9px" title="<?= e($t('admin.delete')) ?>"
                onclick="gcConfirm(this,<?= e(json_encode($t('admin.delete'))) ?>,<?= e(json_encode($t('admin.confirm_delete'))) ?>,<?= e(json_encode($t('admin.delete'))) ?>)">
                <span class="material-symbols-outlined mi-sm">delete</span>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>
</div>
