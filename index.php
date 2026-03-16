<?php
// index.php — NetMap frontend (PHP + polling)
require_once __DIR__ . '/includes/config.php';
?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NetMap v2.1 — DS1511</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Outfit:wght@400;600;700;900&display=swap');
:root {
  --bg:#07090f;--bg2:#0c1018;--bg3:#121722;--bg4:#1a2133;
  --border:#1e2d45;--border2:#263550;
  --accent:#00e5ff;--accent2:#6366f1;
  --green:#22d3a0;--yellow:#fbbf24;--red:#f87171;
  --text:#e2eaf8;--muted:#4a6080;
}
[data-theme=light]{
  --bg:#f0f4f8;--bg2:#fff;--bg3:#e8edf5;--bg4:#dce4f0;
  --border:#c8d6e8;--border2:#a8bdd8;
  --accent:#0284c7;--accent2:#6366f1;
  --green:#059669;--yellow:#d97706;--red:#dc2626;
  --text:#0f172a;--muted:#64748b;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{background:var(--bg);color:var(--text);font-family:'Outfit',sans-serif;font-size:14px;transition:background .3s,color .3s}
.layout{display:grid;grid-template-rows:52px 1fr;grid-template-columns:260px 1fr 300px;height:100vh}

/* TOPBAR */
.topbar{grid-column:1/-1;display:flex;align-items:center;gap:12px;padding:0 20px;
  background:rgba(7,9,15,.96);border-bottom:1px solid var(--border);backdrop-filter:blur(12px);transition:background .3s}
[data-theme=light] .topbar{background:rgba(255,255,255,.96)}
.logo{display:flex;align-items:center;gap:10px}
.logo-mark{width:30px;height:30px;border:1.5px solid var(--accent);border-radius:7px;display:grid;place-items:center;position:relative}
.logo-mark::before{content:'';position:absolute;width:10px;height:10px;border-radius:50%;background:var(--accent);animation:cpulse 2.5s ease-in-out infinite}
@keyframes cpulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(0,229,255,.4)}50%{transform:scale(1.15);box-shadow:0 0 0 6px rgba(0,229,255,0)}}
.logo-name{font-size:17px;font-weight:900;letter-spacing:-.5px}
.logo-name em{color:var(--accent);font-style:normal}
.divider{width:1px;height:24px;background:var(--border);margin:0 2px}
.poll-status{display:flex;align-items:center;gap:6px;font-family:'DM Mono',monospace;font-size:11px;color:var(--muted)}
.pdot{width:6px;height:6px;border-radius:50%;background:var(--red);transition:background .3s}
.pdot.ok{background:var(--green);animation:glow 2s infinite}
.pdot.scan{background:var(--yellow);animation:glowy .5s infinite}
@keyframes glow{0%,100%{box-shadow:0 0 0 0 rgba(34,211,160,.4)}50%{box-shadow:0 0 0 4px rgba(34,211,160,0)}}
@keyframes glowy{0%,100%{opacity:1}50%{opacity:.4}}
.subnet-sel{display:flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:4px 4px 4px 10px}
.subnet-sel label{font-family:'DM Mono',monospace;font-size:10px;color:var(--muted);white-space:nowrap}
.subnet-select{background:var(--bg4);border:1px solid var(--border2);border-radius:6px;padding:3px 8px;color:var(--text);font-family:'DM Mono',monospace;font-size:11px;outline:none;cursor:pointer;min-width:140px}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:8px}
.theme-toggle{width:34px;height:20px;background:var(--bg4);border:1px solid var(--border);border-radius:10px;cursor:pointer;position:relative;transition:background .3s;flex-shrink:0}
.theme-toggle::after{content:'';position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:var(--muted);transition:transform .3s,background .3s}
[data-theme=light] .theme-toggle{background:var(--accent)}
[data-theme=light] .theme-toggle::after{transform:translateX(14px);background:#fff}
.theme-lbl{font-size:10px;color:var(--muted);font-family:'DM Mono',monospace}
.btn{padding:6px 14px;border-radius:7px;border:none;font-family:'Outfit',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:5px}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 3px 16px rgba(0,229,255,.25)}
.btn-primary.scanning{background:linear-gradient(135deg,var(--yellow),#d97706)}
.btn-ghost{background:var(--bg3);border:1px solid var(--border);color:var(--muted)}
.btn-ghost:hover{border-color:var(--border2);color:var(--text)}

/* SIDEBAR LEFT */
.sidebar-left{background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;transition:background .3s}
.sidebar-section{padding:14px 16px 10px;border-bottom:1px solid var(--border)}
.section-label{font-size:10px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;font-family:'DM Mono',monospace}
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.stat-card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:10px 12px;position:relative;overflow:hidden;transition:background .3s}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--c,var(--accent))}
.stat-card:nth-child(2){--c:var(--green)}.stat-card:nth-child(3){--c:var(--yellow)}.stat-card:nth-child(4){--c:var(--red)}
.stat-n{font-size:26px;font-weight:900;line-height:1;color:var(--text);margin-bottom:2px}
.stat-l{font-size:10px;color:var(--muted);font-family:'DM Mono',monospace}
.search-box{position:relative;padding:8px;border-bottom:1px solid var(--border)}
.search-box input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 10px 7px 30px;color:var(--text);font-size:12px;font-family:'Outfit',sans-serif;outline:none;transition:border-color .2s}
.search-box input:focus{border-color:var(--accent)}
.search-box input::placeholder{color:var(--muted)}
.search-icon{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none;font-size:13px}
.device-list-wrap{flex:1;overflow-y:auto;padding:8px}
.device-list-wrap::-webkit-scrollbar{width:3px}
.device-list-wrap::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.device-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:9px;cursor:pointer;transition:background .15s,border-color .15s;border:1px solid transparent;margin-bottom:2px}
.device-item:hover{background:var(--bg3)}
.device-item.active{background:rgba(0,229,255,.07);border-color:rgba(0,229,255,.2)}
.d-icon{width:34px;height:34px;border-radius:8px;background:var(--bg4);border:1px solid var(--border);display:grid;place-items:center;flex-shrink:0;font-size:16px;transition:background .3s}
.d-info{flex:1;min-width:0}
.d-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text)}
.d-meta{display:flex;align-items:center;gap:6px;margin-top:2px}
.d-ip{font-family:'DM Mono',monospace;font-size:10px;color:var(--muted)}
.ping-badge{font-family:'DM Mono',monospace;font-size:9px;font-weight:500;padding:1px 5px;border-radius:3px;white-space:nowrap}
.pg{background:rgba(34,211,160,.12);color:var(--green)}
.po{background:rgba(251,191,36,.12);color:var(--yellow)}
.ps{background:rgba(248,113,113,.12);color:var(--red)}
.pn{color:var(--muted)}
.d-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.d-dot.online{background:var(--green)}.d-dot.offline{background:var(--red)}.d-dot.idle{background:var(--yellow)}

