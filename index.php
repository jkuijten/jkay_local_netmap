<?php
require_once __DIR__ . '/includes/config.php';
?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NetMap v2.2 — DS1511</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Outfit:wght@400;600;700;900&display=swap');
:root{--bg:#07090f;--bg2:#0c1018;--bg3:#121722;--bg4:#1a2133;--border:#1e2d45;--border2:#263550;--accent:#00e5ff;--accent2:#6366f1;--green:#22d3a0;--yellow:#fbbf24;--red:#f87171;--text:#e2eaf8;--muted:#4a6080}
[data-theme=light]{--bg:#f0f4f8;--bg2:#fff;--bg3:#e8edf5;--bg4:#dce4f0;--border:#c8d6e8;--border2:#a8bdd8;--accent:#0284c7;--accent2:#6366f1;--green:#059669;--yellow:#d97706;--red:#dc2626;--text:#0f172a;--muted:#64748b}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{background:var(--bg);color:var(--text);font-family:'Outfit',sans-serif;font-size:14px;transition:background .3s,color .3s}
.layout{display:grid;grid-template-rows:52px 1fr;grid-template-columns:260px 1fr 300px;height:100vh}

/* TOPBAR */
.topbar{grid-column:1/-1;display:flex;align-items:center;gap:10px;padding:0 16px;background:rgba(7,9,15,.96);border-bottom:1px solid var(--border);backdrop-filter:blur(12px);transition:background .3s}
[data-theme=light] .topbar{background:rgba(255,255,255,.96)}
.logo{display:flex;align-items:center;gap:10px}
.logo-mark{width:30px;height:30px;border:1.5px solid var(--accent);border-radius:7px;display:grid;place-items:center;position:relative}
.logo-mark::before{content:'';position:absolute;width:10px;height:10px;border-radius:50%;background:var(--accent);animation:cpulse 2.5s ease-in-out infinite}
@keyframes cpulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(0,229,255,.4)}50%{transform:scale(1.15);box-shadow:0 0 0 6px rgba(0,229,255,0)}}
.logo-name{font-size:17px;font-weight:900;letter-spacing:-.5px}.logo-name em{color:var(--accent);font-style:normal}
.divider{width:1px;height:24px;background:var(--border);margin:0 2px;flex-shrink:0}
.poll-status{display:flex;align-items:center;gap:6px;font-family:'DM Mono',monospace;font-size:11px;color:var(--muted)}
.pdot{width:6px;height:6px;border-radius:50%;background:var(--red);transition:background .3s}
.pdot.ok{background:var(--green);animation:glow 2s infinite}.pdot.scan{background:var(--yellow);animation:glowy .5s infinite}
@keyframes glow{0%,100%{box-shadow:0 0 0 0 rgba(34,211,160,.4)}50%{box-shadow:0 0 0 4px rgba(34,211,160,0)}}
@keyframes glowy{0%,100%{opacity:1}50%{opacity:.4}}
.subnet-sel{display:flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:4px 4px 4px 10px}
.subnet-sel label{font-family:'DM Mono',monospace;font-size:10px;color:var(--muted);white-space:nowrap}
.subnet-select{background:var(--bg4);border:1px solid var(--border2);border-radius:6px;padding:3px 8px;color:var(--text);font-family:'DM Mono',monospace;font-size:11px;outline:none;cursor:pointer;min-width:130px}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:8px}
.theme-toggle{width:34px;height:20px;background:var(--bg4);border:1px solid var(--border);border-radius:10px;cursor:pointer;position:relative;transition:background .3s;flex-shrink:0}
.theme-toggle::after{content:'';position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:var(--muted);transition:transform .3s,background .3s}
[data-theme=light] .theme-toggle{background:var(--accent)}[data-theme=light] .theme-toggle::after{transform:translateX(14px);background:#fff}
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
.search-box input:focus{border-color:var(--accent)}.search-box input::placeholder{color:var(--muted)}
.search-icon{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none;font-size:13px}
.device-list-wrap{flex:1;overflow-y:auto;padding:8px}
.device-list-wrap::-webkit-scrollbar{width:3px}.device-list-wrap::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.device-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:9px;cursor:pointer;transition:background .15s,border-color .15s;border:1px solid transparent;margin-bottom:2px}
.device-item:hover{background:var(--bg3)}.device-item.active{background:rgba(0,229,255,.07);border-color:rgba(0,229,255,.2)}
.d-icon{width:34px;height:34px;border-radius:8px;background:var(--bg4);border:1px solid var(--border);display:grid;place-items:center;flex-shrink:0;font-size:16px}
.d-info{flex:1;min-width:0}.d-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text)}
.d-meta{display:flex;align-items:center;gap:6px;margin-top:2px}.d-ip{font-family:'DM Mono',monospace;font-size:10px;color:var(--muted)}
.ping-badge{font-family:'DM Mono',monospace;font-size:9px;font-weight:500;padding:1px 5px;border-radius:3px;white-space:nowrap}
.pg{background:rgba(34,211,160,.12);color:var(--green)}.po{background:rgba(251,191,36,.12);color:var(--yellow)}.ps{background:rgba(248,113,113,.12);color:var(--red)}.pn{color:var(--muted)}
.d-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}.d-dot.online{background:var(--green)}.d-dot.offline{background:var(--red)}.d-dot.idle{background:var(--yellow)}

/* MAIN CENTER */
.main-center{display:flex;flex-direction:column;overflow:hidden;border-right:1px solid var(--border)}
.scan-bar-wrap{height:2px;background:var(--bg3)}.scan-bar-fill{height:100%;width:0%;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .4s ease}
.view-tabs{display:flex;align-items:center;gap:2px;padding:8px 12px;border-bottom:1px solid var(--border);background:var(--bg2)}
.tab{padding:5px 12px;border-radius:6px;border:none;background:transparent;color:var(--muted);font-family:'Outfit',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
.tab.active{background:var(--bg4);color:var(--text);border:1px solid var(--border2)}

/* Topo controls */
.topo-controls{margin-left:auto;display:flex;align-items:center;gap:6px}
.topo-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:var(--bg3);color:var(--muted);font-size:14px;cursor:pointer;display:grid;place-items:center;transition:all .15s;font-weight:700}
.topo-btn:hover{border-color:var(--accent);color:var(--accent)}
.zoom-label{font-family:'DM Mono',monospace;font-size:10px;color:var(--muted);min-width:36px;text-align:center}

/* Canvas */
.canvas-wrap{flex:1;position:relative;overflow:hidden;background:var(--bg)}
#topo-canvas{display:block;position:absolute;top:0;left:0;cursor:grab}
#topo-canvas.dragging-canvas{cursor:grabbing}
#topo-canvas.dragging-node{cursor:move}

