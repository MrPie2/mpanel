<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'mPanel') — Mangrove Hosting Control Panel</title>
    <style>
        :root{--brand:#114a43;--brand-dark:#0b3833;--brand-soft:#eaf3f1;--bg:#f4f7f6;--surface:#fff;--surface-2:#f8faf9;--text:#14201e;--muted:#71807d;--border:#e0e8e5;--shadow:0 12px 35px rgba(15,55,48,.06)}
        [data-theme="dark"]{--bg:#0b1211;--surface:#111b19;--surface-2:#15211f;--text:#edf5f2;--muted:#91a39f;--border:#263734;--brand-soft:#173b36;--shadow:0 12px 35px rgba(0,0,0,.2)}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px/1.5 Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        a{color:inherit}.mp-shell{min-height:100vh}.mp-sidebar{position:fixed;z-index:30;inset:0 auto 0 0;width:256px;background:var(--surface);border-right:1px solid var(--border);padding:18px 14px;transition:.2s ease;overflow:auto}
        .mp-brand{display:flex;align-items:center;gap:11px;padding:8px 10px 26px;text-decoration:none;font-size:20px;font-weight:850;color:var(--brand)}
        .mp-logo{width:40px;height:40px;border-radius:13px;background:var(--brand);color:#fff;display:grid;place-items:center;font-size:23px;font-weight:900;box-shadow:0 8px 20px rgba(17,74,67,.22)}
        .mp-section-label{padding:14px 12px 7px;color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .mp-nav a{display:flex;align-items:center;gap:12px;padding:11px 12px;margin:3px 0;border-radius:11px;text-decoration:none;color:var(--muted);font-weight:650}
        .mp-nav a:hover,.mp-nav a.active{background:var(--brand-soft);color:var(--brand)}.mp-nav i{width:20px;text-align:center;font-style:normal;font-size:17px}
        .mp-main{margin-left:256px;min-height:100vh}.mp-topbar{height:72px;position:sticky;top:0;z-index:20;background:color-mix(in srgb,var(--surface) 94%,transparent);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;padding:0 28px}
        .mp-menu{display:none;border:0;background:transparent;color:var(--text);font-size:22px}.mp-search{flex:1;max-width:560px;position:relative}.mp-search input{width:100%;border:1px solid var(--border);background:var(--surface-2);color:var(--text);border-radius:11px;padding:10px 14px 10px 38px;outline:0}.mp-search span{position:absolute;left:13px;top:8px;color:var(--muted);font-size:17px}
        .mp-top-actions{margin-left:auto;display:flex;align-items:center;gap:8px}.mp-icon-btn{width:40px;height:40px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:11px;cursor:pointer}.mp-user{display:flex;align-items:center;gap:9px;padding-left:7px}.mp-avatar{width:36px;height:36px;border-radius:50%;background:var(--brand-soft);color:var(--brand);display:grid;place-items:center;font-weight:800}
        .mp-content{padding:30px;max-width:1500px;margin:auto}.mp-eyebrow{color:var(--brand);font-weight:800;text-transform:uppercase;font-size:11px;letter-spacing:.12em}.mp-content h1{font-size:30px;margin:5px 0}.mp-sub{color:var(--muted);margin:0 0 26px}
        .mp-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.mp-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;box-shadow:var(--shadow)}.mp-label{color:var(--muted);font-weight:650}.mp-metric{font-size:27px;font-weight:850;margin-top:8px}.mp-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.mp-action{padding:17px;border:1px solid var(--border);border-radius:14px;background:var(--surface);text-decoration:none;font-weight:750}.mp-action span{display:block;color:var(--brand);font-size:22px;margin-bottom:7px}
        .mp-flash{position:fixed;right:22px;bottom:22px;z-index:100;padding:13px 16px;border-radius:12px;background:var(--surface);border:1px solid var(--border);box-shadow:var(--shadow)}
        @media(max-width:1000px){.mp-sidebar{width:76px}.mp-brand span,.mp-nav span,.mp-section-label{display:none}.mp-brand{justify-content:center}.mp-nav a{justify-content:center}.mp-main{margin-left:76px}.mp-grid,.mp-actions{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:650px){.mp-sidebar{transform:translateX(-100%);width:256px}.mp-sidebar.open{transform:translateX(0)}.mp-brand span,.mp-nav span,.mp-section-label{display:inline}.mp-brand{justify-content:flex-start}.mp-nav a{justify-content:flex-start}.mp-main{margin-left:0}.mp-menu{display:block}.mp-content{padding:20px}.mp-grid,.mp-actions{grid-template-columns:1fr}.mp-topbar{padding:0 14px}.mp-user strong{display:none}.mp-search{max-width:none}}
    </style>
    @stack('head')
</head>
<body>
<div class="mp-shell">
    @include('layouts.partials.sidebar')
    <main class="mp-main">
        @include('layouts.partials.topbar')
        <section class="mp-content">
            @yield('content')
        </section>
    </main>
</div>
@if(session('success'))
    <div class="mp-flash">{{ session('success') }}</div>
@endif
<script>
(() => {
    const root=document.documentElement;
    const saved=localStorage.getItem('mpanel-theme') || 'light';
    root.dataset.theme=saved;
    window.toggleMPanelTheme=()=>{const next=root.dataset.theme==='dark'?'light':'dark';root.dataset.theme=next;localStorage.setItem('mpanel-theme',next);};
    window.toggleMPanelSidebar=()=>document.querySelector('.mp-sidebar')?.classList.toggle('open');
})();
</script>
@stack('scripts')
</body>
</html>
