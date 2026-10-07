<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — mPanel</title>
    <style>
        :root{--brand:#114a43;--brand-dark:#0b3833;--bg:#f2f6f4;--surface:#fff;--text:#14201e;--muted:#71807d;--border:#dfe8e4}
        [data-theme="dark"]{--bg:#09100f;--surface:#111b19;--text:#eef5f2;--muted:#91a39f;--border:#293a36}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 15% 10%,rgba(17,74,67,.13),transparent 34%),var(--bg);color:var(--text);font:14px/1.5 Inter,system-ui,sans-serif;display:grid;place-items:center;padding:22px}
        .login{width:min(430px,100%)}.brand{display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:25px;color:var(--brand);font-size:25px;font-weight:900}.logo{width:48px;height:48px;border-radius:15px;background:var(--brand);color:#fff;display:grid;place-items:center;font-size:26px}
        .card{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:30px;box-shadow:0 25px 70px rgba(10,45,38,.1)}h1{margin:0 0 7px;font-size:26px}.muted{color:var(--muted);margin:0 0 25px}.field{margin:0 0 16px}.field label{display:block;font-weight:700;margin-bottom:7px}.field input{width:100%;border:1px solid var(--border);background:transparent;color:var(--text);padding:12px 13px;border-radius:11px;outline:none}.field input:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(17,74,67,.12)}
        .row{display:flex;justify-content:space-between;align-items:center;margin:3px 0 20px;font-size:13px}.row label{display:flex;gap:8px;align-items:center;color:var(--muted)}.btn{width:100%;border:0;border-radius:11px;padding:12px;background:var(--brand);color:#fff;font-weight:800;cursor:pointer}.error{margin:0 0 18px;padding:11px 12px;border-radius:10px;background:#fff1f1;color:#9b2525;border:1px solid #f0cccc}.foot{margin-top:18px;text-align:center;color:var(--muted);font-size:12px}
        .theme{position:fixed;top:18px;right:18px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:10px;padding:9px 12px;cursor:pointer}
    </style>
</head>
<body>
<button class="theme" onclick="toggleTheme()">◐ Theme</button>
<div class="login">
    <div class="brand"><div class="logo">⌁</div><span>mPanel</span></div>
    <div class="card">
        <h1>Welcome back</h1>
        <p class="muted">Sign in to manage your hosting infrastructure.</p>
        @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
            <div class="row"><label><input type="checkbox" name="remember" value="1"> Remember me</label><span>Secure access</span></div>
            <button class="btn" type="submit">Sign in to mPanel</button>
        </form>
        <div class="foot">Mangrove Control Plane · Secure infrastructure management</div>
    </div>
</div>
<script>
function toggleTheme(){const r=document.documentElement,n=r.dataset.theme==='dark'?'light':'dark';r.dataset.theme=n;localStorage.setItem('mpanel-theme',n);}
document.documentElement.dataset.theme=localStorage.getItem('mpanel-theme')||'light';
</script>
</body>
</html>