/* Lijst view */
.list-view{display:none;flex-direction:column;flex:1;overflow:hidden}
.list-view.active{display:flex}
.list-table-wrap{overflow-y:auto;flex:1;padding:8px 12px}
.list-table-wrap::-webkit-scrollbar{width:3px}.list-table-wrap::-webkit-scrollbar-thumb{background:var(--border)}
.list-table{width:100%;border-collapse:collapse;font-size:12px}
.list-table th{background:var(--bg3);color:var(--muted);font-family:'DM Mono',monospace;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:8px 12px;text-align:left;position:sticky;top:0;border-bottom:1px solid var(--border);cursor:pointer;user-select:none}
.list-table th:hover{color:var(--accent)}
.list-table td{padding:7px 12px;border-bottom:1px solid rgba(30,45,69,.4);color:var(--text)}
.list-table tr:hover td{background:var(--bg3);cursor:pointer}
.list-table tr.selected td{background:rgba(0,229,255,.06)}
.mono{font-family:'DM Mono',monospace;font-size:11px}

/* Events */
.events-view{display:none;flex-direction:column;flex:1;overflow:hidden}
.events-view.active{display:flex}
.events-table{overflow-y:auto;flex:1;padding:12px}
.events-table::-webkit-scrollbar{width:3px}
.event-row{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);margin-bottom:6px;animation:sin .3s ease}
@keyframes sin{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.ebadge{padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:.5px;flex-shrink:0}
.b-new{background:rgba(0,229,255,.12);color:var(--accent)}.b-ip_changed{background:rgba(251,191,36,.12);color:var(--yellow)}.b-offline{background:rgba(248,113,113,.12);color:var(--red)}.b-online{background:rgba(34,211,160,.12);color:var(--green)}

/* SIDEBAR RIGHT */
.sidebar-right{background:var(--bg2);display:flex;flex-direction:column;overflow:hidden;transition:background .3s}
.detail-wrap{flex:1;overflow-y:auto}.detail-wrap::-webkit-scrollbar{width:3px}.detail-wrap::-webkit-scrollbar-thumb{background:var(--border)}
.detail-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;color:var(--muted);font-size:12px;gap:10px;text-align:center;padding:20px}
.detail-header{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border);background:var(--bg3);transition:background .3s}
.detail-big-icon{width:52px;height:52px;border-radius:14px;background:var(--bg4);border:1px solid var(--border);display:grid;place-items:center;flex-shrink:0;font-size:26px}
.detail-name{font-size:16px;font-weight:700;color:var(--text)}.detail-vendor{font-size:11px;color:var(--muted);font-family:'DM Mono',monospace;margin-top:2px}
.detail-rows{padding:12px 16px}
.dr{display:flex;justify-content:space-between;align-items:flex-start;padding:7px 0;border-bottom:1px solid rgba(30,45,69,.6);gap:8px}.dr:last-child{border-bottom:none}
.dr-key{font-size:11px;color:var(--muted);font-family:'DM Mono',monospace;flex-shrink:0}.dr-val{font-size:11px;color:var(--text);font-family:'DM Mono',monospace;text-align:right;word-break:break-all;max-width:160px}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;font-family:'DM Mono',monospace}
.badge-g{background:rgba(34,211,160,.12);color:var(--green)}.badge-y{background:rgba(251,191,36,.12);color:var(--yellow)}.badge-r{background:rgba(248,113,113,.12);color:var(--red)}
.edit-form{padding:12px 16px;border-top:1px solid var(--border);background:var(--bg3);display:none;transition:background .3s}.edit-form.open{display:block}
.form-row{margin-bottom:8px}.form-row label{display:block;font-size:10px;color:var(--muted);margin-bottom:3px;font-family:'DM Mono',monospace}
.form-input,.form-select{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:7px 10px;color:var(--text);font-size:12px;font-family:'DM Mono',monospace;outline:none;transition:border-color .2s,background .3s}
.form-input:focus,.form-select:focus{border-color:var(--accent)}.form-input::placeholder{color:var(--muted)}
.form-actions{display:flex;gap:6px;margin-top:10px}
.btn-save{padding:7px 14px;background:var(--accent);color:var(--bg);border:none;border-radius:7px;font-weight:700;font-size:12px;cursor:pointer;flex:1}
.btn-cancel{padding:7px 12px;background:var(--bg4);border:1px solid var(--border);color:var(--muted);border-radius:7px;font-size:12px;cursor:pointer}
.btn-danger{padding:7px 12px;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.2);color:var(--red);border-radius:7px;font-size:12px;cursor:pointer}
.export-panel{padding:10px 14px;border-top:1px solid var(--border);background:var(--bg3);transition:background .3s}
.export-row{display:flex;gap:8px;margin-top:8px}
.btn-export{flex:1;padding:7px;border-radius:8px;border:1px solid var(--border);background:var(--bg4);color:var(--text);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:5px;font-family:'Outfit',sans-serif;text-decoration:none}
.btn-export:hover{border-color:var(--accent);color:var(--accent)}.btn-export.pdf:hover{border-color:var(--red);color:var(--red)}
.log-panel{height:155px;border-top:1px solid var(--border);display:flex;flex-direction:column}
.log-header{display:flex;align-items:center;justify-content:space-between;padding:7px 14px;border-bottom:1px solid var(--border);background:var(--bg3);transition:background .3s}
.log-title{font-size:10px;font-weight:700;letter-spacing:1.5px;color:var(--muted);text-transform:uppercase;font-family:'DM Mono',monospace}
.log-body{flex:1;overflow-y:auto;padding:8px 12px;font-family:'DM Mono',monospace;font-size:10px}
.log-body::-webkit-scrollbar{width:3px}.log-body::-webkit-scrollbar-thumb{background:var(--border)}
.log-line{display:flex;gap:8px;padding:2px 0;color:var(--muted);animation:fu .25s ease}
@keyframes fu{from{opacity:0;transform:translateY(3px)}to{opacity:1;transform:translateY(0)}}
.log-t{color:var(--accent2);flex-shrink:0}
.log-line.ok .log-m{color:var(--green)}.log-line.err .log-m{color:var(--red)}.log-line.inf .log-m{color:var(--accent)}.log-line.warn .log-m{color:var(--yellow)}
#tooltip{position:fixed;background:var(--bg4);border:1px solid var(--border2);border-radius:8px;padding:8px 12px;font-family:'DM Mono',monospace;font-size:10px;color:var(--text);pointer-events:none;z-index:999;display:none;line-height:1.7;box-shadow:0 4px 20px rgba(0,0,0,.5)}
.toast{position:fixed;bottom:20px;right:20px;background:var(--bg4);border:1px solid var(--border);border-left:3px solid var(--green);border-radius:10px;padding:10px 16px;font-size:13px;color:var(--text);transform:translateY(60px);opacity:0;transition:all .3s cubic-bezier(.34,1.56,.64,1);z-index:200;max-width:280px}
.toast.show{transform:translateY(0);opacity:1}.toast.warn{border-left-color:var(--yellow)}.toast.err{border-left-color:var(--red)}
</style>
</head>
<body>
<div class="layout">
  <header class="topbar">
    <div class="logo"><div class="logo-mark"></div><div class="logo-name">Net<em>Map</em></div></div>
    <div class="divider"></div>
    <div class="poll-status"><div class="pdot" id="pdot"></div><span id="plabel">Laden...</span></div>
    <div class="divider"></div>
    <div class="subnet-sel">
      <label>Subnet</label>
      <select class="subnet-select" id="subnet-select" onchange="saveSubnet()"><option><?= htmlspecialchars(SCAN_SUBNET) ?></option></select>
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
      <button class="tab" onclick="setView('list',this)">Lijst</button>
      <button class="tab" onclick="setView('events',this)">Events</button>
      <!-- Topo controls (alleen zichtbaar bij topologie) -->
      <div class="topo-controls" id="topo-controls">
        <button class="topo-btn" onclick="zoomBy(1.25)" title="Zoom in">+</button>
        <span class="zoom-label" id="zoom-label">100%</span>
        <button class="topo-btn" onclick="zoomBy(0.8)" title="Zoom out">−</button>
        <button class="topo-btn" onclick="resetView()" title="Reset weergave">⌂</button>
        <button class="topo-btn" onclick="toggleForce()" id="force-btn" title="Force layout aan/uit">⚛</button>
      </div>
    </div>

    <!-- Topologie canvas -->
    <div class="canvas-wrap" id="topo-view">
      <canvas id="topo-canvas"></canvas>
    </div>

    <!-- Lijst view -->
    <div class="list-view" id="list-view">
      <div class="list-table-wrap">
        <table class="list-table" id="list-table">
          <thead>
            <tr>
              <th onclick="sortList('name')">Naam ↕</th>
              <th onclick="sortList('ip')">IP-adres ↕</th>
              <th onclick="sortList('vendor')">Fabrikant ↕</th>
              <th onclick="sortList('status')">Status ↕</th>
              <th onclick="sortList('ping_ms')">Ping ↕</th>
            </tr>
          </thead>
          <tbody id="list-tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- Events view -->
    <div class="events-view" id="events-view">
      <div class="events-table" id="events-list"></div>
    </div>
  </main>

  <aside class="sidebar-right">
    <div class="sidebar-section"><div class="section-label">Apparaat Details</div></div>
    <div class="detail-wrap">
      <div class="detail-empty" id="detail-empty"><span style="font-size:32px;opacity:.3">🖥️</span>Selecteer een apparaat</div>
      <div id="detail-content" style="display:none"></div>
    </div>
    <div class="edit-form" id="edit-form">
      <div class="form-row"><label>Naam</label><input class="form-input" id="f-name" placeholder="Mijn Apparaat"></div>
      <div class="form-row"><label>IP-adres</label><input class="form-input" id="f-ip" placeholder="192.168.2.x"></div>
      <div class="form-row"><label>MAC-adres</label><input class="form-input" id="f-mac" placeholder="AA:BB:CC:DD:EE:FF"></div>
      <div class="form-row">
        <label>Type</label>
        <select class="form-select" id="f-type">
          <option value="">— Selecteer type —</option>
          <option>Router/Gateway</option><option>Switch</option><option>Desktop/PC</option>
          <option>Laptop</option><option>Smartphone</option><option>Tablet</option>
          <option>Printer</option><option>Smart TV</option><option>Smart Speaker</option>
          <option>Smart Home Hub</option><option>NAS/Server</option><option>Raspberry Pi</option>
          <option>Game Console</option><option>IP Camera</option><option>Onbekend</option>
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
      <div class="section-label" style="margin-bottom:0">Exporteren & Sync</div>
      <div class="export-row">
        <a class="btn-export" href="api/?action=export_csv" target="_blank">📄 CSV</a>
        <a class="btn-export pdf" href="api/?action=export_html" target="_blank">🖨️ PDF</a>
      </div>
      <div class="export-row" style="margin-top:6px">
        <button class="btn-export" onclick="openFingbox()" style="border-color:var(--accent2);color:var(--accent2)">🔄 Fingbox Sync</button>
      </div>
    </div>

    <!-- Fingbox modal -->
    <div id="fingbox-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:300;align-items:center;justify-content:center">
      <div style="background:var(--bg3);border:1px solid var(--border);border-radius:14px;padding:24px;width:320px;box-shadow:0 8px 40px rgba(0,0,0,.5)">
        <div style="font-size:15px;font-weight:700;margin-bottom:16px;color:var(--text)">🔄 Fingbox API Sync</div>
        <div class="form-row"><label>API URL</label><input class="form-input" id="fg-url" placeholder="http://192.168.2.x:49090"></div>
        <div class="form-row"><label>API Key</label><input class="form-input" id="fg-key" placeholder="jouw-api-key" type="password"></div>
        <div id="fg-result" style="margin:10px 0;font-size:12px;font-family:'DM Mono',monospace;color:var(--muted)"></div>
        <div style="display:flex;gap:8px;margin-top:12px">
          <button class="btn-cancel" onclick="closeFingbox()" style="flex:1">Annuleer</button>
          <button class="btn-save" onclick="saveFingboxConfig()" style="flex:1;background:var(--bg4);color:var(--text);border:1px solid var(--border)">Opslaan</button>
          <button class="btn-save" onclick="runFingboxSync()" style="flex:1">Sync Nu</button>
        </div>
      </div>
    </div>
    <div class="log-panel">
      <div class="log-header"><div class="log-title">Live Log</div><button class="btn-cancel" style="padding:2px 8px;font-size:10px" onclick="clearLog()">wis</button></div>
      <div class="log-body" id="log-body"></div>
    </div>
  </aside>
