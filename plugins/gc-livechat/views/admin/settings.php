<?php
/**
 * Admin: Live Chat settings.
 * Scope: $t, $base, $values, $providers, $defModels, $hasKey, $configured, $csrfToken, chrome.
 */
$pageTitle = $t('admin.settings_title');
$v = $values;

ob_start(); ?>
<a href="<?= e($base) ?>/manage/livechat" class="topbar-btn ghost"><span class="material-symbols-outlined mi-sm">forum</span> <?= e($t('admin.open_inbox')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<style>
.lc-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.lc-tabs a{padding:9px 18px;border-radius:10px;font-weight:600;font-size:13.5px;color:var(--muted);text-decoration:none;border:1px solid var(--border);background:var(--surface)}
.lc-tabs a.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.lc-hint{color:var(--muted);font-size:12.5px;margin-top:5px;line-height:1.5}
</style>

<div class="lc-tabs">
  <a href="<?= e($base) ?>/manage/livechat"><?= e($t('admin.tab_inbox')) ?></a>
  <a href="<?= e($base) ?>/manage/livechat/settings" class="active"><?= e($t('admin.tab_settings')) ?></a>
</div>

<form method="post" action="<?= e($base) ?>/manage/livechat/settings" style="max-width:860px;display:flex;flex-direction:column;gap:20px">

  <div class="card">
    <div class="card-header"><h3><?= e($t('admin.s_general')) ?></h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600">
        <input type="checkbox" name="livechat_enabled" value="1" <?= $v['livechat_enabled'] ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--accent)">
        <?= e($t('admin.s_enabled')) ?>
      </label>
      <div style="display:grid;grid-template-columns:1fr 160px;gap:16px">
        <div>
          <label class="form-label"><?= e($t('admin.s_title')) ?></label>
          <input type="text" name="livechat_title" class="form-input" value="<?= e($v['livechat_title']) ?>" placeholder="<?= e($t('front.title')) ?>">
        </div>
        <div>
          <label class="form-label"><?= e($t('admin.s_color')) ?></label>
          <input type="color" name="livechat_color" class="form-input" value="<?= e($v['livechat_color']) ?>" style="height:42px;padding:4px">
        </div>
      </div>
      <div>
        <label class="form-label"><?= e($t('admin.s_greeting')) ?></label>
        <input type="text" name="livechat_greeting" class="form-input" value="<?= e($v['livechat_greeting']) ?>" placeholder="<?= e($t('front.greeting')) ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3><?= e($t('admin.s_ai')) ?></h3>
      <span class="badge <?= $configured ? 'published' : 'draft' ?>"><?= e($configured ? $t('admin.s_active') : $t('admin.s_not_set')) ?></span>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <label class="form-label"><?= e($t('admin.s_provider')) ?></label>
          <select name="livechat_provider" id="lc-provider" class="form-input">
            <?php foreach ($providers as $key => $label): ?>
              <option value="<?= e($key) ?>" data-model="<?= e($defModels[$key] ?? '') ?>" <?= $v['livechat_provider'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div>
          <label class="form-label"><?= e($t('admin.s_model')) ?></label>
          <input type="text" name="livechat_model" id="lc-model" class="form-input" value="<?= e($v['livechat_model']) ?>" placeholder="<?= e($defModels[$v['livechat_provider']] ?? '') ?>">
        </div>
      </div>
      <div>
        <label class="form-label"><?= e($t('admin.s_apikey')) ?></label>
        <input type="password" name="livechat_api_key" class="form-input" autocomplete="new-password"
               placeholder="<?= $hasKey ? '•••••••••••• ' . e($t('admin.s_key_saved')) : e($t('admin.s_key_ph')) ?>">
        <div class="lc-hint"><?= e($t('admin.s_key_hint')) ?></div>
      </div>
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600">
        <input type="checkbox" name="livechat_use_site_content" value="1" <?= $v['livechat_use_site_content'] ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--accent)">
        <?= e($t('admin.s_use_content')) ?>
      </label>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= e($t('admin.s_knowledge')) ?></h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
      <div>
        <label class="form-label"><?= e($t('admin.s_instructions')) ?></label>
        <textarea name="livechat_system_prompt" class="form-input" rows="4" placeholder="<?= e($t('admin.s_instructions_ph')) ?>"><?= e($v['livechat_system_prompt']) ?></textarea>
        <div class="lc-hint"><?= e($t('admin.s_instructions_hint')) ?></div>
      </div>
      <div>
        <label class="form-label"><?= e($t('admin.s_faq')) ?></label>
        <textarea name="livechat_faq" class="form-input" rows="6" placeholder="<?= e($t('admin.s_faq_ph')) ?>"><?= e($v['livechat_faq']) ?></textarea>
      </div>
    </div>
    <div class="card-header" style="border-top:1px solid var(--border);border-bottom:none;justify-content:flex-end">
      <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined mi-sm">save</span> <?= e($t('admin.save')) ?></button>
    </div>
  </div>
</form>

<script>
(function(){
  var prov=document.getElementById('lc-provider'), model=document.getElementById('lc-model');
  if(prov&&model){prov.addEventListener('change',function(){
    var d=prov.options[prov.selectedIndex].getAttribute('data-model')||'';
    model.setAttribute('placeholder',d);
  });}
})();
</script>
