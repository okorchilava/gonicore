<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Manage') ?> — <?= e($siteName) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --sidebar-w: 280px;
    --topbar-h:  60px;
    --bg:        #f1f5f9;
    --surface:   #ffffff;
    --border:    #e2e8f0;
    --text:      #0f172a;
    --muted:     #64748b;
    --accent:    #10B27C;
    --accent-d:  #0e9c6c;
    --danger:    #ef4444;
    --warn:      #f59e0b;
    --info:      #0EA5E9;
    --sidebar-bg:#0f172a;
    --font:      system-ui, -apple-system, 'Segoe UI', sans-serif;
    --radius:    10px;
    --shadow:    0 1px 3px rgba(0,0,0,.07), 0 4px 12px rgba(0,0,0,.04);
}
body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }

/* ── Sidebar ───────────────────────────────────────── */
.sidebar {
    width: var(--sidebar-w);
    background: var(--sidebar-bg);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    z-index: 100;
    flex-shrink: 0;
}
.sidebar-logo {
    height: var(--topbar-h);
    border-bottom: 1px solid rgba(255,255,255,.06);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sidebar-section {
    padding: 20px 12px 8px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #475569;
}
.sidebar-nav { list-style: none; padding: 0 8px; }
.sidebar-nav li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 7px;
    font-size: 13.5px;
    font-weight: 500;
    color: #94a3b8;
    transition: background .15s, color .15s;
    text-decoration: none;
}
.sidebar-nav li a:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
.sidebar-nav li a.active { background: rgba(16,178,124,.15); color: var(--accent); }
.sidebar-nav li a .nav-icon { font-size: 15px; width: 18px; text-align: center; flex-shrink: 0; }