</div>

<div id="tooltip"></div>
<div class="toast" id="toast"></div>

<script>
// ═══════════════════════════════════════════════════════
// CONFIGURATIE
// ═══════════════════════════════════════════════════════
var API           = 'api/?action=';
var POLL_INTERVAL = 8000;
var NODE_R        = 22;  // straal normale node
var HUB_R         = 32;  // straal gateway node

// ═══════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════
var devices    = [];
var events     = [];
var selectedId = null;
var editingId  = null;
var isScanning = false;
var currentView = 'topo';
var pollTimer   = null;
var scanBarTimer = null;
var lastPollTime = new Date().toISOString();

// Topologie state
var nodes      = [];
var animFrame  = null;
var hoveredNode = null;
var dragNode    = null;
var dragOffX    = 0, dragOffY = 0;
var panX = 0, panY = 0;
var panStartX = 0, panStartY = 0;
var isPanning  = false;
var zoomLevel  = 1.0;
var forceActive = true;
var forceTimer  = null;

// Lijst sorteer state
var sortCol = 'status';
var sortDir = 1;

// ═══════════════════════════════════════════════════════
// THEMA
// ═══════════════════════════════════════════════════════
function initTheme() {
  var t = localStorage.getItem('nm_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
  document.getElementById('theme-lbl').textContent = t === 'dark' ? '🌙' : '☀️';
}
function toggleTheme() {
  var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', cur);
  localStorage.setItem('nm_theme', cur);
  document.getElementById('theme-lbl').textContent = cur === 'dark' ? '🌙' : '☀️';
}

// ═══════════════════════════════════════════════════════
// SUBNET
// ═══════════════════════════════════════════════════════
function loadInterfaces() {
  fetch(API + 'interfaces').then(function(r){ return r.json(); }).then(function(d) {
    var sel = document.getElementById('subnet-select');
    if (d.ok && d.interfaces.length) {
      sel.innerHTML = d.interfaces.map(function(i) {
        return '<option value="' + i.subnet + '">' + i.subnet + ' (' + i.iface + ')</option>';
      }).join('');
      var saved = localStorage.getItem('nm_subnet');
      if (saved) {
        for (var i = 0; i < sel.options.length; i++) {
          if (sel.options[i].value === saved) { sel.selectedIndex = i; break; }
        }
      }
    }
  }).catch(function(){ log('Interface detectie mislukt', 'warn'); });
}
function saveSubnet() {
  localStorage.setItem('nm_subnet', document.getElementById('subnet-select').value);
}

// ═══════════════════════════════════════════════════════
// POLLING
// ═══════════════════════════════════════════════════════
function poll() {
  fetch(API + 'poll&since=' + encodeURIComponent(lastPollTime))
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (!d.ok) return;
      setPollStatus(d.scanning ? 'scan' : 'ok', d.scanning ? 'Scannen...' : 'Live');
      if (d.events && d.events.length > 0) {
        d.events.forEach(addEvent);
        lastPollTime = d.events[0].occurred_at || new Date().toISOString();
      }
      if (d.scanning !== isScanning) { isScanning = d.scanning; setScanUI(isScanning); }
      devices = d.devices || [];
      updateAll();
      if (selectedId) refreshDetail();
    }).catch(function(){ setPollStatus('', 'Verbindingsfout'); });
}
function setPollStatus(state, label) {
  document.getElementById('pdot').className = 'pdot ' + state;
  document.getElementById('plabel').textContent = label;
}
function startPolling() { poll(); pollTimer = setInterval(poll, POLL_INTERVAL); }