/* MAIN CENTER */
.main-center{display:flex;flex-direction:column;overflow:hidden;border-right:1px solid var(--border)}
.scan-bar-wrap{height:2px;background:var(--bg3)}
.scan-bar-fill{height:100%;width:0%;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .4s ease}
.view-tabs{display:flex;gap:2px;padding:10px 16px;border-bottom:1px solid var(--border);background:var(--bg2)}
.tab{padding:5px 14px;border-radius:6px;border:none;background:transparent;color:var(--muted);font-family:'Outfit',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
.tab.active{background:var(--bg4);color:var(--text);border:1px solid var(--border2)}
.canvas-wrap{flex:1;position:relative;overflow:hidden}
#topo-canvas{display:block;width:100%;height:100%}
.events-view{display:none;flex-direction:column;flex:1;overflow:hidden}
.events-view.active{display:flex}
.events-table{overflow-y:auto;flex:1;padding:12px}
.events-table::-webkit-scrollbar{width:3px}
.event-row{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);margin-bottom:6px;animation:sin .3s ease}
@keyframes sin{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.ebadge{padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:.5px;flex-shrink:0}
.b-new{background:rgba(0,229,255,.12);color:var(--accent)}
.b-ip_changed{background:rgba(251,191,36,.12);color:var(--yellow)}
.b-offline{background:rgba(248,113,113,.12);color:var(--red)}
.b-online{background:rgba(34,211,160,.12);color:var(--green)}

/* SIDEBAR RIGHT */
.sidebar-right{background:var(--bg2);display:flex;flex-direction:column;overflow:hidden;transition:background .3s}
.detail-wrap{flex:1;overflow-y:auto}
.detail-wrap::-webkit-scrollbar{width:3px}
.detail-wrap::-webkit-scrollbar-thumb{background:var(--border)}
.detail-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;color:var(--muted);font-size:12px;gap:10px;text-align:center;padding:20px}
.detail-header{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border);background:var(--bg3);transition:background .3s}
.detail-big-icon{width:52px;height:52px;border-radius:14px;background:var(--bg4);border:1px solid var(--border);display:grid;place-items:center;flex-shrink:0;font-size:26px}
.detail-name{font-size:16px;font-weight:700;color:var(--text)}
.detail-vendor{font-size:11px;color:var(--muted);font-family:'DM Mono',monospace;margin-top:2px}
.detail-rows{padding:12px 16px}
.dr{display:flex;justify-content:space-between;align-items:flex-start;padding:7px 0;border-bottom:1px solid rgba(30,45,69,.6);gap:8px}
.dr:last-child{border-bottom:none}
.dr-key{font-size:11px;color:var(--muted);font-family:'DM Mono',monospace;flex-shrink:0}
.dr-val{font-size:11px;color:var(--text);font-family:'DM Mono',monospace;text-align:right;word-break:break-all;max-width:160px}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;font-family:'DM Mono',monospace}
.badge-g{background:rgba(34,211,160,.12);color:var(--green)}
.badge-y{background:rgba(251,191,36,.12);color:var(--yellow)}
.badge-r{background:rgba(248,113,113,.12);color:var(--red)}
.edit-form{padding:12px 16px;border-top:1px solid var(--border);background:var(--bg3);display:none;transition:background .3s}
.edit-form.open{display:block}
.form-row{margin-bottom:8px}
.form-row label{display:block;font-size:10px;color:var(--muted);margin-bottom:3px;font-family:'DM Mono',monospace}
.form-input,.form-select{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:7px 10px;color:var(--text);font-size:12px;font-family:'DM Mono',monospace;outline:none;transition:border-color .2s,background .3s}
.form-input:focus,.form-select:focus{border-color:var(--accent)}
.form-input::placeholder{color:var(--muted)}
.form-actions{display:flex;gap:6px;margin-top:10px}
.btn-save{padding:7px 14px;background:var(--accent);color:var(--bg);border:none;border-radius:7px;font-weight:700;font-size:12px;cursor:pointer;flex:1}
.btn-cancel{padding:7px 12px;background:var(--bg4);border:1px solid var(--border);color:var(--muted);border-radius:7px;font-size:12px;cursor:pointer}
.btn-danger{padding:7px 12px;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.2);color:var(--red);border-radius:7px;font-size:12px;cursor:pointer}
.export-panel{padding:10px 14px;border-top:1px solid var(--border);background:var(--bg3);transition:background .3s}
.export-row{display:flex;gap:8px;margin-top:8px}
.btn-export{flex:1;padding:7px;border-radius:8px;border:1px solid var(--border);background:var(--bg4);color:var(--text);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:5px;font-family:'Outfit',sans-serif;text-decoration:none}
.btn-export:hover{border-color:var(--accent);color:var(--accent)}
.btn-export.pdf:hover{border-color:var(--red);color:var(--red)}
.log-panel{height:155px;border-top:1px solid var(--border);display:flex;flex-direction:column}
.log-header{display:flex;align-items:center;justify-content:space-between;padding:7px 14px;border-bottom:1px solid var(--border);background:var(--bg3);transition:background .3s}
.log-title{font-size:10px;font-weight:700;letter-spacing:1.5px;color:var(--muted);text-transform:uppercase;font-family:'DM Mono',monospace}
.log-body{flex:1;overflow-y:auto;padding:8px 12px;font-family:'DM Mono',monospace;font-size:10px}
.log-body::-webkit-scrollbar{width:3px}
.log-body::-webkit-scrollbar-thumb{background:var(--border)}
.log-line{display:flex;gap:8px;padding:2px 0;color:var(--muted);animation:fu .25s ease}
@keyframes fu{from{opacity:0;transform:translateY(3px)}to{opacity:1;transform:translateY(0)}}
.log-t{color:var(--accent2);flex-shrink:0}
.log-line.ok .log-m{color:var(--green)}.log-line.err .log-m{color:var(--red)}
.log-line.inf .log-m{color:var(--accent)}.log-line.warn .log-m{color:var(--yellow)}
#tooltip{position:fixed;background:var(--bg4);border:1px solid var(--border2);border-radius:8px;padding:8px 12px;font-family:'DM Mono',monospace;font-size:10px;color:var(--text);pointer-events:none;z-index:999;display:none;line-height:1.7;box-shadow:0 4px 20px rgba(0,0,0,.5)}
.toast{position:fixed;bottom:20px;right:20px;background:var(--bg4);border:1px solid var(--border);border-left:3px solid var(--green);border-radius:10px;padding:10px 16px;font-size:13px;color:var(--text);transform:translateY(60px);opacity:0;transition:all .3s cubic-bezier(.34,1.56,.64,1);z-index:200;max-width:280px}
.toast.show{transform:translateY(0);opacity:1}
.toast.warn{border-left-color:var(--yellow)}.toast.err{border-left-color:var(--red)}
@media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar-left,.sidebar-right{display:none}}
</style>
</head>
<body>
<div class="layout">
  <header class="topbar">
    <div class="logo">
      <div class="logo-mark"></div>
      <div class="logo-name">Net<em>Map</em></div>
    </div>
    <div class="divider"></div>
    <div class="poll-status">
      <div class="pdot" id="pdot"></div>
      <span id="plabel">Laden...</span>
    </div>
    <div class="divider"></div>
    <div class="subnet-sel">
      <label>Subnet</label>
      <select class="subnet-select" id="subnet-select" onchange="saveSubnet()">
        <option><?= htmlspecialchars(SCAN_SUBNET) ?></option>
      </select>
      <button style="padding:4px 7px;background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:13px" onclick="loadInterfaces()" title="Herladen">↻</button>
    </div>
    <div class="topbar-right">
      <span class="theme-lbl" id="theme-lbl">🌙</span>
      <div class="theme-toggle" id="theme-toggle" onclick="toggleTheme()"></div>
      <div class="divider"></div>
      <a class="btn btn-ghost" href="api/?action=export_csv" target="_blank">⬇ CSV</a>
      <a class="btn btn-ghost" href="api/?action=export_html" target="_blank">⬇ PDF</a>
      <button class="btn btn-primary" id="scan-btn" onclick="triggerScan()">▶ Scan Nu</button>
    </div>
  </header>

  <aside class="sidebar-left">
    <div class="sidebar-section">
      <div class="section-label">Overzicht</div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-n" id="s-total">0</div><div class="stat-l">Totaal</div></div>
        <div class="stat-card"><div class="stat-n" id="s-online">0</div><div class="stat-l">Online</div></div>
        <div class="stat-card"><div class="stat-n" id="s-idle">0</div><div class="stat-l">Idle</div></div>
        <div class="stat-card"><div class="stat-n" id="s-offline">0</div><div class="stat-l">Offline</div></div>
      </div>
    </div>
    <div class="search-box">
      <span class="search-icon">🔍</span>
      <input placeholder="Zoek apparaat, IP, MAC..." id="search-input" oninput="renderList()">
    </div>
    <div class="device-list-wrap" id="device-list"></div>
    <div style="padding:10px 12px;border-top:1px solid var(--border)">
      <button class="btn btn-ghost" style="width:100%;justify-content:center" onclick="openAdd()">+ Apparaat toevoegen</button>
    </div>
  </aside>

  <main class="main-center">
    <div class="scan-bar-wrap"><div class="scan-bar-fill" id="scan-bar"></div></div>
    <div class="view-tabs">
      <button class="tab active" onclick="setView('topo',this)">Topologie</button>
      <button class="tab" onclick="setView('events',this)">Events</button>
    </div>
    <div class="canvas-wrap" id="topo-view">
      <canvas id="topo-canvas"></canvas>
    </div>
    <div class="events-view" id="events-view">
      <div class="events-table" id="events-list"></div>
    </div>
  </main>

  <aside class="sidebar-right">
    <div class="sidebar-section"><div class="section-label">Apparaat Details</div></div>
    <div class="detail-wrap">
      <div class="detail-empty" id="detail-empty">
        <span style="font-size:32px;opacity:.3">🖥️</span>
        Selecteer een apparaat
      </div>
      <div id="detail-content" style="display:none"></div>
    </div>
    <div class="edit-form" id="edit-form">
      <div class="form-row"><label>Naam</label><input class="form-input" id="f-name" placeholder="Mijn Apparaat"></div>
      <div class="form-row"><label>IP-adres</label><input class="form-input" id="f-ip" placeholder="192.168.1.x"></div>
      <div class="form-row"><label>MAC-adres</label><input class="form-input" id="f-mac" placeholder="AA:BB:CC:DD:EE:FF"></div>
      <div class="form-row">
        <label>Type</label>
        <select class="form-select" id="f-type">
          <option value="">— Selecteer type —</option>
          <option>Router/Gateway</option><option>Switch</option>
          <option>Desktop/PC</option><option>Laptop</option>
          <option>Smartphone</option><option>Tablet</option>
          <option>Printer</option><option>Smart TV</option>
          <option>Smart Speaker</option><option>Smart Home Hub</option>
          <option>NAS/Server</option><option>Raspberry Pi</option>
          <option>Game Console</option><option>IP Camera</option>
          <option>Onbekend</option>
        </select>
      </div>
      <div class="form-row"><label>Notities</label><input class="form-input" id="f-notes" placeholder="Optioneel..."></div>
      <div class="form-row" style="display:flex;align-items:center;gap:8px">
        <input type="checkbox" id="f-trusted" style="accent-color:var(--green)">
        <label for="f-trusted" style="font-size:12px;color:var(--text);margin-bottom:0">Vertrouwd apparaat</label>
      </div>
      <div class="form-actions">
        <button class="btn-cancel" onclick="closeEdit()">Annuleer</button>
        <button class="btn-danger" id="btn-delete" onclick="deleteSelected()" style="display:none">🗑</button>
        <button class="btn-save" id="btn-save" onclick="saveDevice()">Opslaan</button>
      </div>
    </div>
    <div class="export-panel">
      <div class="section-label" style="margin-bottom:0">Exporteren</div>
      <div class="export-row">
        <a class="btn-export" href="api/?action=export_csv" target="_blank">📄 CSV</a>
        <a class="btn-export pdf" href="api/?action=export_html" target="_blank">🖨️ PDF</a>
      </div>
    </div>
    <div class="log-panel">
      <div class="log-header">
        <div class="log-title">Live Log</div>
        <button class="btn-cancel" style="padding:2px 8px;font-size:10px" onclick="clearLog()">wis</button>
      </div>
      <div class="log-body" id="log-body"></div>
    </div>
  </aside>
