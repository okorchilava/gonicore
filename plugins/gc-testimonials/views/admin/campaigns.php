<?php
/**
 * Admin: campaigns (placements) + their shortcodes.
 * Scope: $t, $base, $campaigns, $csrfToken, layout chrome.
 */
$pageTitle = $t('admin.campaigns_title');
?>
<style>
.gct-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.gct-tabs a{padding:9px 18px;border-radius:10px;font-weight:600;font-size:13.5px;color:var(--muted);text-decoration:none;border:1px solid var(--border);background:var(--surface)}
.gct-tabs a:hover{color:var(--text)}
.gct-tabs a.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.gct-grid2{display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start}
@media(max-width:820px){.gct-grid2{grid-template-columns:1fr}}
.gct-camp{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin-bottom:12px}
.gct-camp-name{font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.gct-code{display:block;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:6px 10px;font-family:ui-monospace,Consolas,monospace;font-size:12px;color:var(--accent);margin-bottom:6px;cursor:pointer;word-break:break-all}
.gct-code:hover{border-color:var(--accent)}
</style>

<div class="gct-tabs">
  <a href="<?= e($base) ?>/manage/testimonials"><?= e($t('admin.tab_reviews')) ?></a>
  <a href="<?= e($base) ?>/manage/testimonials?tab=campaigns" class="active"><?= e($t('admin.tab_campaigns')) ?></a>
</div>

<div class="gct-grid2">
  <div class="card">
    <div class="card-header"><h3><?= e($t('admin.new_campaign')) ?></h3></div>
    <div class="card-body">
      <form method="post" action="<?= e($base) ?>/manage/testimonials/campaign/save">
        <label class="form-label"><?= e($t('admin.campaign_name')) ?> *</label>
        <input type="text" name="name" class="form-input" placeholder="<?= e($t('admin.campaign_ph')) ?>" required maxlength="240" style="margin-bottom:14px">
        <button type="submit" class="btn btn-primary" style="width:100%"><span class="material-symbols-outlined mi-sm">add</span> <?= e($t('admin.add_campaign')) ?></button>
      </form>
      <p style="color:var(--muted);font-size:12.5px;margin-top:14px;line-height:1.5"><?= e($t('admin.campaign_help')) ?></p>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= e($t('admin.existing_campaigns')) ?></h3></div>
    <div class="card-body">
      <?php if (empty($campaigns)): ?>
        <div class="empty" style="padding:30px">
          <div class="empty-icon material-symbols-outlined" style="font-size:38px">campaign</div>
          <p style="color:var(--muted)"><?= e($t('admin.no_campaigns')) ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($campaigns as $c): $slug = (string) $c['slug']; ?>
        <div class="gct-camp">
          <div class="gct-camp-name">
            <span class="material-symbols-outlined mi-sm" style="color:var(--muted)">sell</span>
            <span><?= e((string) $c['name']) ?></span>
            <form method="post" action="<?= e($base) ?>/manage/testimonials/campaign/delete" style="display:inline;margin-left:auto">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button type="button" class="btn btn-danger" style="padding:4px 8px"
                onclick="gcConfirm(this,<?= e(json_encode($t('admin.delete'))) ?>,<?= e(json_encode($t('admin.confirm_delete_campaign'))) ?>,<?= e(json_encode($t('admin.delete'))) ?>)">
                <span class="material-symbols-outlined mi-sm">delete</span>
              </button>
            </form>
          </div>
          <code class="gct-code" onclick="gctCopy(this)">[gc_testimonials id="<?= e($slug) ?>"]</code>
          <code class="gct-code" onclick="gctCopy(this)">[gc_testimonials_slider id="<?= e($slug) ?>"]</code>
          <code class="gct-code" onclick="gctCopy(this)">[gc_testimonial_form id="<?= e($slug) ?>"]</code>
        </div>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  </div>
</div>

<script>
function gctCopy(el){
  var txt = el.textContent;
  if (navigator.clipboard) navigator.clipboard.writeText(txt).then(function(){ gcToast && gcToast(<?= json_encode($t('admin.copied'), JSON_UNESCAPED_UNICODE) ?>, 'success'); });
}
</script>