// ═══════════════════════════════════════════════════════
// SCAN
// ═══════════════════════════════════════════════════════
function triggerScan() {
  if (isScanning) { toast('Scan al bezig...', 'warn'); return; }
  var subnet = document.getElementById('subnet-select').value;
  isScanning = true; setScanUI(true); startProgressAnim();
  log('Scan gestart: ' + subnet, 'inf');
  fetch(API + 'scan', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({subnet: subnet})
  }).then(function(r){ return r.json(); }).then(function(d) {
    isScanning = false; setScanUI(false); stopProgressAnim();
    if (d.ok) {
      log('Scan klaar: ' + d.hosts + ' hosts in ' + d.duration + 'ms (' + d.scan_type + ')', 'ok');
      if (d.events) d.events.forEach(addEvent);
      toast('Scan klaar — ' + d.hosts + ' hosts');
      poll();
    } else {
      log('Scan fout: ' + d.error, 'err'); toast('Scan mislukt', 'err');
    }
  }).catch(function() {
    isScanning = false; setScanUI(false); stopProgressAnim();
    log('Verbindingsfout', 'err'); toast('Verbindingsfout', 'err');
  });
}
function setScanUI(scanning) {
  var btn = document.getElementById('scan-btn');
  btn.textContent = scanning ? '⏹ Scannen...' : '▶ Scan Nu';
  btn.classList.toggle('scanning', scanning);
  btn.disabled = scanning;
}
function startProgressAnim() {
  var p = 0, bar = document.getElementById('scan-bar');
  bar.style.width = '0%';
  scanBarTimer = setInterval(function() { p = Math.min(p + Math.random()*8+2, 92); bar.style.width = p+'%'; }, 200);
}
function stopProgressAnim() {
  clearInterval(scanBarTimer);
  var bar = document.getElementById('scan-bar');
  bar.style.width = '100%';
  setTimeout(function() { bar.style.width = '0%'; }, 600);
}

// ═══════════════════════════════════════════════════════
// UI UPDATES
// ═══════════════════════════════════════════════════════
function updateAll() {
  updateStats(); renderList();
  if (currentView === 'topo') { initNodes(); if (!animFrame) drawTopo(); }
  else if (currentView === 'list') renderListTable();
}

function updateStats() {
  document.getElementById('s-total').textContent   = devices.length;
  document.getElementById('s-online').textContent  = devices.filter(function(d){ return d.status==='online'; }).length;
  document.getElementById('s-idle').textContent    = devices.filter(function(d){ return d.status==='idle'; }).length;
  document.getElementById('s-offline').textContent = devices.filter(function(d){ return d.status==='offline'; }).length;
}

function getIcon(d) {
  var s = ((d.name||'')+(d.vendor||'')+(d.device_type||'')).toLowerCase();
  if (s.indexOf('router')>=0||s.indexOf('gateway')>=0) return '🌐';
  if (s.indexOf('laptop')>=0||s.indexOf('notebook')>=0) return '💻';
  if (s.indexOf('desktop')>=0||s.indexOf('pc')>=0) return '🖥️';
  if (s.indexOf('server')>=0||s.indexOf('nas')>=0) return '🗄️';
  if (s.indexOf('phone')>=0||s.indexOf('smartphone')>=0) return '📱';
  if (s.indexOf('tablet')>=0) return '📱';
  if (s.indexOf('printer')>=0) return '🖨️';
  if (s.indexOf('tv')>=0) return '📺';
  if (s.indexOf('speaker')>=0||s.indexOf('sonos')>=0) return '🔊';
  if (s.indexOf('console')>=0||s.indexOf('game')>=0) return '🎮';
  if (s.indexOf('raspberry')>=0) return '🍓';
  if (s.indexOf('camera')>=0) return '📷';
  if (s.indexOf('light')>=0||s.indexOf('hue')>=0) return '💡';
  return '💻';
}

function pingBadge(ms) {
  if (ms == null) return '<span class="ping-badge pn">—</span>';
  var cls = ms < 10 ? 'pg' : ms < 50 ? 'po' : 'ps';
  return '<span class="ping-badge ' + cls + '">' + ms + 'ms</span>';
}

function renderList() {
  var q = document.getElementById('search-input').value.toLowerCase();
  var sorted = devices.slice().filter(function(d) {
    return !q || (d.name||'').toLowerCase().indexOf(q)>=0 || (d.ip||'').indexOf(q)>=0 || (d.mac||'').toLowerCase().indexOf(q)>=0;
  }).sort(function(a,b) {
    if (a.is_gateway) return -1; if (b.is_gateway) return 1;
    var s = {online:0, idle:1, offline:2};
    return (s[a.status]||2) - (s[b.status]||2);
  });
  document.getElementById('device-list').innerHTML = sorted.map(function(d) {
    return '<div class="device-item ' + (d.id==selectedId?'active':'') + '" onclick="selectDevice(' + d.id + ')" id="di-' + d.id + '">' +
      '<div class="d-icon">' + getIcon(d) + '</div>' +
      '<div class="d-info"><div class="d-name">' + (d.name||'(onbekend)') + '</div>' +
      '<div class="d-meta"><span class="d-ip">' + (d.ip||d.mac||'—') + '</span>' + pingBadge(d.ping_ms) + '</div></div>' +
      '<div class="d-dot ' + (d.status||'offline') + '"></div></div>';
  }).join('');
}