</div>

<div id="tooltip"></div>
<div class="toast" id="toast"></div>

<script>
// ── Config ────────────────────────────────────────────────────
const API = 'api/?action=';
const POLL_INTERVAL = 8000; // ms tussen polls

// ── State ─────────────────────────────────────────────────────
let devices = [], events = [], selectedId = null, editingId = null;
let isScanning = false, currentView = 'topo';
let nodes = [], hoveredNode = null, animFrame = null;
let pollTimer = null, lastPollTime = new Date().toISOString();
let scanBarTimer = null;

// ── Thema ─────────────────────────────────────────────────────
function initTheme() {
  const t = localStorage.getItem('nm_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
  document.getElementById('theme-lbl').textContent = t === 'dark' ? '🌙' : '☀️';
}
function toggleTheme() {
  const t = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('nm_theme', t);
  document.getElementById('theme-lbl').textContent = t === 'dark' ? '🌙' : '☀️';
}

// ── Subnet ────────────────────────────────────────────────────
async function loadInterfaces() {
  try {
    const r = await fetch(API + 'interfaces');
    const d = await r.json();
    const sel = document.getElementById('subnet-select');
    if (d.ok && d.interfaces.length) {
      sel.innerHTML = d.interfaces.map(i => `<option value="${i.subnet}">${i.subnet} (${i.iface})</option>`).join('');
      const saved = localStorage.getItem('nm_subnet');
      if (saved && d.interfaces.find(i => i.subnet === saved)) sel.value = saved;
    }
  } catch(e) { log('Interface detectie mislukt', 'warn'); }
}
function saveSubnet() {
  localStorage.setItem('nm_subnet', document.getElementById('subnet-select').value);
}

// ── Polling i.p.v. WebSocket ──────────────────────────────────
async function poll() {
  try {
    const r = await fetch(`${API}poll&since=${encodeURIComponent(lastPollTime)}`);
    const d = await r.json();
    if (!d.ok) return;

    setPollStatus(d.scanning ? 'scan' : 'ok', d.scanning ? 'Scannen...' : 'Live');

    // Nieuwe events verwerken
    if (d.events && d.events.length > 0) {
      d.events.forEach(ev => addEvent(ev));
      lastPollTime = d.events[0].occurred_at || new Date().toISOString();
    }

    if (d.scanning !== isScanning) {
      isScanning = d.scanning;
      setScanUI(isScanning);
    }

    devices = d.devices || [];
    updateAll();
    if (selectedId) refreshDetail();

  } catch(e) {
    setPollStatus('', 'Verbindingsfout');
  }
}

function setPollStatus(state, label) {
  document.getElementById('pdot').className = 'pdot ' + state;
  document.getElementById('plabel').textContent = label;
}

function startPolling() {
  poll();
  pollTimer = setInterval(poll, POLL_INTERVAL);
}

// ── Scan triggeren ────────────────────────────────────────────
async function triggerScan() {
  if (isScanning) { toast('Scan al bezig...', 'warn'); return; }
  const subnet = document.getElementById('subnet-select').value;
  isScanning = true;
  setScanUI(true);
  startProgressAnim();
  log(`Scan gestart: ${subnet}`, 'inf');
  try {
    const r = await fetch(API + 'scan', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ subnet }),
    });
    const d = await r.json();
    isScanning = false;
    setScanUI(false);
    stopProgressAnim();
    if (d.ok) {
      log(`Scan klaar: ${d.hosts} hosts in ${d.duration}ms (${d.scan_type})`, 'ok');
      if (d.events) d.events.forEach(addEvent);
      toast(`Scan klaar — ${d.hosts} hosts gevonden`);
      poll(); // direct updaten
    } else {
      log('Scan fout: ' + d.error, 'err');
      toast('Scan mislukt', 'err');
    }
  } catch(e) {
    isScanning = false;
    setScanUI(false);
    stopProgressAnim();
    log('Scan verbindingsfout', 'err');
    toast('Verbindingsfout', 'err');
  }
}

