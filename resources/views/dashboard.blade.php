<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>mPanel — Mangrove Hosting Control Panel</title>
    <style>
        :root{--brand:#114a43;--brand-2:#0b3833;--bg:#f5f7f6;--card:#fff;--text:#15201e;--muted:#71807d;--border:#e2e9e7}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px/1.5 Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .shell{display:flex;min-height:100vh}.side{width:250px;background:#fff;border-right:1px solid var(--border);padding:22px 16px;position:fixed;inset:0 auto 0 0}
        .brand{display:flex;align-items:center;gap:11px;font-weight:800;font-size:20px;color:var(--brand);padding:8px 10px 28px}
        .logo{width:38px;height:38px;border-radius:12px;background:var(--brand);display:grid;place-items:center;color:#fff;font-size:20px}
        nav a{display:flex;gap:12px;align-items:center;padding:11px 12px;margin:3px 0;border-radius:10px;color:#596764;text-decoration:none;font-weight:600}
        nav a.active,nav a:hover{background:#eaf3f1;color:var(--brand)}.main{margin-left:250px;width:calc(100% - 250px)}
        header{height:72px;background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 32px}
        .search{border:1px solid var(--border);background:#f8faf9;border-radius:10px;padding:10px 14px;min-width:300px;color:var(--muted)}
        .content{padding:30px;max-width:1500px;margin:auto}.eyebrow{color:var(--brand);font-weight:800;text-transform:uppercase;font-size:11px;letter-spacing:.12em}
        h1{font-size:30px;margin:5px 0}.sub{color:var(--muted);margin:0 0 26px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
        .card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;box-shadow:0 5px 20px rgba(15,55,48,.04)}
        .metric{font-size:27px;font-weight:800;margin-top:9px}.label{color:var(--muted);font-weight:600}.section{margin-top:22px}.section h2{font-size:17px}
        .actions{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.action{padding:17px;border:1px solid var(--border);border-radius:14px;background:#fff;text-decoration:none;color:var(--text);font-weight:700}
        .action span{display:block;color:var(--brand);font-size:22px;margin-bottom:8px}@media(max-width:900px){.side{width:74px}.brand span,nav a span{display:none}.main{margin-left:74px;width:calc(100% - 74px)}header{padding:0 18px}.search{min-width:0;width:180px}.grid,.actions{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.side{display:none}.main{margin-left:0;width:100%}.grid,.actions{grid-template-columns:1fr}.content{padding:20px}header{height:62px}}
    </style>
</head>
<body>
<div class="shell">
<aside class="side">
    <div class="brand"><div class="logo">⌁</div><span>mPanel</span></div>
    <nav>
        <a class="active" href="/dashboard">▦ <span>Dashboard</span></a>
        <a href="#">◈ <span>Websites</span></a>
        <a href="#">◎ <span>Domains</span></a>
        <a href="#">▤ <span>Databases</span></a>
        <a href="#">◇ <span>DNS Manager</span></a>
        <a href="#">▣ <span>SSL Certificates</span></a>
        <a href="#">↗ <span>Git Deployments</span></a>
        <a href="#">◫ <span>Backups</span></a>
    </nav>
</aside>
<main class="main">
<header><div class="search">⌕ &nbsp; Search mPanel...</div><div>● &nbsp; <strong>Admin</strong></div></header>
<div class="content">
    <div class="eyebrow">Mangrove Control Plane</div>
    <h1>Good morning, Admin.</h1>
    <p class="sub">Manage your hosting infrastructure from one place.</p>
    <div class="grid">
        <div class="card"><div class="label">Servers</div><div class="metric">0</div></div>
        <div class="card"><div class="label">Websites</div><div class="metric">0</div></div>
        <div class="card"><div class="label">Databases</div><div class="metric">0</div></div>
        <div class="card"><div class="label">Storage Used</div><div class="metric">0 GB</div></div>
    </div>
    <div class="section"><h2>Quick actions</h2><div class="actions">
        <a class="action" href="#"><span>＋</span>Add Server</a>
        <a class="action" href="#"><span>⌂</span>Create Website</a>
        <a class="action" href="#"><span>◉</span>Create Database</a>
        <a class="action" href="#"><span>↥</span>Deploy from Git</a>
    </div></div>
    <div class="section card"><h2>System status</h2><p class="sub">mPanel is ready for the Agent layer. Server telemetry and provisioning will appear here once a VPS is connected.</p></div>
</div>
</main></div>
</body></html>