/* Collapsible groups */
.nav-group-toggle {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 7px;
    font-size: 13.5px; font-weight: 500; color: #94a3b8;
    cursor: pointer; user-select: none; transition: background .15s, color .15s;
    list-style: none; width: 100%;
}
.nav-group-toggle:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
.nav-group-toggle.open { color: #e2e8f0; }
.nav-group-arrow { margin-left: auto; font-size: 10px; transition: transform .2s; opacity: .5; }
.nav-group-toggle.open .nav-group-arrow { transform: rotate(90deg); opacity: 1; }
.nav-group-items { overflow: hidden; max-height: 0; transition: max-height .25s ease; }
.nav-group-items.open { max-height: 500px; }
.nav-group-items li a { padding-left: 38px; font-size: 13px; }
.nav-group-items li a .nav-icon { font-size: 13px; }

.sidebar-bottom {
    margin-top: auto;
    padding: 12px 8px 16px;
    border-top: 1px solid rgba(255,255,255,.06);
}
.sidebar-user {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px;
    border-radius: 7px;
    transition: background .15s;
    text-decoration: none;
}
.sidebar-user:hover { background: rgba(255,255,255,.06); text-decoration: none; }
.sidebar-avatar {
    width: 30px; height: 30px;
    background: linear-gradient(135deg, var(--accent), var(--info));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.sidebar-user-info { min-width: 0; }
.sidebar-user-name { font-size: 13px; font-weight: 600; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-user-role { font-size: 11px; color: #475569; text-transform: capitalize; }

/* ── Main area ─────────────────────────────────────── */
.main-wrap {
    margin-left: var(--sidebar-w);
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

/* ── Topbar ────────────────────────────────────────── */
.topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    height: var(--topbar-h);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 50;
}
.topbar-title { font-size: 16px; font-weight: 700; letter-spacing: -.3px; }
.topbar-actions { display: flex; align-items: center; gap: 8px; }
.topbar-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px;
    background: var(--accent);
    color: #fff;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    transition: background .15s;
    text-decoration: none;
}
.topbar-btn:hover { background: var(--accent-d); text-decoration: none; color: #fff; }
.topbar-btn.ghost { background: transparent; color: var(--muted); border: 1px solid var(--border); }
.topbar-btn.ghost:hover { background: var(--bg); color: var(--text); }

/* ── Content ───────────────────────────────────────── */
.content { padding: 28px; flex: 1; }

/* ── Cards & utils ─────────────────────────────────── */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.card-header h3 { font-size: 14px; font-weight: 700; }
.card-body { padding: 20px; }

.stat-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 24px; }
.stat-card {
    position: relative;
    border-radius: var(--radius);
    padding: 20px 20px 18px;
    overflow: hidden;
    color: #fff;
}
.stat-card-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; opacity: .75; margin-bottom: 10px; position: relative; z-index: 1; }
.stat-card-value { font-size: 32px; font-weight: 900; letter-spacing: -1.5px; line-height: 1; position: relative; z-index: 1; }
.stat-card-sub   { font-size: 12px; opacity: .65; margin-top: 5px; position: relative; z-index: 1; }
.stat-card-icon  { position: absolute; bottom: -10px; right: -8px; font-size: 56px; transform: rotate(-35deg); opacity: .18; line-height: 1; pointer-events: none; user-select: none; }
.stat-card.c-green  { background: linear-gradient(135deg, #059669, #10b981); }
.stat-card.c-amber  { background: linear-gradient(135deg, #d97706, #f59e0b); }
.stat-card.c-slate  { background: linear-gradient(135deg, #475569, #64748b); }
.stat-card.c-blue   { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
.stat-card.c-violet { background: linear-gradient(135deg, #7c3aed, #a78bfa); }

/* ── Sortable widget grid ───────────────────────────── */
.widget-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    align-items: start;
}
.widget {
    cursor: default;
    transition: box-shadow .2s, opacity .2s, transform .15s;
}
.widget.dragging { opacity: .45; transform: scale(.97); }
.widget.drag-over { box-shadow: 0 0 0 2px var(--accent); }
.widget-handle {
    cursor: grab;
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
}
.widget-handle:active { cursor: grabbing; }
.drag-icon { color: var(--muted); font-size: 14px; opacity: .5; letter-spacing: -2px; }

/* ── Table ─────────────────────────────────────────── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); text-align: left; padding: 10px 16px; border-bottom: 1px solid var(--border); white-space: nowrap; }
td { padding: 12px 16px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: var(--bg); }

/* ── Topbar profile ────────────────────────────────── */
.topbar-profile-wrap { position: relative; }
.topbar-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px 5px 6px;
    border-radius: 8px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: background .15s, border-color .15s;
    text-decoration: none;
    user-select: none;
}
.topbar-profile:hover,
.topbar-profile.open { background: var(--bg); border-color: var(--border); text-decoration: none; }
.topbar-gravatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border);
    display: block;
    flex-shrink: 0;
}
.topbar-avatar-initials {
    display: none;
    width: 32px; height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--info));
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.topbar-profile-info { line-height: 1.3; }
.topbar-profile-name { font-size: 13px; font-weight: 600; color: var(--text); }
.topbar-profile-role { font-size: 11px; color: var(--muted); text-transform: capitalize; }
.topbar-chevron { font-size: 10px; color: var(--muted); margin-left: 2px; transition: transform .2s; }
.topbar-profile.open .topbar-chevron { transform: rotate(180deg); }

/* ── Notification bell ─────────────────────────────── */
.notif-wrap { position: relative; }
.notif-btn {
    position: relative;
    width: 36px; height: 36px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    transition: background .15s, border-color .15s;
    color: var(--muted);
}
.notif-btn:hover, .notif-btn.open { background: var(--bg); border-color: var(--accent); color: var(--text); }
.notif-badge {
    position: absolute;
    top: -5px; right: -5px;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    min-width: 17px; height: 17px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 4px;
    border: 2px solid var(--surface);
    line-height: 1;
    pointer-events: none;
}

/* ── Notification dropdown ─────────────────────────── */
.notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 340px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(0,0,0,.12);
    z-index: 200;
    overflow: hidden;
    opacity: 0;
    transform: translateY(-6px);
    pointer-events: none;
    transition: opacity .15s ease, transform .15s ease;
}
.notif-dropdown.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
.notif-header {
    padding: 14px 16px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border);
}
.notif-header h4 { font-size: 13px; font-weight: 700; }
.notif-read-all {
    font-size: 11px; font-weight: 600; color: var(--accent);
    background: none; border: none; cursor: pointer; font-family: var(--font);
    padding: 3px 6px; border-radius: 4px;
}
.notif-read-all:hover { background: #f0fdf4; }
.notif-list { max-height: 340px; overflow-y: auto; }
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    transition: background .12s;
    cursor: pointer;
    text-decoration: none;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: var(--bg); }
.notif-item.unread { background: #f0fdf4; }
.notif-item.unread:hover { background: #dcfce7; }
.notif-icon-wrap {
    width: 34px; height: 34px;
    border-radius: 8px;
    background: var(--bg);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    border: 1px solid var(--border);
}
.notif-item.unread .notif-icon-wrap { background: #dcfce7; border-color: #bbf7d0; }
.notif-body { flex: 1; min-width: 0; }
.notif-title { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.notif-msg { font-size: 12px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.notif-time { font-size: 11px; color: var(--muted); white-space: nowrap; margin-top: 3px; }
.notif-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); flex-shrink: 0; margin-top: 5px; }
.notif-empty { text-align: center; padding: 32px 16px; color: var(--muted); font-size: 13px; }
.notif-empty-icon { font-size: 28px; margin-bottom: 8px; }

/* ── Lang switcher (panel) ─────────────────────────── */
.lang-btn {
    display: flex; align-items: center; gap: 5px;
    padding: 6px 10px; border-radius: 8px;
    border: 1px solid var(--border); background: transparent;
    font-size: 13px; font-weight: 600; color: var(--text);
    cursor: pointer; font-family: var(--font);
    transition: background .15s, border-color .15s;
    height: 36px;
}
.lang-btn:hover, .lang-btn.open { background: var(--bg); border-color: var(--accent); }
.lang-dropdown {
    position: absolute; top: calc(100% + 8px); right: 0;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.1);
    min-width: 150px; z-index: 200; overflow: hidden;
    opacity: 0; transform: translateY(-6px);
    pointer-events: none; transition: opacity .15s, transform .15s;
}
.lang-dropdown.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
.lang-option {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 14px; font-size: 13px; font-weight: 500;
    color: var(--text); text-decoration: none; transition: background .12s;
}
.lang-option:hover { background: var(--bg); text-decoration: none; }
.lang-option.active { background: #f0fdf4; color: var(--accent); font-weight: 700; }

/* ── Profile dropdown ──────────────────────────────── */
.profile-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
    min-width: 200px;
    z-index: 200;
    overflow: hidden;
    opacity: 0;
    transform: translateY(-6px);
    pointer-events: none;
    transition: opacity .15s ease, transform .15s ease;
}
.profile-dropdown.show {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
.profile-dropdown-header {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--border);
}
.profile-dropdown-header .name { font-size: 13px; font-weight: 700; color: var(--text); }
.profile-dropdown-header .email { font-size: 12px; color: var(--muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.profile-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    font-size: 13px;
    color: var(--text);
    text-decoration: none;
    transition: background .12s;
    cursor: pointer;
}
.profile-dropdown-item:hover { background: var(--bg); text-decoration: none; }
.profile-dropdown-item.danger { color: var(--danger); }
.profile-dropdown-item.danger:hover { background: #fef2f2; }
.profile-dropdown-divider { height: 1px; background: var(--border); margin: 4px 0; }

/* ── Badges ────────────────────────────────────────── */
.badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.badge.published { background: #dcfce7; color: #16a34a; }
.badge.draft     { background: #fef3c7; color: #d97706; }
.badge.archived  { background: #f1f5f9; color: #64748b; }
.badge.admin     { background: #ede9fe; color: #7c3aed; }
.badge.editor    { background: #dbeafe; color: #1d4ed8; }
.badge.viewer    { background: #f1f5f9; color: #64748b; }

/* ── Buttons ───────────────────────────────────────── */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: none; font-family: var(--font); transition: all .15s; text-decoration: none; }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent-d); text-decoration: none; color: #fff; }
.btn-danger  { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
.btn-danger:hover  { background: #fee2e2; text-decoration: none; }
.btn-ghost   { background: transparent; color: var(--muted); border: 1px solid var(--border); }
.btn-ghost:hover { background: var(--bg); text-decoration: none; color: var(--text); }

/* ── Activity log ──────────────────────────────────── */
.activity-list { list-style: none; }
.activity-item { display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
.activity-item:last-child { border-bottom: none; }
.activity-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); margin-top: 5px; flex-shrink: 0; }
.activity-dot.del { background: var(--danger); }
.activity-dot.upd { background: var(--info); }
.activity-time { font-size: 11px; color: var(--muted); white-space: nowrap; }

/* ── Todo ──────────────────────────────────────────── */
.todo-form { display: flex; gap: 8px; margin-bottom: 14px; }
.todo-form input { flex: 1; padding: 8px 12px; border: 1.5px solid var(--border); border-radius: 7px; font-size: 13.5px; font-family: var(--font); outline: none; }
.todo-form input:focus { border-color: var(--accent); }
.todo-list { list-style: none; }
.todo-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13.5px; }
.todo-item:last-child { border-bottom: none; }
.todo-item.done span { text-decoration: line-through; color: var(--muted); }
.todo-check { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; flex-shrink: 0; }
.todo-del { margin-left: auto; color: var(--muted); font-size: 11px; background: none; border: none; cursor: pointer; padding: 2px 6px; border-radius: 4px; }
.todo-del:hover { background: #fee2e2; color: var(--danger); }

/* ── Server stats ──────────────────────────────────── */
.server-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; }
.server-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border); }
.server-row:last-child { border-bottom: none; }
.server-key { color: var(--muted); }
.server-val { font-weight: 600; color: var(--text); }
.disk-bar { height: 6px; background: var(--border); border-radius: 3px; margin-top: 8px; overflow: hidden; }
.disk-bar-fill { height: 100%; background: var(--accent); border-radius: 3px; transition: width .4s; }
.disk-bar-fill.warn { background: var(--warn); }
.disk-bar-fill.danger { background: var(--danger); }

/* ── Form ──────────────────────────────────────────── */
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text); }
.form-input, .form-select, .form-textarea {
    width: 100%; padding: 10px 13px;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-size: 14px; font-family: var(--font); color: var(--text);
    background: var(--surface); outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(16,178,124,.1);
}
.form-textarea { min-height: 300px; resize: vertical; line-height: 1.7; }

/* ── Pagination ────────────────────────────────────── */
.pagination { display: flex; gap: 4px; padding-top: 20px; }
.pagination a, .pagination span {
    width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 7px; font-size: 13px; font-weight: 500; border: 1px solid var(--border); color: var(--text);
}
.pagination a:hover { background: var(--bg); text-decoration: none; }
.pagination .current { background: var(--accent); color: #fff; border-color: var(--accent); }
.pagination .disabled { opacity: .3; pointer-events: none; }

/* ── Empty ─────────────────────────────────────────── */
.empty { text-align: center; padding: 48px 24px; color: var(--muted); }
.empty-icon { font-size: 36px; margin-bottom: 12px; }
.empty h3 { font-size: 16px; color: var(--text); margin-bottom: 6px; }

/* ── Recent posts ──────────────────────────────────── */
.recent-post { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
.recent-post:last-child { border-bottom: none; }
.recent-post-title { flex: 1; font-weight: 500; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ── SweetAlert2 overrides ─────────────────────────── */
.gc-swal-popup { font-family: var(--font) !important; border-radius: 14px !important; }
.gc-swal-toast { font-family: var(--font) !important; border-radius: 10px !important; }
.swal2-title   { font-size: 18px !important; font-weight: 700 !important; }
.swal2-html-container, .swal2-content { font-size: 14px !important; }
.swal2-actions { gap: 8px !important; }
.swal2-confirm, .swal2-cancel {
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: 13.5px !important;
    padding: 9px 20px !important;
}

@media (max-width: 1200px) {
    .stat-grid { grid-template-columns: repeat(3, 1fr); }
    .widget-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .main-wrap { margin-left: 0; }
    .stat-grid { grid-template-columns: 1fr 1fr; }
    .widget-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="<?= e($base) ?>/manage" style="display:flex;align-items:center;justify-content:center;text-decoration:none;width:100%">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="120" height="60">
                <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#fff" stroke-width="5" opacity=".6"/>
                <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
                <text x="80" y="46" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="900" fill="#ffffff" letter-spacing="-0.5">Goni</text>
                <text x="80" y="74" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="300" fill="#10B27C" letter-spacing="-0.5">Core</text>
            </svg>
        </a>
    </div>

    <?php
    $_nav   = $activeNav ?? '';
    $_groups = [
        'content'    => ['posts','categories','pages'],
        'media'      => ['gallery'],
        'appearance' => ['menus','widgets','themes'],
        'users'      => ['users','profile'],
        'tools'      => ['languages','plugins','settings','sliders','store'],
    ];
    // Determine which group is open based on active nav
    $_openGroup = '';
    foreach ($_groups as $_gk => $_gitems) {
        if (in_array($_nav, $_gitems, true)) { $_openGroup = $_gk; break; }
    }
    ?>
    <ul class="sidebar-nav" style="padding-top:12px">

        <!-- Dashboard (standalone) -->
        <li><a href="<?= e($base) ?>/manage" class="<?= $_nav === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">◈</span> Dashboard
        </a></li>

        <!-- Content group -->
        <li>
            <div class="nav-group-toggle <?= $_openGroup==='content'?'open':'' ?>" onclick="toggleNavGroup(this,'content')">
                <span class="nav-icon">✦</span> Content
                <span class="nav-group-arrow">▶</span>
            </div>
            <ul class="nav-group-items <?= $_openGroup==='content'?'open':'' ?>" id="navg-content">
                <li><a href="<?= e($base) ?>/manage/posts" class="<?= in_array($_nav,['posts','post_form'])?'active':'' ?>">
                    <span class="nav-icon">✦</span> Posts
                </a></li>
                <li><a href="<?= e($base) ?>/manage/categories" class="<?= $_nav==='categories'?'active':'' ?>">
                    <span class="nav-icon">📂</span> Categories
                </a></li>
                <li><a href="<?= e($base) ?>/manage/pages" class="<?= $_nav==='pages'?'active':'' ?>">
                    <span class="nav-icon">◫</span> Pages
                </a></li>
            </ul>
        </li>

        <!-- Media group -->
        <li>
            <div class="nav-group-toggle <?= $_openGroup==='media'?'open':'' ?>" onclick="toggleNavGroup(this,'media')">
                <span class="nav-icon">🖼</span> Media
                <span class="nav-group-arrow">▶</span>
            </div>
            <ul class="nav-group-items <?= $_openGroup==='media'?'open':'' ?>" id="navg-media">
                <li><a href="<?= e($base) ?>/manage/gallery" class="<?= $_nav==='gallery'?'active':'' ?>">
                    <span class="nav-icon">🖼</span> Gallery
                </a></li>
            </ul>
        </li>

        <!-- Appearance group -->
        <li>
            <div class="nav-group-toggle <?= $_openGroup==='appearance'?'open':'' ?>" onclick="toggleNavGroup(this,'appearance')">
                <span class="nav-icon">◫</span> Appearance
                <span class="nav-group-arrow">▶</span>
            </div>
            <ul class="nav-group-items <?= $_openGroup==='appearance'?'open':'' ?>" id="navg-appearance">
                <li><a href="<?= e($base) ?>/manage/menus" class="<?= $_nav==='menus'?'active':'' ?>">
                    <span class="nav-icon">☰</span> Menus
                </a></li>
                <li><a href="<?= e($base) ?>/manage/widgets" class="<?= $_nav==='widgets'?'active':'' ?>">
                    <span class="nav-icon">⊞</span> Widgets
                </a></li>
            </ul>
        </li>

        <!-- Users group -->
        <li>
            <div class="nav-group-toggle <?= $_openGroup==='users'?'open':'' ?>" onclick="toggleNavGroup(this,'users')">
                <span class="nav-icon">◉</span> Users
                <span class="nav-group-arrow">▶</span>
            </div>
            <ul class="nav-group-items <?= $_openGroup==='users'?'open':'' ?>" id="navg-users">
                <li><a href="<?= e($base) ?>/manage/users" class="<?= $_nav==='users'?'active':'' ?>">
                    <span class="nav-icon">◉</span> All Users
                </a></li>
                <li><a href="<?= e($base) ?>/manage/profile" class="<?= $_nav==='profile'?'active':'' ?>">
                    <span class="nav-icon">👤</span> My Profile
                </a></li>
            </ul>
        </li>

        <!-- Tools group -->
        <li>
            <div class="nav-group-toggle <?= $_openGroup==='tools'?'open':'' ?>" onclick="toggleNavGroup(this,'tools')">
                <span class="nav-icon">⚙</span> Tools
                <span class="nav-group-arrow">▶</span>
            </div>
            <ul class="nav-group-items <?= $_openGroup==='tools'?'open':'' ?>" id="navg-tools">
                <li><a href="<?= e($base) ?>/manage/languages" class="<?= $_nav==='languages'?'active':'' ?>">
                    <span class="nav-icon">🌐</span> Languages
                </a></li>
                <li><a href="<?= e($base) ?>/manage/plugins" class="<?= $_nav==='plugins'?'active':'' ?>">
                    <span class="nav-icon">⬡</span> Plugins
                </a></li>
                <li><a href="<?= e($base) ?>/manage/settings" class="<?= $_nav==='settings'?'active':'' ?>">
                    <span class="nav-icon">⚙</span> Settings
                </a></li>
                <?php if (isset($hooks) && $hooks instanceof \GoniCore\Core\Hooks\HookManager): ?>
                <?php $hooks->doAction('manage.sidebar.nav', $base, $_nav); ?>
                <?php endif ?>
            </ul>
        </li>

    </ul>

    <script>
    function toggleNavGroup(toggle, groupId) {
        var items = document.getElementById('navg-' + groupId);
        var isOpen = toggle.classList.contains('open');

        // Close all other groups (accordion behavior)
        document.querySelectorAll('.nav-group-toggle.open').forEach(function(t) {
            if (t !== toggle) {
                t.classList.remove('open');
                var id = t.getAttribute('onclick').match(/'([^']+)'\)/)?.[1];
                if (id) {
                    var el = document.getElementById('navg-' + id);
                    if (el) el.classList.remove('open');
                }
            }
        });
        document.querySelectorAll('.nav-group-items.open').forEach(function(el) {
            if (el !== items) el.classList.remove('open');
        });

        // Toggle current
        toggle.classList.toggle('open', !isOpen);
        if (items) items.classList.toggle('open', !isOpen);
    }
    </script>

    <div class="sidebar-bottom"></div>
</aside>

<?php
$_gravatarHash = !empty($user['email'])
    ? md5(strtolower(trim((string)$user['email'])))
    : '00000000000000000000000000000000';
$_gravatarUrl = 'https://www.gravatar.com/avatar/' . $_gravatarHash . '?s=64&d=404';
$_userInitial = strtoupper(substr((string)($user['name'] ?? 'U'), 0, 1));
?>
<div class="main-wrap">
    <header class="topbar">
        <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
        <div class="topbar-actions" style="display:flex;align-items:center;gap:10px">
            <?php if (!empty($panelLangs) && count($panelLangs) > 1):
                $activePanelLang = null;
                foreach ($panelLangs as $pl) { if ($pl['code'] === ($currentLangCode ?? 'en')) { $activePanelLang = $pl; break; } }
                $activePanelLang = $activePanelLang ?? $panelLangs[0];
            ?>
            <div class="notif-wrap" style="margin-right:2px">
                <button class="lang-btn" id="panelLangBtn" type="button">
                    <span style="font-size:16px;line-height:1"><?= e((string)($activePanelLang['flag'] ?? '🌐')) ?></span>
                    <span style="font-size:12px;font-weight:600"><?= strtoupper(e((string)$activePanelLang['code'])) ?></span>
                    <span style="font-size:9px;opacity:.5">▼</span>
                </button>
                <div class="lang-dropdown" id="panelLangDropdown" style="min-width:160px">
                    <?php foreach ($panelLangs as $pl): ?>
                    <a href="<?= e($base) ?>/lang/<?= e((string)$pl['code']) ?>" class="lang-option <?= $pl['code'] === ($currentLangCode ?? 'en') ? 'active' : '' ?>">
                        <span style="font-size:16px;line-height:1"><?= e((string)($pl['flag'] ?? '🌐')) ?></span>
                        <span><?= e((string)$pl['native']) ?></span>
                    </a>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>
            <?= $topbarActions ?? '' ?>
            <?php if (!empty($user)): ?>
            <a href="<?= e($base) ?>/" target="_blank" class="topbar-btn ghost" style="font-size:12px">↗ View Site</a>
            <!-- Bell -->
            <div class="notif-wrap">
                <button class="notif-btn" id="notifBtn" type="button">
                    🔔
                    <?php if (($notifUnread ?? 0) > 0): ?>
                    <span class="notif-badge"><?= min((int)($notifUnread ?? 0), 99) ?></span>
                    <?php endif ?>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <h4>Notifications <?php if (($notifUnread ?? 0) > 0): ?><span style="color:var(--muted);font-weight:400">(<?= (int)($notifUnread ?? 0) ?> unread)</span><?php endif ?></h4>
                        <?php if (($notifUnread ?? 0) > 0): ?>
                        <form method="POST" action="<?= e($base) ?>/manage/notifications/read-all">
                            <button type="submit" class="notif-read-all">Mark all read</button>
                        </form>
                        <?php endif ?>
                    </div>
                    <div class="notif-list">
                        <?php if (!empty($notifList)): ?>
                        <?php foreach ($notifList as $n):
                            $isUnread = empty($n['read_at']);
                            $nMeta    = $n['data'] ? json_decode((string)$n['data'], true) : [];
                        ?>
                        <form method="POST" action="<?= e($base) ?>/manage/notifications/<?= (int)$n['id'] ?>/read" style="display:contents">
                            <button type="submit" class="notif-item <?= $isUnread ? 'unread' : '' ?>">
                                <div class="notif-icon-wrap"><?= e((string)($n['icon'] ?? '🔔')) ?></div>
                                <div class="notif-body">
                                    <div class="notif-title"><?= e((string)$n['title']) ?></div>
                                    <?php if ($n['message']): ?>
                                    <div class="notif-msg"><?= e((string)$n['message']) ?></div>
                                    <?php endif ?>
                                    <div class="notif-time"><?= e(fmt_date((string)$n['created_at'])) ?></div>
                                </div>
                                <?php if ($isUnread): ?><div class="notif-dot"></div><?php endif ?>
                            </button>
                        </form>
                        <?php endforeach ?>
                        <?php else: ?>
                        <div class="notif-empty">
                            <div class="notif-empty-icon">🔔</div>
                            <div>No notifications yet</div>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <!-- Profile -->
            <div class="topbar-profile-wrap">
                <div class="topbar-profile" id="profileTrigger">
                    <img src="<?= e($_gravatarUrl) ?>"
                         alt="<?= e((string)($user['name'] ?? '')) ?>"
                         class="topbar-gravatar"
                         id="profileGravatar"
                         onerror="this.style.display='none';document.getElementById('profileInitials').style.display='flex'">
                    <div class="topbar-avatar-initials" id="profileInitials">
                        <?= e($_userInitial) ?>
                    </div>
                    <div class="topbar-profile-info">
                        <div class="topbar-profile-name"><?= e((string)($user['name'] ?? '')) ?></div>
                        <div class="topbar-profile-role"><?= e((string)($user['role'] ?? '')) ?></div>
                    </div>
                    <span class="topbar-chevron">▼</span>
                </div>

                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="name"><?= e((string)($user['name'] ?? '')) ?></div>
                        <div class="email"><?= e((string)($user['email'] ?? '')) ?></div>
                    </div>
                    <a href="<?= e($base) ?>/manage" class="profile-dropdown-item">◈ &nbsp;Dashboard</a>
                    <a href="<?= e($base) ?>/manage/profile" class="profile-dropdown-item">👤 &nbsp;My Profile</a>
                    <a href="<?= e($base) ?>/manage/users" class="profile-dropdown-item">◉ &nbsp;Users</a>
                    <div class="profile-dropdown-divider"></div>
                    <a href="<?= e($base) ?>/logout" class="profile-dropdown-item danger">⏻ &nbsp;Sign out</a>
                </div>
            </div>
            <?php endif ?>
        </div>
    </header>
    <div class="content">
        <?= $content ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.all.min.js"></script>
<script>
/* ── GoniCore SweetAlert2 helpers ─────────────────── */

/**
 * Confirm dialog before form submission.
 * Usage: <button type="button" onclick="gcConfirm(this,'Title','Text','Delete','#ef4444')">
 */
window.gcConfirm = function(btn, title, text, confirmText, confirmColor) {
    Swal.fire({
        title:              title || 'Are you sure?',
        text:               text  || '',
        icon:               'warning',
        showCancelButton:   true,
        confirmButtonText:  confirmText  || 'Confirm',
        cancelButtonText:   'Cancel',
        confirmButtonColor: confirmColor || '#ef4444',
        cancelButtonColor:  '#94a3b8',
        reverseButtons:     true,
        focusCancel:        true,
        customClass: { popup: 'gc-swal-popup' },
    }).then(function(result) {
        if (result.isConfirmed) btn.closest('form').submit();
    });
};

/**
 * Toast notification (bottom-right, auto-dismiss).
 * icon: 'success' | 'error' | 'warning' | 'info'
 */
window.gcToast = function(message, icon, duration) {
    Swal.fire({
        toast:             true,
        position:          'bottom-end',
        icon:              icon     || 'success',
        title:             message,
        showConfirmButton: false,
        timer:             duration || 3500,
        timerProgressBar:  true,
        customClass: { popup: 'gc-swal-toast' },
    });
};

/* Auto-fire toasts from PHP flash data */
(function () {
    var flashEl = document.getElementById('gc-flash');
    if (flashEl) {
        gcToast(flashEl.dataset.msg, flashEl.dataset.icon || 'success');
    }
})();

/* ── Dropdown toggles ─────────────────────────────── */
(function () {
    function makeToggle(btnId, dropId) {
        var btn  = document.getElementById(btnId);
        var drop = document.getElementById(dropId);
        if (!btn || !drop) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = drop.classList.contains('show');
            document.querySelectorAll('.notif-dropdown.show, .profile-dropdown.show, .lang-dropdown.show').forEach(function(el){ el.classList.remove('show'); });
            document.querySelectorAll('.notif-btn.open, .topbar-profile.open, .lang-btn.open').forEach(function(el){ el.classList.remove('open'); });
            drop.classList.toggle('show', !open);
            btn.classList.toggle('open', !open);
        });
        drop.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    makeToggle('notifBtn',       'notifDropdown');
    makeToggle('profileTrigger', 'profileDropdown');
    makeToggle('panelLangBtn',   'panelLangDropdown');

    document.addEventListener('click', function () {
        document.querySelectorAll('.notif-dropdown.show, .profile-dropdown.show, .lang-dropdown.show').forEach(function(el){ el.classList.remove('show'); });
        document.querySelectorAll('.notif-btn.open, .topbar-profile.open, .lang-btn.open').forEach(function(el){ el.classList.remove('open'); });
    });
})();
</script>
</body>
</html>