function setScanUI(scanning) {
  const btn = document.getElementById('scan-btn');
  btn.textContent = scanning ? '⏹ Scannen...' : '▶ Scan Nu';
  btn.classList.toggle('scanning', scanning);
  btn.disabled = scanning;
}

function startProgressAnim() {
  let p = 0;
  const bar = document.getElementById('scan-bar');
  bar.style.width = '0%';
  scanBarTimer = setInterval(() => { p = Math.min(p + Math.random()*8+2, 92); bar.style.width = p+'%'; }, 200);
}
function stopProgressAnim() {
  clearInterval(scanBarTimer);
  const bar = document.getElementById('scan-bar');
  bar.style.width = '100%';
  setTimeout(() => { bar.style.width = '0%'; }, 600);
}

// ── UI Updates ─────────────────────────────────────────────────
function updateAll() {
  updateStats();
  renderList();
  layoutNodes();
  if (currentView === 'topo' && !animFrame) drawTopo();
}

function updateStats() {
  document.getElementById('s-total').textContent   = devices.length;
  document.getElementById('s-online').textContent  = devices.filter(d=>d.status==='online').length;
  document.getElementById('s-idle').textContent    = devices.filter(d=>d.status==='idle').length;
  document.getElementById('s-offline').textContent = devices.filter(d=>d.status==='offline').length;
}