function setView(view, btn) {
  currentView = view;
  document.querySelectorAll('.tab').forEach(function(t){ t.classList.remove('active'); });
  btn.classList.add('active');
  document.getElementById('topo-view').style.display    = view==='topo' ? 'flex' : 'none';
  document.getElementById('list-view').classList.toggle('active', view==='list');
  document.getElementById('events-view').classList.toggle('active', view==='events');
  document.getElementById('topo-controls').style.display = view==='topo' ? 'flex' : 'none';

  if (view === 'topo') {
    if (!animFrame) { initNodes(); drawTopo(); }
  } else {
    cancelAnimationFrame(animFrame); animFrame = null;
    if (view === 'list') renderListTable();
    else if (view === 'events') renderEvents();
  }
}

// ═══════════════════════════════════════════════════════
// LIJST VIEW MET SORTERING
// ═══════════════════════════════════════════════════════
function sortList(col) {
  if (sortCol === col) sortDir *= -1; else { sortCol = col; sortDir = 1; }
  renderListTable();
}

function renderListTable() {
  var q = document.getElementById('search-input').value.toLowerCase();
  var filtered = devices.filter(function(d) {
    return !q || (d.name||'').toLowerCase().indexOf(q)>=0 || (d.ip||'').indexOf(q)>=0 || (d.mac||'').toLowerCase().indexOf(q)>=0;
  });
  filtered.sort(function(a, b) {
    var va = a[sortCol] || '';
    var vb = b[sortCol] || '';
    if (sortCol === 'ping_ms') { va = va===null||va===''?9999:parseInt(va); vb = vb===null||vb===''?9999:parseInt(vb); return (va-vb)*sortDir; }
    if (sortCol === 'ip') {
      var toNum = function(ip) { return ip ? ip.split('.').reduce(function(a,o){ return (a<<8)+(parseInt(o)||0); }, 0) : 0; };
      return (toNum(va) - toNum(vb)) * sortDir;
    }
    return String(va).localeCompare(String(vb)) * sortDir;
  });

  var statusOrder = {online:0, idle:1, offline:2};
  var tbody = document.getElementById('list-tbody');
  tbody.innerHTML = filtered.map(function(d) {
    var sb = d.status==='online' ? '<span class="badge badge-g">Online</span>'
           : d.status==='idle'   ? '<span class="badge badge-y">Idle</span>'
           : '<span class="badge badge-r">Offline</span>';
    return '<tr class="' + (d.id==selectedId?'selected':'') + '" onclick="selectDevice(' + d.id + ')">' +
      '<td>' + getIcon(d) + ' ' + (d.name||'<span style="color:var(--muted)">(onbekend)</span>') + '</td>' +
      '<td class="mono">' + (d.ip||'—') + '</td>' +
      '<td>' + (d.vendor||'—') + '</td>' +
      '<td>' + sb + '</td>' +
      '<td>' + pingBadge(d.ping_ms) + '</td>' +
      '</tr>';
  }).join('');
}

// ═══════════════════════════════════════════════════════
// FORCE-DIRECTED TOPOLOGIE
// ═══════════════════════════════════════════════════════
var canvas = document.getElementById('topo-canvas');
var ctx    = canvas.getContext('2d');

function initNodes() {
  var W = canvas.parentElement.offsetWidth  || 600;
  var H = canvas.parentElement.offsetHeight || 400;
  canvas.width  = W;
  canvas.height = H;
  canvas.style.width  = W + 'px';
  canvas.style.height = H + 'px';

  var cx = W / 2, cy = H / 2;
  var existing = {};
  nodes.forEach(function(n) { existing[n.d.id] = n; });

  var newNodes = [];
  var gw = devices.find(function(d){ return d.is_gateway; }) || devices[0];

  devices.forEach(function(d, i) {
    var isHub = gw && d.id === gw.id;
    var r     = isHub ? HUB_R : NODE_R;

    if (existing[d.id]) {
      // Bewaar positie van bestaande node
      var en = existing[d.id];
      en.d   = d;
      en.hub = isHub;
      en.r   = r;
      newNodes.push(en);
    } else {
      // Nieuwe node — start op willekeurige positie rondom centrum
      var angle = (i / devices.length) * Math.PI * 2;
      var dist  = isHub ? 0 : Math.min(W, H) * 0.25 + Math.random() * 80;
      newNodes.push({
        d: d, r: r, hub: isHub,
        x: cx + Math.cos(angle) * dist,
        y: cy + Math.sin(angle) * dist,
        vx: 0, vy: 0
      });
    }
  });
  nodes = newNodes;

  // Start force simulatie
  if (forceActive) startForce();
}

function startForce() {
  clearInterval(forceTimer);
  var steps = 0, maxSteps = 200;
  forceTimer = setInterval(function() {
    if (steps++ > maxSteps || !forceActive) { clearInterval(forceTimer); return; }
    applyForces();
  }, 16);
}

function applyForces() {
  var W = canvas.width, H = canvas.height;
  var cx = W / 2, cy = H / 2;
  var hub = nodes.find(function(n){ return n.hub; });

  // Repulsie tussen alle nodes
  for (var i = 0; i < nodes.length; i++) {
    for (var j = i + 1; j < nodes.length; j++) {
      var dx = nodes[i].x - nodes[j].x;
      var dy = nodes[i].y - nodes[j].y;
      var dist = Math.sqrt(dx*dx + dy*dy) || 1;
      var minDist = nodes[i].r + nodes[j].r + 40;
      if (dist < minDist) {
        var force = (minDist - dist) / dist * 0.5;
        nodes[i].vx += dx * force; nodes[i].vy += dy * force;
        nodes[j].vx -= dx * force; nodes[j].vy -= dy * force;
      }
    }
  }

  // Attractie richting hub (spring)
  if (hub) {
    nodes.forEach(function(n) {
      if (n.hub) return;
      var dx = hub.x - n.x, dy = hub.y - n.y;
      var dist = Math.sqrt(dx*dx + dy*dy) || 1;
      var idealDist = Math.min(W, H) * 0.28;
      var force = (dist - idealDist) / dist * 0.03;
      n.vx += dx * force; n.vy += dy * force;
    });
    // Hub wordt naar centrum getrokken
    hub.vx += (cx - hub.x) * 0.05;
    hub.vy += (cy - hub.y) * 0.05;
  }

  // Demping + positie update + rand botsing
  var pad = 60;
  nodes.forEach(function(n) {
    if (n === dragNode) return; // sleep node niet bewegen
    n.vx *= 0.7; n.vy *= 0.7;
    n.x  += n.vx; n.y  += n.vy;
    // Binnen canvas houden
    n.x = Math.max(n.r + pad, Math.min(W - n.r - pad, n.x));
    n.y = Math.max(n.r + pad, Math.min(H - n.r - pad, n.y));
  });
}

function toggleForce() {
  forceActive = !forceActive;
  var btn = document.getElementById('force-btn');
  btn.style.color = forceActive ? 'var(--accent)' : 'var(--muted)';
  if (forceActive) startForce();
  else clearInterval(forceTimer);
  toast(forceActive ? 'Force layout aan' : 'Force layout uit');
}

