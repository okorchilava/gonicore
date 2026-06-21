<?php
/**
 * Admin: operator inbox (live console).
 * Scope: $t, $base, $conversations, $active (array|null), $messages, $csrfToken, chrome.
 */
$pageTitle = $t('admin.inbox_title');
$activeId  = $active ? (int) $active['id'] : 0;
$lastId    = 0;
foreach ($messages as $m) { $lastId = max($lastId, (int) $m['id']); }

ob_start(); ?>
<a href="<?= e($base) ?>/manage/livechat/settings" class="topbar-btn ghost"><span class="material-symbols-outlined mi-sm">settings</span> <?= e($t('admin.tab_settings')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<style>
.lc-wrap{display:grid;grid-template-columns:320px 1fr;gap:0;border:1px solid var(--border);border-radius:14px;overflow:hidden;background:var(--surface);height:calc(100vh - 170px);min-height:480px}
.lc-list{border-right:1px solid var(--border);overflow-y:auto;background:var(--bg)}
.lc-item{display:block;padding:13px 16px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text)}
.lc-item:hover{background:var(--surface)}
.lc-item.active{background:var(--surface);box-shadow:inset 3px 0 0 var(--accent)}
.lc-item-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
.lc-item-name{font-weight:700;font-size:13.5px}
.lc-item-last{color:var(--muted);font-size:12px;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lc-dot{font-size:10px;font-weight:800;padding:2px 7px;border-radius:20px;text-transform:uppercase}
.lc-dot.waiting{background:#fef3c7;color:#d97706}
.lc-dot.operator{background:#dcfce7;color:#16a34a}
.lc-dot.ai{background:#e0e7ff;color:#4f46e5}
.lc-dot.closed{background:#f1f5f9;color:#64748b}
.lc-badge{background:#ef4444;color:#fff;font-size:11px;font-weight:700;border-radius:20px;padding:1px 7px}
.lc-main{display:flex;flex-direction:column;min-width:0}
.lc-main-head{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.lc-thread{flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:10px;background:var(--bg)}
.lc-m{max-width:74%;padding:9px 13px;border-radius:13px;font-size:13.5px;line-height:1.5;white-space:pre-wrap;word-wrap:break-word}
.lc-m.visitor{align-self:flex-start;background:#fff;border:1px solid var(--border)}
.lc-m.operator{align-self:flex-end;background:var(--accent);color:#fff}
.lc-m.ai{align-self:flex-start;background:#eef2ff;color:#3730a3}
.lc-m.system{align-self:center;background:transparent;color:var(--muted);font-size:12px}
.lc-reply{border-top:1px solid var(--border);padding:12px;display:flex;gap:8px;background:var(--surface)}
.lc-reply textarea{flex:1;border:1px solid var(--border);border-radius:10px;padding:10px 12px;font-family:inherit;font-size:14px;resize:none;max-height:100px;outline:none}
.lc-empty{display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);flex-direction:column;gap:10px}
</style>

<div class="lc-wrap">
  <div class="lc-list" id="lc-list">
    <?php if (empty($conversations)): ?>
      <div style="padding:30px 16px;text-align:center;color:var(--muted)"><?= e($t('admin.no_chats')) ?></div>
    <?php else: foreach ($conversations as $c): ?>
      <a href="<?= e($base) ?>/manage/livechat?cid=<?= (int) $c['id'] ?>" class="lc-item <?= (int) $c['id'] === $activeId ? 'active' : '' ?>">
        <div class="lc-item-top">
          <span class="lc-item-name"><?= e((string) ($c['visitor_name'] ?: ('#' . $c['id']))) ?></span>
          <span class="lc-dot <?= e((string) $c['status']) ?>"><?= e((string) $c['status']) ?></span>
        </div>
        <div class="lc-item-last">
          <?php if ((int) ($c['unread'] ?? 0) > 0): ?><span class="lc-badge"><?= (int) $c['unread'] ?></span> <?php endif ?>
          <?= e(mb_substr((string) ($c['last_body'] ?? ''), 0, 60)) ?>
        </div>
      </a>
    <?php endforeach; endif ?>
  </div>

  <div class="lc-main">
    <?php if ($active === null): ?>
      <div class="lc-empty">
        <span class="material-symbols-outlined" style="font-size:48px;opacity:.5">forum</span>
        <p><?= e($t('admin.pick_chat')) ?></p>
      </div>
    <?php else: ?>
      <div class="lc-main-head">
        <div>
          <strong><?= e((string) ($active['visitor_name'] ?: ('#' . $active['id']))) ?></strong>
          <span class="lc-dot <?= e((string) $active['status']) ?>" id="lc-status"><?= e((string) $active['status']) ?></span>
          <?php if ((string) ($active['summary'] ?? '') !== ''): ?>
            <div style="color:var(--muted);font-size:12.5px;margin-top:4px"><?= e($t('admin.request')) ?>: <?= e((string) $active['summary']) ?></div>
          <?php endif ?>
        </div>
        <div style="display:flex;gap:8px">
          <?php if ((string) $active['status'] !== 'operator'): ?>
          <form method="post" action="<?= e($base) ?>/manage/livechat/takeover" style="display:inline">
            <input type="hidden" name="cid" value="<?= (int) $active['id'] ?>">
            <button type="submit" class="btn btn-primary" style="padding:6px 12px"><span class="material-symbols-outlined mi-sm">support_agent</span> <?= e($t('admin.take_over')) ?></button>
          </form>
          <?php endif ?>
          <form method="post" action="<?= e($base) ?>/manage/livechat/close" style="display:inline">
            <input type="hidden" name="cid" value="<?= (int) $active['id'] ?>">
            <button type="button" class="btn btn-ghost" style="padding:6px 12px"
              onclick="gcConfirm(this,<?= e(json_encode($t('admin.close_chat'))) ?>,<?= e(json_encode($t('admin.close_confirm'))) ?>,<?= e(json_encode($t('admin.close_chat'))) ?>)">
              <span class="material-symbols-outlined mi-sm">check_circle</span> <?= e($t('admin.close_chat')) ?>
            </button>
          </form>
        </div>
      </div>

      <div class="lc-thread" id="lc-thread">
        <?php foreach ($messages as $m): ?>
          <div class="lc-m <?= e((string) $m['sender']) ?>"><?= nl2br(e((string) $m['body'])) ?></div>
        <?php endforeach ?>
      </div>

      <form class="lc-reply" id="lc-reply" method="post" action="<?= e($base) ?>/manage/livechat/reply">
        <input type="hidden" name="cid" value="<?= (int) $active['id'] ?>">
        <textarea name="body" id="lc-body" rows="1" placeholder="<?= e($t('admin.reply_ph')) ?>" required></textarea>
        <button type="submit" class="btn btn-primary" style="padding:0 16px"><span class="material-symbols-outlined mi-sm">send</span></button>
      </form>
    <?php endif ?>
  </div>
</div>

<script>
(function(){
  var cfg={poll:<?= json_encode($base . '/manage/livechat/poll') ?>,base:<?= json_encode($base) ?>,cid:<?= (int) $activeId ?>,csrf:<?= json_encode((string) $csrfToken) ?>};
  var lastId=<?= (int) $lastId ?>, thread=document.getElementById('lc-thread'), list=document.getElementById('lc-list');
  function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
  function addMsg(m){if(!thread)return;var el=document.createElement('div');el.className='lc-m '+(m.sender||'visitor');el.innerHTML=esc(m.body).replace(/\n/g,'<br>');thread.appendChild(el);thread.scrollTop=thread.scrollHeight;}
  function rebuildList(items){
    if(!list||!items)return;
    list.innerHTML=items.map(function(c){
      var active=c.id===cfg.cid?' active':'';
      var badge=c.unread>0?'<span class="lc-badge">'+c.unread+'</span> ':'';
      return '<a href="'+cfg.base+'/manage/livechat?cid='+c.id+'" class="lc-item'+active+'">'
        +'<div class="lc-item-top"><span class="lc-item-name">'+esc(c.name)+'</span>'
        +'<span class="lc-dot '+c.status+'">'+c.status+'</span></div>'
        +'<div class="lc-item-last">'+badge+esc(c.last)+'</div></a>';
    }).join('');
  }
  function poll(){
    fetch(cfg.poll+'?cid='+cfg.cid+'&after='+lastId,{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();}).then(function(d){
        if(!d||!d.ok)return;
        (d.messages||[]).forEach(function(m){addMsg(m);if(m.id>lastId)lastId=m.id;});
        rebuildList(d.inbox);
        var st=document.getElementById('lc-status'); if(st&&d.status){st.textContent=d.status;st.className='lc-dot '+d.status;}
      }).catch(function(){});
  }
  if(thread)thread.scrollTop=thread.scrollHeight;
  setInterval(poll,4000);

  var rf=document.getElementById('lc-reply'), bodyEl=document.getElementById('lc-body');
  if(bodyEl){bodyEl.addEventListener('input',function(){bodyEl.style.height='auto';bodyEl.style.height=Math.min(100,bodyEl.scrollHeight)+'px';});
    bodyEl.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();rf.requestSubmit();}});}
  if(rf){rf.addEventListener('submit',function(e){
    e.preventDefault();
    var v=bodyEl.value.trim(); if(!v)return;
    var fd=new FormData(rf); fd.append('_csrf',cfg.csrf);
    fetch(rf.action,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd})
      .then(function(r){return r.json();}).then(function(d){ bodyEl.value='';bodyEl.style.height='auto'; poll(); })
      .catch(function(){});
  });}
})();
</script>