function getIcon(d) {
  const s = ((d.name||'')+(d.vendor||'')+(d.device_type||'')).toLowerCase();
  if (s.includes('router')||s.includes('gateway')) return '🌐';
  if (s.includes('laptop')||s.includes('notebook')) return '💻';
  if (s.includes('desktop')||s.includes('pc')) return '🖥️';
  if (s.includes('server')||s.includes('nas')) return '🗄️';
  if (s.includes('phone')||s.includes('smartphone')) return '📱';
  if (s.includes('tablet')) return '📱';
  if (s.includes('printer')) return '🖨️';
  if (s.includes('tv')||s.includes('television')) return '📺';
  if (s.includes('speaker')||s.includes('sonos')) return '🔊';
  if (s.includes('console')||s.includes('game')) return '🎮';
  if (s.includes('raspberry')) return '🍓';
  if (s.includes('camera')) return '📷';
  if (s.includes('light')||s.includes('hue')) return '💡';
  return '💻';
}

function pingBadge(ms) {
  if (ms == null) return '<span class="ping-badge pn">—</span>';
  const cls = ms < 10 ? 'pg' : ms < 50 ? 'po' : 'ps';
  return `<span class="ping-badge ${cls}">${ms}ms</span>`;
}

function renderList() {
  const q = document.getElementById('search-input').value.toLowerCase();
  const sorted = [...devices]
    .filter(d => !q || (d.name||'').toLowerCase().includes(q) || (d.ip||'').includes(q) || (d.mac||'').toLowerCase().includes(q))
    .sort((a,b) => {
      if (a.is_gateway) return -1; if (b.is_gateway) return 1;
      return ({online:0,idle:1,offline:2}[a.status]||2) - ({online:0,idle:1,offline:2}[b.status]||2);
    });
  document.getElementById('device-list').innerHTML = sorted.map(d => `
    <div class="device-item ${d.id==selectedId?'active':''}" onclick="selectDevice(${d.id})" id="di-${d.id}">
      <div class="d-icon">${getIcon(d)}</div>
      <div class="d-info">
        <div class="d-name">${d.name||'(onbekend)'}</div>
        <div class="d-meta"><span class="d-ip">${d.ip||d.mac||'—'}</span>${pingBadge(d.ping_ms)}</div>
      </div>
      <div class="d-dot ${d.status||'offline'}"></div>
    </div>`).join('');
}