// ── Zoom & pan ──────────────────────────────────────────
function zoomBy(factor) {
  zoomLevel = Math.max(0.2, Math.min(4.0, zoomLevel * factor));
  updateZoomLabel();
}
function resetView() {
  zoomLevel = 1.0; panX = 0; panY = 0;
  updateZoomLabel();
  initNodes();
}
function updateZoomLabel() {
  document.getElementById('zoom-label').textContent = Math.round(zoomLevel * 100) + '%';
}

// ── Canvas drawing ─────────────────────────────────────
function drawTopo() {
  var W = canvas.width, H = canvas.height, now = Date.now();
  ctx.clearRect(0, 0, W, H);

  if (!nodes.length) { animFrame = requestAnimationFrame(drawTopo); return; }

  var hub = nodes.find(function(n){ return n.hub; });
  var isDark = document.documentElement.getAttribute('data-theme') !== 'light';

  // Transformatie: pan + zoom vanuit midden
  ctx.save();
  ctx.translate(W/2 + panX, H/2 + panY);
  ctx.scale(zoomLevel, zoomLevel);
  ctx.translate(-W/2, -H/2);

  // Verbindingen
  nodes.forEach(function(n) {
    if (!hub || n === hub) return;
    var st = n.d.status;
    ctx.beginPath(); ctx.moveTo(hub.x, hub.y); ctx.lineTo(n.x, n.y);
    if (st === 'online') {
      ctx.strokeStyle = 'rgba(0,229,255,.4)'; ctx.setLineDash([]); ctx.lineWidth = 1; ctx.stroke();
      // Animerend datapakket
      var t = ((now / 1400 * (1 + nodes.indexOf(n) * 0.15)) % 1);
      ctx.beginPath();
      ctx.arc(hub.x + (n.x - hub.x) * t, hub.y + (n.y - hub.y) * t, 2, 0, Math.PI*2);
      ctx.fillStyle = 'rgba(0,229,255,.9)'; ctx.fill();
    } else {
      ctx.strokeStyle = 'rgba(74,96,128,.25)'; ctx.setLineDash([4,6]); ctx.lineWidth = 1; ctx.stroke();
      ctx.setLineDash([]);
    }
  });

  // Nodes
  nodes.forEach(function(n) {
    var x = n.x, y = n.y, r = n.r, d = n.d;
    var sel = d.id === selectedId, hov = n === hoveredNode;

    // Glow halo voor geselecteerd/hover
    if (sel || hov) {
      var g = ctx.createRadialGradient(x,y,r,x,y,r+20);
      g.addColorStop(0,'rgba(0,229,255,.25)'); g.addColorStop(1,'rgba(0,229,255,0)');
      ctx.beginPath(); ctx.arc(x,y,r+20,0,Math.PI*2); ctx.fillStyle=g; ctx.fill();
    }

    // Hub pulse ring
    if (n.hub) {
      var pulse = (Math.sin(now/700)+1)/2;
      ctx.beginPath(); ctx.arc(x,y,r+5+pulse*8,0,Math.PI*2);
      ctx.strokeStyle = 'rgba(0,229,255,' + (.2 - pulse*.1) + ')';
      ctx.lineWidth = 1; ctx.stroke();
    }

    // Node achtergrond
    var gf = ctx.createRadialGradient(x-r/3,y-r/3,0,x,y,r);
    gf.addColorStop(0, isDark?'#1a2133':'#f0f4f8');
    gf.addColorStop(1, isDark?'#0c1018':'#e8edf5');
    ctx.beginPath(); ctx.arc(x,y,r,0,Math.PI*2);
    ctx.fillStyle = n.hub ? (isDark?'#0c1018':'#fff') : gf; ctx.fill();

    // Rand kleur op basis van status
    var bc = d.status==='online' ? '#00e5ff' : d.status==='idle' ? '#fbbf24' : '#2a3a55';
    ctx.beginPath(); ctx.arc(x,y,r,0,Math.PI*2);
    ctx.strokeStyle = sel ? '#fff' : bc; ctx.lineWidth = sel ? 2 : 1.5; ctx.stroke();

    // Ping badge boven node
    if (d.status==='online' && d.ping_ms != null) {
      var pc = d.ping_ms<10 ? '#22d3a0' : d.ping_ms<50 ? '#fbbf24' : '#f87171';
      ctx.font = 'bold 8px DM Mono,monospace'; ctx.textAlign='center'; ctx.textBaseline='bottom';
      ctx.fillStyle = pc; ctx.fillText(d.ping_ms+'ms', x, y-r-3);
    }

    // Icoon
    ctx.font = (n.hub?15:12)+'px serif';
    ctx.textAlign='center'; ctx.textBaseline='middle';
    ctx.fillStyle = isDark?'#e2eaf8':'#0f172a'; ctx.fillText(getIcon(d), x, y);

    // Label
    var lbl = d.name || d.mac || '?';
    if (lbl.length > 14) lbl = lbl.substring(0,13)+'…';
    ctx.font = (sel?'700':'400') + ' ' + (n.hub?11:9)+'px Outfit,sans-serif';
    ctx.fillStyle = sel ? (isDark?'#fff':'#000') : isDark?'#94a3b8':'#64748b';
    ctx.textBaseline = 'top'; ctx.fillText(lbl, x, y+r+3);
  });

  ctx.restore();
  animFrame = requestAnimationFrame(drawTopo);
}

// ── Coördinaat omrekenen (canvas naar wereld) ───────────
function canvasToWorld(cx2, cy2) {
  var W = canvas.width, H = canvas.height;
  return {
    x: (cx2 - W/2 - panX) / zoomLevel + W/2,
    y: (cy2 - H/2 - panY) / zoomLevel + H/2
  };
}

function nodeAtPos(wx, wy) {
  for (var i = nodes.length-1; i >= 0; i--) {
    var n = nodes[i];
    if (Math.hypot(wx - n.x, wy - n.y) < n.r + 8) return n;
  }
  return null;
}

// ── Mouse/touch events ──────────────────────────────────
canvas.addEventListener('mousedown', function(e) {
  var rect = canvas.getBoundingClientRect();
  var w = canvasToWorld(e.clientX - rect.left, e.clientY - rect.top);
  var n = nodeAtPos(w.x, w.y);
  if (n) {
    dragNode = n; dragOffX = n.x - w.x; dragOffY = n.y - w.y;
    canvas.className = 'dragging-node';
  } else {
    isPanning = true; panStartX = e.clientX - panX; panStartY = e.clientY - panY;
    canvas.className = 'dragging-canvas';
  }
});

