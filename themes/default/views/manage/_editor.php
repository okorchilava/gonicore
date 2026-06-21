<?php
/**
 * Shared rich-text editor partial.
 *
 * Required variables:
 *   $editorName    — textarea name attribute (e.g. 'content')
 *   $editorId      — textarea id attribute   (e.g. 'postContent')
 *   $editorValue   — current content string
 *   $editorHeight  — min-height CSS value    (default '420px')
 *   $base          — URL base path
 */
$editorHeight = $editorHeight ?? '420px';
?>
<style>
.gc-editor-wrap{border:1.5px solid var(--border);border-radius:10px;overflow:hidden;background:#fff;transition:border-color .15s}
.gc-editor-wrap:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(16,178,124,.08)}
.gc-editor-wrap.fullscreen{position:fixed;inset:0;z-index:9999;border-radius:0;border:none;display:flex;flex-direction:column}
.gc-editor-wrap.fullscreen .gc-editor-body{flex:1}
.gc-editor-wrap.fullscreen textarea{height:100%!important;min-height:unset!important}

/* Toolbar */
.gc-toolbar{display:flex;flex-wrap:wrap;gap:2px;padding:8px 10px;border-bottom:1.5px solid var(--border);background:#fafafa;align-items:center}
.gc-toolbar-sep{width:1px;height:20px;background:var(--border);margin:0 4px;flex-shrink:0}
.gc-tb{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:28px;padding:0 5px;border:none;border-radius:5px;background:transparent;cursor:pointer;font-size:13px;color:var(--text);transition:background .1s;font-family:inherit;gap:4px;white-space:nowrap}
.gc-tb:hover{background:var(--border)}
.gc-tb.active{background:rgba(16,178,124,.15);color:var(--accent)}
.gc-tb b{font-weight:800;font-size:14px}
.gc-tb i{font-style:italic;font-size:14px}
.gc-tb .gc-tb-lbl{font-size:11px;font-weight:600}
.gc-tb-heading{font-size:12px;font-weight:700}

/* Textarea */
.gc-editor-body{position:relative}
.gc-editor-ta{width:100%;border:none;outline:none;resize:vertical;padding:18px 20px;font-size:15px;font-family:'Fira Code','JetBrains Mono',monospace;line-height:1.8;color:var(--text);background:#fff;tab-size:2;min-height:<?= $editorHeight ?>}
.gc-editor-wrap.fullscreen .gc-editor-ta{resize:none}

/* Footer */
.gc-editor-footer{display:flex;align-items:center;justify-content:space-between;padding:6px 16px;border-top:1.5px solid var(--border);background:#fafafa;font-size:11.5px;color:var(--muted)}
.gc-editor-footer .gc-stats{display:flex;gap:14px}

/* Find bar */
.gc-find-bar{display:none;padding:6px 12px;border-top:1px solid var(--border);background:#fff;align-items:center;gap:8px}
.gc-find-bar.open{display:flex}
.gc-find-input{padding:5px 9px;border:1.5px solid var(--border);border-radius:6px;font-size:12.5px;font-family:inherit;outline:none;width:180px}
.gc-find-input:focus{border-color:var(--accent)}
.gc-find-btn{padding:4px 10px;border:1.5px solid var(--border);border-radius:5px;background:#fff;font-size:12px;cursor:pointer;font-family:inherit}
.gc-find-btn:hover{background:var(--bg)}
.gc-find-count{font-size:12px;color:var(--muted);min-width:60px}

/* Gallery modal grid */
.gc-gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;max-height:55vh;overflow-y:auto;padding:2px}
.gc-gallery-item{position:relative;border-radius:8px;overflow:hidden;border:2px solid transparent;cursor:pointer;transition:border-color .15s,transform .12s;aspect-ratio:1;background:var(--bg)}
.gc-gallery-item:hover{border-color:var(--accent);transform:scale(1.03)}
.gc-gallery-item img{width:100%;height:100%;object-fit:cover;display:block}
.gc-gallery-item .gc-gi-name{position:absolute;bottom:0;left:0;right:0;padding:3px 5px;background:rgba(0,0,0,.6);color:#fff;font-size:9px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.gc-gallery-item.gc-gi-file{display:flex;align-items:center;justify-content:center;flex-direction:column;gap:4px;font-size:11px;color:var(--muted);padding:8px}
.gc-gallery-item.gc-gi-file .gc-gi-icon{font-size:28px}
.gc-gallery-drop{border:2px dashed var(--border);border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:border-color .15s}
.gc-gallery-drop:hover,.gc-gallery-drop.dragover{border-color:var(--accent);background:#f0fdf4}
.gc-gallery-drop p{font-size:13px;color:var(--muted);margin-top:6px}
</style>

<div class="gc-editor-wrap" id="gc-editor-wrap-<?= $editorId ?>">

  <!-- ── Toolbar ─────────────────────────────────────── -->
  <div class="gc-toolbar" id="gc-tb-<?= $editorId ?>">

    <!-- Undo / Redo -->
    <button type="button" class="gc-tb" onclick="gcEd.undo('<?= $editorId ?>')" title="Undo (Ctrl+Z)">↩</button>
    <button type="button" class="gc-tb" onclick="gcEd.redo('<?= $editorId ?>')" title="Redo (Ctrl+Y)">↪</button>
    <span class="gc-toolbar-sep"></span>

    <!-- Headings -->
    <button type="button" class="gc-tb" onclick="gcEd.heading('<?= $editorId ?>',1)" title="Heading 1"><span class="gc-tb-heading">H1</span></button>
    <button type="button" class="gc-tb" onclick="gcEd.heading('<?= $editorId ?>',2)" title="Heading 2"><span class="gc-tb-heading">H2</span></button>
    <button type="button" class="gc-tb" onclick="gcEd.heading('<?= $editorId ?>',3)" title="Heading 3"><span class="gc-tb-heading">H3</span></button>
    <span class="gc-toolbar-sep"></span>

    <!-- Inline formatting -->
    <button type="button" class="gc-tb" id="gc-tb-bold-<?= $editorId ?>"   onclick="gcEd.inline('<?= $editorId ?>','**','**')"   title="Bold (Ctrl+B)"><b>B</b></button>
    <button type="button" class="gc-tb" id="gc-tb-italic-<?= $editorId ?>" onclick="gcEd.inline('<?= $editorId ?>','*','*')"     title="Italic (Ctrl+I)"><i>I</i></button>
    <button type="button" class="gc-tb" id="gc-tb-under-<?= $editorId ?>"  onclick="gcEd.inline('<?= $editorId ?>','<u>','</u>')" title="Underline (Ctrl+U)"><u>U</u></button>
    <button type="button" class="gc-tb" id="gc-tb-strike-<?= $editorId ?>" onclick="gcEd.inline('<?= $editorId ?>','~~','~~')"   title="Strikethrough"><s>S</s></button>
    <button type="button" class="gc-tb" id="gc-tb-code-<?= $editorId ?>"   onclick="gcEd.inline('<?= $editorId ?>','`','`')"     title="Inline code"><code style="font-size:11px">code</code></button>
    <span class="gc-toolbar-sep"></span>

    <!-- Lists -->
    <button type="button" class="gc-tb" onclick="gcEd.list('<?= $editorId ?>','ul')" title="Bullet list">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><circle cx="2" cy="4" r="1.5" fill="currentColor"/><rect x="5" y="3" width="9" height="2" rx="1" fill="currentColor"/><circle cx="2" cy="8" r="1.5" fill="currentColor"/><rect x="5" y="7" width="9" height="2" rx="1" fill="currentColor"/><circle cx="2" cy="12" r="1.5" fill="currentColor"/><rect x="5" y="11" width="9" height="2" rx="1" fill="currentColor"/></svg>
    </button>
    <button type="button" class="gc-tb" onclick="gcEd.list('<?= $editorId ?>','ol')" title="Numbered list">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><text x="0" y="5.5" font-size="5" font-family="monospace" fill="currentColor">1.</text><rect x="6" y="3" width="8" height="2" rx="1" fill="currentColor"/><text x="0" y="9.5" font-size="5" font-family="monospace" fill="currentColor">2.</text><rect x="6" y="7" width="8" height="2" rx="1" fill="currentColor"/><text x="0" y="13.5" font-size="5" font-family="monospace" fill="currentColor">3.</text><rect x="6" y="11" width="8" height="2" rx="1" fill="currentColor"/></svg>
    </button>
    <span class="gc-toolbar-sep"></span>

    <!-- Block elements -->
    <button type="button" class="gc-tb" onclick="gcEd.block('<?= $editorId ?>','quote')"   title="Blockquote"><span class="gc-tb-lbl">" Quote</span></button>
    <button type="button" class="gc-tb" onclick="gcEd.block('<?= $editorId ?>','codeblock')" title="Code block"><span class="gc-tb-lbl">```</span></button>
    <button type="button" class="gc-tb" onclick="gcEd.insertText('<?= $editorId ?>','\n---\n')" title="Horizontal rule"><span class="gc-tb-lbl">─ HR</span></button>
    <span class="gc-toolbar-sep"></span>

    <!-- Inserts -->
    <button type="button" class="gc-tb" onclick="gcEd.insertLink('<?= $editorId ?>')"  title="Insert link">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
    </button>
    <button type="button" class="gc-tb" onclick="gcEd.insertImageUrl('<?= $editorId ?>')" title="Insert image by URL">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
    </button>
    <button type="button" class="gc-tb" onclick="gcEd.insertTable('<?= $editorId ?>')" title="Insert table">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
    </button>
    <span class="gc-toolbar-sep"></span>

    <!-- Shortcode -->
    <button type="button" class="gc-tb" onclick="gcEd.insertShortcode('<?= $editorId ?>')" title="Insert shortcode"><span class="gc-tb-lbl">[/]</span></button>
    <span class="gc-toolbar-sep"></span>

    <!-- Gallery -->
    <button type="button" class="gc-tb" onclick="gcEd.openGallery('<?= $editorId ?>','<?= e($base) ?>')" title="Media Gallery" style="color:var(--accent);font-weight:600">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
      <span class="gc-tb-lbl">Gallery</span>
    </button>
    <span class="gc-toolbar-sep"></span>

    <!-- Utilities -->
    <button type="button" class="gc-tb" onclick="gcEd.toggleFind('<?= $editorId ?>')" title="Find &amp; Replace">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>
    <button type="button" class="gc-tb" onclick="gcEd.toggleFullscreen('<?= $editorId ?>')" title="Fullscreen (F11)">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
    </button>
  </div>

  <!-- ── Find bar ────────────────────────────────────── -->
  <div class="gc-find-bar" id="gc-find-<?= $editorId ?>">
    <input class="gc-find-input" id="gc-find-q-<?= $editorId ?>"   placeholder="Find…"    oninput="gcEd.findHighlight('<?= $editorId ?>')">
    <input class="gc-find-input" id="gc-find-r-<?= $editorId ?>"   placeholder="Replace…" style="width:160px">
    <button class="gc-find-btn" onclick="gcEd.findNext('<?= $editorId ?>')">Next</button>
    <button class="gc-find-btn" onclick="gcEd.replaceOne('<?= $editorId ?>')">Replace</button>
    <button class="gc-find-btn" onclick="gcEd.replaceAll('<?= $editorId ?>')">All</button>
    <span class="gc-find-count" id="gc-find-cnt-<?= $editorId ?>"></span>
    <button class="gc-find-btn" onclick="gcEd.toggleFind('<?= $editorId ?>')" style="margin-left:auto">✕</button>
  </div>

  <!-- ── Textarea ───────────────────────────────────── -->
  <div class="gc-editor-body">
    <textarea
      name="<?= $editorName ?>"
      id="<?= $editorId ?>"
      class="gc-editor-ta"
      placeholder="Write your content here…"
      oninput="gcEd.onChange('<?= $editorId ?>')"
      onkeydown="gcEd.onKeyDown(event,'<?= $editorId ?>')"><?= e($editorValue) ?></textarea>
  </div>

  <!-- ── Footer ─────────────────────────────────────── -->
  <div class="gc-editor-footer">
    <div class="gc-stats">
      <span id="gc-words-<?= $editorId ?>">0 words</span>
      <span id="gc-chars-<?= $editorId ?>">0 chars</span>
      <span id="gc-lines-<?= $editorId ?>">0 lines</span>
    </div>
    <div id="gc-cursor-<?= $editorId ?>" style="font-family:monospace">Ln 1, Col 1</div>
  </div>
</div>

<script>
(function(){
if (window.gcEd) { gcEd._init('<?= $editorId ?>'); return; }

window.gcEd = {
  _history: {},
  _histIdx: {},
  _findIdx: {},

  _init: function(id) {
    this._history[id] = [document.getElementById(id).value];
    this._histIdx[id] = 0;
    this._findIdx[id] = -1;
    this._updateStats(id);
    var ta = document.getElementById(id);
    ta.addEventListener('click',  function(){ gcEd._updateCursor(id); });
    ta.addEventListener('keyup',  function(){ gcEd._updateCursor(id); });
    ta.addEventListener('scroll', function(){ gcEd._updateCursor(id); });
  },

  // ── History ──────────────────────────────────────────
  _push: function(id) {
    var v = document.getElementById(id).value;
    var h = this._history[id];
    if (h[this._histIdx[id]] === v) return;
    h.splice(this._histIdx[id] + 1);
    h.push(v);
    if (h.length > 100) h.shift();
    this._histIdx[id] = h.length - 1;
  },
  undo: function(id) {
    var h = this._history[id]; var i = this._histIdx[id];
    if (i <= 0) return;
    this._histIdx[id]--;
    var ta = document.getElementById(id);
    ta.value = h[this._histIdx[id]];
    this._updateStats(id);
  },
  redo: function(id) {
    var h = this._history[id]; var i = this._histIdx[id];
    if (i >= h.length - 1) return;
    this._histIdx[id]++;
    document.getElementById(id).value = h[this._histIdx[id]];
    this._updateStats(id);
  },

  onChange: function(id) {
    this._push(id);
    this._updateStats(id);
  },

  // ── Stats ─────────────────────────────────────────────
  _updateStats: function(id) {
    var v   = document.getElementById(id).value;
    var words = v.trim() === '' ? 0 : v.trim().split(/\s+/).length;
    var chars = v.length;
    var lines = v.split('\n').length;
    document.getElementById('gc-words-' + id).textContent = words.toLocaleString() + ' words';
    document.getElementById('gc-chars-' + id).textContent = chars.toLocaleString() + ' chars';
    document.getElementById('gc-lines-' + id).textContent = lines.toLocaleString() + ' lines';
  },
  _updateCursor: function(id) {
    var ta = document.getElementById(id);
    var s  = ta.selectionStart;
    var text = ta.value.substring(0, s);
    var lines = text.split('\n');
    var ln = lines.length, col = lines[lines.length - 1].length + 1;
    document.getElementById('gc-cursor-' + id).textContent = 'Ln ' + ln + ', Col ' + col;
  },

  // ── Inline wrap ──────────────────────────────────────
  inline: function(id, before, after) {
    var ta = document.getElementById(id);
    var s = ta.selectionStart, e = ta.selectionEnd;
    var sel = ta.value.substring(s, e) || 'text';
    ta.value = ta.value.substring(0, s) + before + sel + after + ta.value.substring(e);
    ta.selectionStart = s + before.length;
    ta.selectionEnd   = s + before.length + sel.length;
    ta.focus(); this._push(id); this._updateStats(id);
  },

  // ── Heading ──────────────────────────────────────────
  heading: function(id, level) {
    var prefix = '#'.repeat(level) + ' ';
    this._linePrefix(id, prefix);
  },

  // ── List ─────────────────────────────────────────────
  list: function(id, type) {
    var ta = document.getElementById(id);
    var s = ta.selectionStart, e = ta.selectionEnd;
    var selected = ta.value.substring(s, e);
    if (!selected) { this._linePrefix(id, type === 'ul' ? '- ' : '1. '); return; }
    var lines = selected.split('\n');
    var result = lines.map(function(l, i) {
      return (type === 'ul' ? '- ' : (i+1) + '. ') + l;
    }).join('\n');
    ta.value = ta.value.substring(0, s) + result + ta.value.substring(e);
    ta.selectionStart = s; ta.selectionEnd = s + result.length;
    ta.focus(); this._push(id); this._updateStats(id);
  },

  // ── Block ────────────────────────────────────────────
  block: function(id, type) {
    if (type === 'quote')     this._linePrefix(id, '> ');
    if (type === 'codeblock') {
      var ta = document.getElementById(id);
      var s = ta.selectionStart, e = ta.selectionEnd;
      var sel = ta.value.substring(s, e) || 'code here';
      var ins = '```\n' + sel + '\n```';
      ta.value = ta.value.substring(0, s) + ins + ta.value.substring(e);
      ta.selectionStart = s + 4; ta.selectionEnd = s + 4 + sel.length;
      ta.focus(); this._push(id); this._updateStats(id);
    }
  },

  // ── Line prefix helper ────────────────────────────────
  _linePrefix: function(id, prefix) {
    var ta = document.getElementById(id);
    var s  = ta.selectionStart;
    var ls = ta.value.lastIndexOf('\n', s - 1) + 1;
    // Toggle: remove prefix if already present
    if (ta.value.substring(ls, ls + prefix.length) === prefix) {
      ta.value = ta.value.substring(0, ls) + ta.value.substring(ls + prefix.length);
      ta.selectionStart = ta.selectionEnd = Math.max(ls, s - prefix.length);
    } else {
      ta.value = ta.value.substring(0, ls) + prefix + ta.value.substring(ls);
      ta.selectionStart = ta.selectionEnd = s + prefix.length;
    }
    ta.focus(); this._push(id); this._updateStats(id);
  },

  // ── Insert helpers ────────────────────────────────────
  insertText: function(id, text) {
    var ta = document.getElementById(id);
    var s = ta.selectionStart;
    ta.value = ta.value.substring(0, s) + text + ta.value.substring(s);
    ta.selectionStart = ta.selectionEnd = s + text.length;
    ta.focus(); this._push(id); this._updateStats(id);
  },
  insertLink: function(id) {
    var ta  = document.getElementById(id);
    var sel = ta.value.substring(ta.selectionStart, ta.selectionEnd) || 'link text';
    var url = prompt('URL:', 'https://');
    if (!url) return;
    this.inline(id, '[' + sel, '](' + url + ')');
  },
  insertImageUrl: function(id) {
    var url = prompt('Image URL:', 'https://');
    if (!url) return;
    var alt = prompt('Alt text:', '');
    this.insertText(id, '![' + (alt||'image') + '](' + url + ')');
  },
  insertTable: function(id) {
    var cols = parseInt(prompt('Columns:', '3')) || 3;
    var rows = parseInt(prompt('Rows:',    '3')) || 3;
    var header = '| ' + Array(cols).fill('Header').join(' | ') + ' |';
    var sep    = '| ' + Array(cols).fill('---').join(' | ') + ' |';
    var body   = Array(rows).fill('| ' + Array(cols).fill('Cell').join(' | ') + ' |').join('\n');
    this.insertText(id, '\n' + header + '\n' + sep + '\n' + body + '\n');
  },
  insertShortcode: function(id) {
    var tag = prompt('Shortcode tag:', 'button');
    if (!tag) return;
    this.insertText(id, '[' + tag + '][/' + tag + ']');
  },

  // ── Gallery modal ─────────────────────────────────────
  openGallery: function(editorId, base) {
    var self = this;
    Swal.fire({
      title: 'Media Gallery',
      width: 860,
      html: '<div id="gc-gal-wrap" style="text-align:left">' +
            '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">' +
            '<input type="text" id="gc-gal-search" placeholder="Search files…" oninput="gcEd._galFilter()" ' +
            'style="padding:7px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;width:220px;outline:none">' +
            '<label style="cursor:pointer;background:#10B27C;color:#fff;padding:7px 14px;border-radius:7px;font-size:12.5px;font-weight:600">' +
            '⬆ Upload' +
            '<input type="file" id="gc-gal-upload" multiple accept="image/*" style="display:none" onchange="gcEd._galUpload(this,\'' + base + '\')">' +
            '</label></div>' +
            '<div id="gc-gal-grid" class="gc-gallery-grid"><div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b">Loading…</div></div>' +
            '</div>',
      showConfirmButton: false,
      showCloseButton: true,
      customClass: { popup: 'gc-swal-popup' },
      didOpen: function() {
        self._galLoad(base, editorId);
      }
    });
    window._gcGalEditorId = editorId;
  },

  _galLoad: function(base, editorId) {
    fetch(base + '/manage/gallery/json')
      .then(function(r){ return r.json(); })
      .then(function(data) {
        window._gcGalBase    = base;
        window._gcGalEdId    = editorId;
        window._gcGalItems   = data.media || [];
        gcEd._galRender(data.media || []);
      })
      .catch(function(){ document.getElementById('gc-gal-grid').innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#ef4444;padding:24px">Failed to load media.</div>'; });
  },

  _galRender: function(items) {
    var grid = document.getElementById('gc-gal-grid');
    if (!grid) return;
    if (!items.length) { grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b">No media uploaded yet.</div>'; return; }
    var base  = window._gcGalBase;
    var edId  = window._gcGalEdId;
    grid.innerHTML = items.map(function(m) {
      var isImg = (m.mime_type || '').startsWith('image/');
      var url   = base + '/storage/media/' + m.path;
      var ins   = isImg ? '![' + m.original_name + '](' + url + ')' : '[' + m.original_name + '](' + url + ')';
      if (isImg) {
        return '<div class="gc-gallery-item" onclick="gcEd._galPick(\'' + ins.replace(/'/g,"\\'") + '\')" title="' + m.original_name + '">' +
               '<img src="' + url + '" loading="lazy" onerror="this.style.display=\'none\'">' +
               '<div class="gc-gi-name">' + m.original_name + '</div></div>';
      }
      return '<div class="gc-gallery-item gc-gi-file" onclick="gcEd._galPick(\'' + ins.replace(/'/g,"\\'") + '\')" title="' + m.original_name + '">' +
             '<div class="gc-gi-icon">📄</div><div>' + m.original_name + '</div></div>';
    }).join('');
  },

  _galFilter: function() {
    var q     = (document.getElementById('gc-gal-search').value || '').toLowerCase();
    var items = (window._gcGalItems || []).filter(function(m) {
      return !q || m.original_name.toLowerCase().includes(q);
    });
    gcEd._galRender(items);
  },

  _galPick: function(ins) {
    gcEd.insertText(window._gcGalEdId, ins);
    Swal.close();
  },

  _galUpload: function(input, base) {
    var files = input.files;
    if (!files.length) return;
    var grid = document.getElementById('gc-gal-grid');
    if (grid) grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:32px;color:#64748b">Uploading…</div>';
    var fd = new FormData();
    for (var i = 0; i < files.length; i++) fd.append('file', files[i]);
    fd.append('_csrf', window.gcCsrf || '');
    fetch(base + '/manage/gallery/upload', { method: 'POST', body: fd })
      .then(function(){ gcEd._galLoad(base, window._gcGalEdId); })
      .catch(function(){ if (grid) grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#ef4444;padding:24px">Upload failed.</div>'; });
  },

  // ── Find & Replace ────────────────────────────────────
  toggleFind: function(id) {
    var bar = document.getElementById('gc-find-' + id);
    bar.classList.toggle('open');
    if (bar.classList.contains('open')) {
      document.getElementById('gc-find-q-' + id).focus();
    }
  },
  findHighlight: function(id) {
    this._findIdx[id] = -1;
    var q   = document.getElementById('gc-find-q-' + id).value;
    var cnt = document.getElementById('gc-find-cnt-' + id);
    if (!q) { cnt.textContent = ''; return; }
    var text  = document.getElementById(id).value;
    var regex = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'gi');
    var matches = text.match(regex);
    cnt.textContent = matches ? matches.length + ' found' : '0 found';
  },
  findNext: function(id) {
    var q    = document.getElementById('gc-find-q-' + id).value;
    if (!q) return;
    var ta   = document.getElementById(id);
    var text = ta.value;
    var from = (this._findIdx[id] < 0 ? 0 : ta.selectionEnd);
    var idx  = text.toLowerCase().indexOf(q.toLowerCase(), from);
    if (idx < 0) idx = text.toLowerCase().indexOf(q.toLowerCase(), 0);
    if (idx < 0) return;
    ta.focus();
    ta.selectionStart = idx;
    ta.selectionEnd   = idx + q.length;
    this._findIdx[id] = idx;
  },
  replaceOne: function(id) {
    var q = document.getElementById('gc-find-q-' + id).value;
    var r = document.getElementById('gc-find-r-' + id).value;
    if (!q) return;
    var ta = document.getElementById(id);
    var s  = ta.selectionStart, e = ta.selectionEnd;
    if (ta.value.substring(s, e).toLowerCase() === q.toLowerCase()) {
      ta.value = ta.value.substring(0, s) + r + ta.value.substring(e);
      this._push(id); this._updateStats(id);
    }
    this.findNext(id);
  },
  replaceAll: function(id) {
    var q = document.getElementById('gc-find-q-' + id).value;
    var r = document.getElementById('gc-find-r-' + id).value;
    if (!q) return;
    var ta = document.getElementById(id);
    var regex = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'gi');
    ta.value = ta.value.replace(regex, r);
    this._push(id); this._updateStats(id);
    this.findHighlight(id);
  },

  // ── Fullscreen ───────────────────────────────────────
  toggleFullscreen: function(id) {
    var wrap = document.getElementById('gc-editor-wrap-' + id);
    wrap.classList.toggle('fullscreen');
  },

  // ── Keyboard shortcuts ────────────────────────────────
  onKeyDown: function(e, id) {
    if (e.ctrlKey || e.metaKey) {
      if (e.key === 'b') { e.preventDefault(); this.inline(id,'**','**'); }
      if (e.key === 'i') { e.preventDefault(); this.inline(id,'*','*'); }
      if (e.key === 'u') { e.preventDefault(); this.inline(id,'<u>','</u>'); }
      if (e.key === 'k') { e.preventDefault(); this.insertLink(id); }
      if (e.key === 'z' && !e.shiftKey) { e.preventDefault(); this.undo(id); }
      if (e.key === 'y' || (e.key === 'z' && e.shiftKey)) { e.preventDefault(); this.redo(id); }
      if (e.key === 'f') { e.preventDefault(); this.toggleFind(id); }
    }
    if (e.key === 'F11') { e.preventDefault(); this.toggleFullscreen(id); }
    if (e.key === 'Tab') {
      e.preventDefault();
      var ta = document.getElementById(id);
      var s  = ta.selectionStart;
      ta.value = ta.value.substring(0, s) + '  ' + ta.value.substring(s);
      ta.selectionStart = ta.selectionEnd = s + 2;
    }
    if (e.key === 'Escape') {
      var wrap = document.getElementById('gc-editor-wrap-' + id);
      if (wrap.classList.contains('fullscreen')) wrap.classList.remove('fullscreen');
    }
  }
};

gcEd._init('<?= $editorId ?>');
})();
</script>