function setView(view, btn) {
  currentView = view;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('topo-view').style.display   = view==='topo'?'flex':'none';
  document.getElementById('events-view').classList.toggle('active', view==='events');
  if (view==='topo') { if (!animFrame) drawTopo(); }
  else { cancelAnimationFrame(animFrame); animFrame=null; renderEvents(); }
}

// ── Topology Canvas ────────────────────────────────────────────
const canvas = document.getElementById('topo-canvas');
const ctx = canvas.getContext('2d');

function layoutNodes() {
  const W=canvas.offsetWidth||600, H=canvas.offsetHeight||400;
  canvas.width=W; canvas.height=H;
  const cx=W/2, cy=H/2-10, r=Math.min(W*.36,H*.36);
  nodes=[];
  const gw=devices.find(d=>d.is_gateway)||devices[0];
  if(gw) nodes.push({d:gw,x:cx,y:cy,r:32,hub:true});
  devices.filter(d=>!d.is_gateway).forEach((d,i,arr)=>{
    const a=(i/arr.length)*Math.PI*2-Math.PI/2;
    nodes.push({d,x:cx+Math.cos(a)*r,y:cy+Math.sin(a)*r,r:22,hub:false});
  });
}

function drawTopo() {
  const W=canvas.width,H=canvas.height,now=Date.now();
  ctx.clearRect(0,0,W,H);
  if(!nodes.length){animFrame=requestAnimationFrame(drawTopo);return}
  const hub=nodes.find(n=>n.hub);
  if(!hub){animFrame=requestAnimationFrame(drawTopo);return}
  const isDark=document.documentElement.getAttribute('data-theme')!=='light';

  nodes.forEach(n=>{
    if(n===hub) return;
    const st=n.d.status;
    ctx.beginPath(); ctx.moveTo(hub.x,hub.y); ctx.lineTo(n.x,n.y);
    if(st==='online'){
      ctx.strokeStyle=`rgba(0,229,255,.5)`;ctx.setLineDash([]);ctx.lineWidth=1;ctx.stroke();
      const t=((now/1200*(1+nodes.indexOf(n)*.2))%1);
      const px=hub.x+(n.x-hub.x)*t,py=hub.y+(n.y-hub.y)*t;
      ctx.beginPath();ctx.arc(px,py,2,0,Math.PI*2);ctx.fillStyle='rgba(0,229,255,.9)';ctx.fill();
    } else {
      ctx.strokeStyle=`rgba(74,96,128,.3)`;ctx.setLineDash([4,6]);ctx.lineWidth=1;ctx.stroke();ctx.setLineDash([]);
    }
  });

  nodes.forEach(n=>{
    const {x,y,r,d,hub}=n;
    const sel=d.id===selectedId,hov=n===hoveredNode;
    if(sel||hov){
      const g=ctx.createRadialGradient(x,y,r,x,y,r+18);
      g.addColorStop(0,'rgba(0,229,255,.2)');g.addColorStop(1,'rgba(0,229,255,0)');
      ctx.beginPath();ctx.arc(x,y,r+18,0,Math.PI*2);ctx.fillStyle=g;ctx.fill();
    }
    if(hub){
      const p=(Math.sin(now/700)+1)/2;
      ctx.beginPath();ctx.arc(x,y,r+5+p*10,0,Math.PI*2);
      ctx.strokeStyle=`rgba(0,229,255,${.25-p*.15})`;ctx.lineWidth=1;ctx.stroke();
    }
    const gf=ctx.createRadialGradient(x-r/3,y-r/3,0,x,y,r);
    gf.addColorStop(0,isDark?'#1a2133':'#f0f4f8');gf.addColorStop(1,isDark?'#0c1018':'#e8edf5');
    ctx.beginPath();ctx.arc(x,y,r,0,Math.PI*2);ctx.fillStyle=hub?(isDark?'#0c1018':'#fff'):gf;ctx.fill();
    const bc=d.status==='online'?'#00e5ff':d.status==='idle'?'#fbbf24':'#2a3a55';
    ctx.beginPath();ctx.arc(x,y,r,0,Math.PI*2);ctx.strokeStyle=sel?'#fff':bc;ctx.lineWidth=sel?2:1.5;ctx.stroke();
    if(d.status==='online'&&d.ping_ms!=null){
      const pc=d.ping_ms<10?'#22d3a0':d.ping_ms<50?'#fbbf24':'#f87171';
      ctx.font='bold 9px DM Mono,monospace';ctx.textAlign='center';ctx.textBaseline='bottom';
      ctx.fillStyle=pc;ctx.fillText(d.ping_ms+'ms',x,y-r-3);
    }
    ctx.font=`${hub?16:13}px serif`;ctx.textAlign='center';ctx.textBaseline='middle';
    ctx.fillStyle=isDark?'#e2eaf8':'#0f172a';ctx.fillText(getIcon(d),x,y);
    const lbl=(d.name||d.mac||'?');
    ctx.font=`${sel?'700':'400'} ${hub?11:10}px Outfit,sans-serif`;
    ctx.fillStyle=sel?(isDark?'#fff':'#000'):isDark?'#94a3b8':'#64748b';
    ctx.textBaseline='top';ctx.fillText(lbl.length>14?lbl.substring(0,13)+'…':lbl,x,y+r+4);
  });
  animFrame=requestAnimationFrame(drawTopo);
}