canvas.addEventListener('mousemove', function(e) {
  var rect = canvas.getBoundingClientRect();
  var mx = e.clientX - rect.left, my = e.clientY - rect.top;
  var w = canvasToWorld(mx, my);

  if (dragNode) {
    dragNode.x = w.x + dragOffX; dragNode.y = w.y + dragOffY;
    dragNode.vx = 0; dragNode.vy = 0;
    return;
  }
  if (isPanning) {
    panX = e.clientX - panStartX; panY = e.clientY - panStartY;
    return;
  }

  // Hover detectie
  hoveredNode = nodeAtPos(w.x, w.y);
  canvas.style.cursor = hoveredNode ? 'pointer' : 'default';

  var tip = document.getElementById('tooltip');
  if (hoveredNode) {
    var d = hoveredNode.d;
    tip.style.display = 'block';
    tip.style.left = (e.clientX + 14) + 'px';
    tip.style.top  = (e.clientY - 10) + 'px';
    tip.innerHTML = '<b>' + (d.name||'(onbekend)') + '</b><br>'
      + (d.ip||'—') + ' · ' + (d.mac||'—') + '<br>'
      + (d.vendor||'—') + '<br>Ping: ' + (d.ping_ms!=null ? d.ping_ms+'ms' : '—');
  } else {
    tip.style.display = 'none';
  }
});

canvas.addEventListener('mouseup', function(e) {
  if (dragNode) {
    // Kleine beweging = klik op node
    var rect = canvas.getBoundingClientRect();
    var w = canvasToWorld(e.clientX - rect.left, e.clientY - rect.top);
    if (Math.hypot(w.x + dragOffX - dragNode.x, w.y + dragOffY - dragNode.y) < 5) {
      selectDevice(dragNode.d.id);
    }
    dragNode = null;
  }
  isPanning = false;
  canvas.className = hoveredNode ? 'pointer' : '';
});

canvas.addEventListener('mouseleave', function() {
  dragNode = null; isPanning = false; hoveredNode = null;
  document.getElementById('tooltip').style.display = 'none';
  canvas.className = '';
});

// Scroll = zoom
canvas.addEventListener('wheel', function(e) {
  e.preventDefault();
  zoomBy(e.deltaY < 0 ? 1.1 : 0.9);
}, {passive: false});

window.addEventListener('resize', function() {
  if (currentView === 'topo') {
    var W = canvas.parentElement.offsetWidth || 600;
    var H = canvas.parentElement.offsetHeight || 400;
    canvas.width = W; canvas.height = H;
    canvas.style.width = W+'px'; canvas.style.height = H+'px';
  }
});

// ═══════════════════════════════════════════════════════
// DETAIL PANEL
// ═══════════════════════════════════════════════════════
function selectDevice(id) {
  selectedId = id; renderList();
  if (currentView === 'list') renderListTable();
  refreshDetail();
}

function refreshDetail() {
  var d = null;
  for (var i = 0; i < devices.length; i++) { if (devices[i].id == selectedId) { d = devices[i]; break; } }
  if (!d) return clearDetail();
  document.getElementById('detail-empty').style.display = 'none';
  var dc = document.getElementById('detail-content'); dc.style.display = 'block';
  var sb = d.status==='online' ? '<span class="badge badge-g">Online</span>'
         : d.status==='idle'   ? '<span class="badge badge-y">Idle</span>'
         : '<span class="badge badge-r">Offline</span>';
  var fmt = function(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('nl-NL',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
  };
  dc.innerHTML =
    '<div class="detail-header">' +
    '<div class="detail-big-icon">' + getIcon(d) + '</div>' +
    '<div><div class="detail-name">' + (d.name||'(onbekend)') + '</div>' +
    '<div class="detail-vendor">' + (d.vendor||'—') + '</div></div></div>' +
    '<div class="detail-rows">' +
    '<div class="dr"><span class="dr-key">Status</span><span class="dr-val">' + sb + '</span></div>' +
    '<div class="dr"><span class="dr-key">IP-adres</span><span class="dr-val">' + (d.ip||'—') + '</span></div>' +
    '<div class="dr"><span class="dr-key">MAC</span><span class="dr-val">' + (d.mac||'—') + '</span></div>' +
    '<div class="dr"><span class="dr-key">Hostname</span><span class="dr-val">' + (d.hostname||'—') + '</span></div>' +
    '<div class="dr"><span class="dr-key">Type</span><span class="dr-val">' + (d.device_type||'—') + '</span></div>' +
    '<div class="dr"><span class="dr-key">Ping</span><span class="dr-val">' + pingBadge(d.ping_ms) + '</span></div>' +
    '<div class="dr"><span class="dr-key">Vertrouwd</span><span class="dr-val">' + (d.is_trusted?'<span class="badge badge-g">Ja</span>':'<span class="badge badge-r">Nee</span>') + '</span></div>' +
    '<div class="dr"><span class="dr-key">Eerste gezien</span><span class="dr-val">' + fmt(d.first_seen) + '</span></div>' +
    '<div class="dr"><span class="dr-key">Laatste gezien</span><span class="dr-val">' + fmt(d.last_seen) + '</span></div>' +
    (d.notes ? '<div class="dr"><span class="dr-key">Notities</span><span class="dr-val">' + d.notes + '</span></div>' : '') +
    '</div>' +
    '<div style="padding:0 16px 12px;display:flex;gap:6px">' +
    '<button class="btn btn-ghost" style="flex:1;justify-content:center;font-size:12px" onclick="openEdit(' + d.id + ')">✏️ Bewerk</button>' +
    '</div>';
}

function clearDetail() {
  document.getElementById('detail-empty').style.display = 'flex';
  var dc = document.getElementById('detail-content'); dc.style.display = 'none'; dc.innerHTML = '';
}

// ═══════════════════════════════════════════════════════
// EDIT / ADD
// ═══════════════════════════════════════════════════════
function openAdd() {
  editingId = null; clearForm();
  document.getElementById('f-mac').removeAttribute('readonly');
  document.getElementById('btn-delete').style.display = 'none';
  document.getElementById('btn-save').textContent = 'Toevoegen';
  document.getElementById('edit-form').classList.add('open');
  document.getElementById('f-name').focus();
}
function openEdit(id) {
  var d = null;
  for (var i=0;i<devices.length;i++){if(devices[i].id==id){d=devices[i];break;}}
  if (!d) return;
  editingId = id;
  document.getElementById('f-name').value  = d.name || '';
  document.getElementById('f-ip').value    = d.ip   || '';
  document.getElementById('f-mac').value   = d.mac  || '';
  document.getElementById('f-mac').setAttribute('readonly', true);
  document.getElementById('f-type').value  = d.device_type || '';
  document.getElementById('f-notes').value = d.notes || '';
  document.getElementById('f-trusted').checked = !!d.is_trusted;
  document.getElementById('btn-delete').style.display = 'inline-block';
  document.getElementById('btn-save').textContent = 'Opslaan';
  document.getElementById('edit-form').classList.add('open');
}
function closeEdit(){ document.getElementById('edit-form').classList.remove('open'); editingId = null; }
function clearForm(){
  ['f-name','f-ip','f-mac','f-notes'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('f-type').selectedIndex = 0;
  document.getElementById('f-trusted').checked = false;
}
function saveDevice() {
  var mac  = document.getElementById('f-mac').value.trim().toUpperCase();
  var name = document.getElementById('f-name').value.trim();
  if (!mac) { toast('MAC verplicht','warn'); return; }
  var body = {mac:mac, name:name, ip:document.getElementById('f-ip').value.trim(),
    device_type:document.getElementById('f-type').value, notes:document.getElementById('f-notes').value.trim(),
    is_trusted:document.getElementById('f-trusted').checked};
  if (editingId) body.id = editingId;
  fetch(API+'devices', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)})
    .then(function(r){ return r.json(); }).then(function(d) {
      if (!d.ok) throw new Error(d.error);
      toast(editingId ? 'Bijgewerkt!' : 'Toegevoegd!');
      closeEdit(); log((editingId?'Bijgewerkt':'Toegevoegd')+': '+(name||mac),'ok'); poll();
    }).catch(function(e){ toast('Fout: '+e.message,'err'); });
}
function deleteSelected() {
  if (!editingId) return;
  var d = null;
  for (var i=0;i<devices.length;i++){if(devices[i].id==editingId){d=devices[i];break;}}
  if (!confirm('Verwijder "'+(d&&d.name||d&&d.mac)+'"?')) return;
  fetch(API+'devices&id='+editingId, {method:'DELETE'})
    .then(function(r){ return r.json(); }).then(function(data) {
      if (!data.ok) throw new Error(data.error);
      toast('Verwijderd','warn'); closeEdit();
      log('Verwijderd: '+(d&&d.name||d&&d.mac),'warn');
      if (selectedId==editingId){selectedId=null;clearDetail();}
      poll();
    }).catch(function(e){ toast('Fout: '+e.message,'err'); });
}