canvas.addEventListener('mousemove',e=>{
  const rect=canvas.getBoundingClientRect(),mx=e.clientX-rect.left,my=e.clientY-rect.top;
  hoveredNode=null;
  for(const n of nodes){if(Math.hypot(mx-n.x,my-n.y)<n.r+8){hoveredNode=n;break;}}
  canvas.style.cursor=hoveredNode?'pointer':'default';
  const tip=document.getElementById('tooltip');
  if(hoveredNode){
    const d=hoveredNode.d;
    tip.style.display='block';tip.style.left=(e.clientX+14)+'px';tip.style.top=(e.clientY-10)+'px';
    tip.innerHTML=`<b>${d.name||'(onbekend)'}</b><br>${d.ip||'—'} · ${d.mac||'—'}<br>${d.vendor||'—'}<br>Ping: ${d.ping_ms!=null?d.ping_ms+'ms':'—'}`;
  } else tip.style.display='none';
});
canvas.addEventListener('click',e=>{
  const rect=canvas.getBoundingClientRect(),mx=e.clientX-rect.left,my=e.clientY-rect.top;
  for(const n of nodes){if(Math.hypot(mx-n.x,my-n.y)<n.r+8){selectDevice(n.d.id);return;}}
  selectedId=null;clearDetail();
});
canvas.addEventListener('mouseleave',()=>{document.getElementById('tooltip').style.display='none';hoveredNode=null;});
window.addEventListener('resize',layoutNodes);