// ═══════════════════════════════════════════════════════
// EVENTS
// ═══════════════════════════════════════════════════════
function addEvent(ev) {
  events.unshift(Object.assign({}, ev, {ts: new Date()}));
  if (events.length > 100) events.pop();
  if (currentView === 'events') renderEvents();
  if ((ev.event_type||ev.type) === 'new') {
    toast('⚠️ Nieuw apparaat: '+(ev.new_value||'?')+' ('+ev.mac+')', 'warn');
    log('Nieuw apparaat: '+ev.mac+' @ '+(ev.new_value||'?'), 'warn');
  }
}
function renderEvents() {
  var list = document.getElementById('events-list');
  if (!events.length) { list.innerHTML = '<div style="text-align:center;padding:40px;color:var(--muted);font-size:12px">Nog geen events</div>'; return; }
  list.innerHTML = events.map(function(ev) {
    var t    = ev.ts ? new Date(ev.ts).toLocaleTimeString('nl-NL') : '—';
    var type = ev.event_type || ev.type || 'info';
    var msg  = type==='new'        ? 'Nieuw apparaat: '+(ev.new_value||'?')+' ('+ev.mac+')'
             : type==='ip_changed' ? 'IP gewijzigd: '+ev.mac+' — '+ev.old_value+' → '+ev.new_value
             : type==='offline'    ? 'Offline: '+(ev.device_name||ev.mac)
             : JSON.stringify(ev);
    return '<div class="event-row"><span class="ebadge b-'+type+'">'+type+'</span>' +
           '<span style="flex:1;font-size:12px">'+msg+'</span>' +
           '<span style="font-family:\'DM Mono\',monospace;font-size:10px;color:var(--muted);flex-shrink:0">'+t+'</span></div>';
  }).join('');
}

// ═══════════════════════════════════════════════════════
// LOG / TOAST
// ═══════════════════════════════════════════════════════
function log(msg, type) {
  type = type || '';
  var body = document.getElementById('log-body');
  var t = new Date().toLocaleTimeString('nl-NL',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  var line = document.createElement('div'); line.className = 'log-line '+type;
  line.innerHTML = '<span class="log-t">'+t+'</span><span class="log-m">'+msg+'</span>';
  body.appendChild(line); body.scrollTop = body.scrollHeight;
  while (body.children.length > 80) body.removeChild(body.firstChild);
}
function clearLog(){ document.getElementById('log-body').innerHTML=''; }
var toastTimer;
function toast(msg, type) {
  type = type || '';
  var el = document.getElementById('toast'); el.textContent = msg;
  el.className = 'toast '+type+' show';
  clearTimeout(toastTimer); toastTimer = setTimeout(function(){ el.classList.remove('show'); }, 3000);
}

// ═══════════════════════════════════════════════════════
// FINGBOX SYNC
// ═══════════════════════════════════════════════════════
function openFingbox() {
  // Laad opgeslagen instellingen
  fetch(API + 'status').then(function(r){ return r.json(); }).then(function(d) {
    if (d.fingbox_url) document.getElementById('fg-url').value = d.fingbox_url;
  }).catch(function(){});
  document.getElementById('fg-result').textContent = '';
  document.getElementById('fingbox-modal').style.display = 'flex';
}
function closeFingbox() {
  document.getElementById('fingbox-modal').style.display = 'none';
}
function saveFingboxConfig() {
  var url = document.getElementById('fg-url').value.trim();
  var key = document.getElementById('fg-key').value.trim();
  if (!url || !key) { document.getElementById('fg-result').textContent = '⚠ URL en API key verplicht'; return; }
  fetch(API + 'fingbox_config', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({url:url, api_key:key})})
    .then(function(r){ return r.json(); }).then(function(d) {
      document.getElementById('fg-result').textContent = d.ok ? '✅ Instellingen opgeslagen' : '❌ ' + d.error;
    });
}
function runFingboxSync() {
  var url = document.getElementById('fg-url').value.trim();
  var key = document.getElementById('fg-key').value.trim();
  if (!url || !key) { document.getElementById('fg-result').textContent = '⚠ URL en API key verplicht'; return; }
  document.getElementById('fg-result').textContent = '⏳ Sync bezig...';
  fetch(API + 'fingbox_sync', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({url:url, api_key:key})})
    .then(function(r){ return r.json(); }).then(function(d) {
      if (d.ok) {
        var msg = '✅ ' + d.imported + ' apparaten gesynchroniseerd';
        if (d.skipped > 0) msg += ' (' + d.skipped + ' overgeslagen)';
        document.getElementById('fg-result').textContent = msg;
        log('Fingbox sync: ' + d.imported + ' apparaten', 'ok');
        toast('Fingbox sync klaar — ' + d.imported + ' apparaten');
        poll();
      } else {
        document.getElementById('fg-result').textContent = '❌ ' + d.error;
        log('Fingbox sync fout: ' + d.error, 'err');
      }
    }).catch(function(e) {
      document.getElementById('fg-result').textContent = '❌ Verbindingsfout: ' + e.message;
    });
}

// ═══════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════
window.addEventListener('load', function() {
  initTheme();
  loadInterfaces();
  startPolling();
  updateZoomLabel();
  document.getElementById('force-btn').style.color = 'var(--accent)';
  document.getElementById('topo-controls').style.display = 'flex';
  log('NetMap v2.2 gestart','inf');
  log('Poll interval: <?= SCAN_INTERVAL ?>min auto-scan','inf');

  fetch(API+'events?limit=30').then(function(r){ return r.json(); }).then(function(data) {
    if (data.events) events = data.events.map(function(e){ return Object.assign({},e,{ts:new Date(e.occurred_at)}); });
  }).catch(function(){});
});
</script>
</body>
</html>