// ── Detail ─────────────────────────────────────────────────────
function selectDevice(id){selectedId=id;renderList();refreshDetail();}
function refreshDetail(){
  const d=devices.find(x=>x.id==selectedId);
  if(!d) return clearDetail();
  document.getElementById('detail-empty').style.display='none';
  const dc=document.getElementById('detail-content');dc.style.display='block';
  const sb=d.status==='online'?'<span class="badge badge-g">Online</span>':d.status==='idle'?'<span class="badge badge-y">Idle</span>':'<span class="badge badge-r">Offline</span>';
  const fmt=dt=>dt?new Date(dt).toLocaleString('nl-NL',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'}):'—';
  dc.innerHTML=`
    <div class="detail-header">
      <div class="detail-big-icon">${getIcon(d)}</div>
      <div><div class="detail-name">${d.name||'(onbekend)'}</div><div class="detail-vendor">${d.vendor||'—'}</div></div>
    </div>
    <div class="detail-rows">
      <div class="dr"><span class="dr-key">Status</span><span class="dr-val">${sb}</span></div>
      <div class="dr"><span class="dr-key">IP-adres</span><span class="dr-val">${d.ip||'—'}</span></div>
      <div class="dr"><span class="dr-key">MAC</span><span class="dr-val">${d.mac||'—'}</span></div>
      <div class="dr"><span class="dr-key">Hostname</span><span class="dr-val">${d.hostname||'—'}</span></div>
      <div class="dr"><span class="dr-key">Type</span><span class="dr-val">${d.device_type||'—'}</span></div>
      <div class="dr"><span class="dr-key">Ping</span><span class="dr-val">${pingBadge(d.ping_ms)}</span></div>
      <div class="dr"><span class="dr-key">Vertrouwd</span><span class="dr-val">${d.is_trusted?'<span class="badge badge-g">Ja</span>':'<span class="badge badge-r">Nee</span>'}</span></div>
      <div class="dr"><span class="dr-key">Eerste gezien</span><span class="dr-val">${fmt(d.first_seen)}</span></div>
      <div class="dr"><span class="dr-key">Laatste gezien</span><span class="dr-val">${fmt(d.last_seen)}</span></div>
      ${d.notes?`<div class="dr"><span class="dr-key">Notities</span><span class="dr-val">${d.notes}</span></div>`:''}
    </div>
    <div style="padding:0 16px 12px;display:flex;gap:6px">
      <button class="btn btn-ghost" style="flex:1;justify-content:center;font-size:12px" onclick="openEdit(${d.id})">✏️ Bewerk</button>
    </div>`;
}
function clearDetail(){
  document.getElementById('detail-empty').style.display='flex';
  const dc=document.getElementById('detail-content');dc.style.display='none';dc.innerHTML='';
}

// ── Edit / Add ─────────────────────────────────────────────────
function openAdd(){
  editingId=null;clearForm();
  document.getElementById('f-mac').removeAttribute('readonly');
  document.getElementById('btn-delete').style.display='none';
  document.getElementById('btn-save').textContent='Toevoegen';
  document.getElementById('edit-form').classList.add('open');
  document.getElementById('f-name').focus();
}
function openEdit(id){
  const d=devices.find(x=>x.id==id);if(!d) return;
  editingId=id;
  document.getElementById('f-name').value=d.name||'';
  document.getElementById('f-ip').value=d.ip||'';
  document.getElementById('f-mac').value=d.mac||'';
  document.getElementById('f-mac').setAttribute('readonly',true);
  document.getElementById('f-type').value=d.device_type||'';
  document.getElementById('f-notes').value=d.notes||'';
  document.getElementById('f-trusted').checked=!!d.is_trusted;
  document.getElementById('btn-delete').style.display='inline-block';
  document.getElementById('btn-save').textContent='Opslaan';
  document.getElementById('edit-form').classList.add('open');
}
function closeEdit(){document.getElementById('edit-form').classList.remove('open');editingId=null;}
function clearForm(){
  ['f-name','f-ip','f-mac','f-notes'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('f-type').selectedIndex=0;
  document.getElementById('f-trusted').checked=false;
}
async function saveDevice(){
  const mac=document.getElementById('f-mac').value.trim().toUpperCase();
  const name=document.getElementById('f-name').value.trim();
  if(!mac){toast('MAC verplicht','warn');return;}
  const body={mac,name,ip:document.getElementById('f-ip').value.trim(),device_type:document.getElementById('f-type').value,notes:document.getElementById('f-notes').value.trim(),is_trusted:document.getElementById('f-trusted').checked};
  if(editingId) body.id=editingId;
  try{
    const r=await fetch(API+'devices',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
    const d=await r.json();
    if(!d.ok) throw new Error(d.error);
    toast(editingId?'Bijgewerkt!':'Toegevoegd!');
    closeEdit();log(`${editingId?'Bijgewerkt':'Toegevoegd'}: ${name||mac}`,'ok');
    poll();
  }catch(e){toast('Fout: '+e.message,'err');}
}
async function deleteSelected(){
  if(!editingId) return;
  const d=devices.find(x=>x.id==editingId);
  if(!confirm(`Verwijder "${d?.name||d?.mac}"?`)) return;
  try{
    const r=await fetch(`${API}devices&id=${editingId}`,{method:'DELETE'});
    const data=await r.json();
    if(!data.ok) throw new Error(data.error);
    toast('Verwijderd','warn');closeEdit();
    log(`Verwijderd: ${d?.name||d?.mac}`,'warn');
    if(selectedId==editingId){selectedId=null;clearDetail();}
    poll();
  }catch(e){toast('Fout: '+e.message,'err');}
}

// ── Events ─────────────────────────────────────────────────────
function addEvent(ev){
  events.unshift({...ev,ts:new Date()});
  if(events.length>100) events.pop();
  if(currentView==='events') renderEvents();
  if(ev.event_type==='new'){
    toast(`⚠️ Nieuw apparaat: ${ev.new_value} (${ev.mac})`,'warn');
    log(`Nieuw apparaat: ${ev.mac} @ ${ev.new_value}`,'warn');
  }
}
function renderEvents(){
  const list=document.getElementById('events-list');
  if(!events.length){list.innerHTML='<div style="text-align:center;padding:40px;color:var(--muted);font-size:12px">Nog geen events</div>';return;}
  list.innerHTML=events.map(ev=>{
    const t=ev.ts?new Date(ev.ts).toLocaleTimeString('nl-NL'):'—';
    const type=ev.event_type||ev.type||'info';
    const msg=type==='new'?`Nieuw apparaat: ${ev.new_value||'?'} (${ev.mac})`
             :type==='ip_changed'?`IP gewijzigd: ${ev.mac} — ${ev.old_value} → ${ev.new_value}`
             :type==='offline'?`Offline: ${ev.device_name||ev.mac}`
             :JSON.stringify(ev);
    return `<div class="event-row"><span class="ebadge b-${type}">${type}</span><span style="flex:1;font-size:12px">${msg}</span><span style="font-family:'DM Mono',monospace;font-size:10px;color:var(--muted);flex-shrink:0">${t}</span></div>`;
  }).join('');
}

// ── Log / Toast ────────────────────────────────────────────────
function log(msg,type=''){
  const body=document.getElementById('log-body');
  const t=new Date().toLocaleTimeString('nl-NL',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  const line=document.createElement('div');line.className='log-line '+type;
  line.innerHTML=`<span class="log-t">${t}</span><span class="log-m">${msg}</span>`;
  body.appendChild(line);body.scrollTop=body.scrollHeight;
  while(body.children.length>80) body.removeChild(body.firstChild);
}
function clearLog(){document.getElementById('log-body').innerHTML='';}
let toastTimer;
function toast(msg,type=''){
  const el=document.getElementById('toast');el.textContent=msg;
  el.className=`toast ${type} show`;clearTimeout(toastTimer);
  toastTimer=setTimeout(()=>el.classList.remove('show'),3000);
}

// ── Init ───────────────────────────────────────────────────────
window.addEventListener('load',async()=>{
  initTheme();
  await loadInterfaces();
  startPolling();
  log('NetMap v2.1 PHP gestart','inf');
  log('Poll interval: <?= SCAN_INTERVAL ?>min auto-scan','inf');
});
</script>
</body></html>
