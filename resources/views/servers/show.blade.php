@extends('layouts.app')
@section('title',$server->name)
@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px"><div><div class="mp-eyebrow">Server</div><h1>{{ $server->name }}</h1><p class="mp-sub" style="margin-bottom:0">{{ $server->hostname ?: $server->ip_address }} · {{ $server->ip_address }}:{{ $server->port }}</p></div><span class="mp-status {{ $server->status }}">{{ ucfirst($server->status) }}</span></div>
<div class="mp-grid"><div class="mp-card"><div class="mp-label">CPU</div><div class="mp-metric">{{ $server->cpu_percent ?? '—' }}%</div></div><div class="mp-card"><div class="mp-label">Memory</div><div class="mp-metric">{{ $server->memory_percent ?? '—' }}%</div></div><div class="mp-card"><div class="mp-label">Disk</div><div class="mp-metric">{{ $server->disk_percent ?? '—' }}%</div></div><div class="mp-card"><div class="mp-label">Agent</div><div class="mp-metric" style="font-size:19px">{{ $server->last_seen_at ? 'Connected' : 'Awaiting' }}</div></div></div>
@if(session('agent_token'))
<div class="mp-card" style="margin-top:22px;border-color:#d5b24a"><h2 style="font-size:17px;margin-top:0">Agent pairing token</h2><p class="mp-sub">This token is shown only after registration. Store it securely; use it when installing the Agent on the VPS.</p><div style="display:flex;gap:8px"><input id="agent-token" readonly value="{{ session('agent_token') }}" style="flex:1;border:1px solid var(--border);background:var(--surface-2);color:var(--text);padding:11px;border-radius:10px;font-family:monospace"><button class="mp-primary-btn" type="button" onclick="navigator.clipboard.writeText(document.getElementById('agent-token').value)">Copy token</button></div></div>
@endif
<div class="mp-card" style="margin-top:22px"><h2 style="font-size:17px;margin-top:0">Agent connection</h2><p class="mp-sub">The Agent authenticates with a bearer token and reports health telemetry to mPanel.</p><div style="display:flex;gap:10px;flex-wrap:wrap"><a class="mp-primary-btn" href="{{ route('servers.index') }}">Back to servers</a><form method="POST" action="{{ route('servers.destroy',$server) }}" onsubmit="return confirm('Remove this server from mPanel?')">@csrf @method('DELETE')<button class="mp-secondary-btn" type="submit">Remove server</button></form></div></div>

<div class="mp-card" style="margin-top:22px"><h2 style="font-size:17px;margin-top:0">Create website</h2><p class="mp-sub">Queue an approved website provisioning operation on this server.</p>
<form method="POST" action="{{ route('servers.websites.store',$server) }}" style="display:grid;grid-template-columns:minmax(0,2fr) minmax(150px,1fr) auto;gap:10px;align-items:end">
@csrf
<div><label class="mp-label" for="domain">Domain</label><input id="domain" name="domain" required placeholder="example.com" value="{{ old('domain') }}" style="width:100%;border:1px solid var(--border);background:var(--surface-2);color:var(--text);padding:11px;border-radius:10px"></div>
<div><label class="mp-label" for="php_version">PHP</label><select id="php_version" name="php_version" style="width:100%;border:1px solid var(--border);background:var(--surface-2);color:var(--text);padding:11px;border-radius:10px"><option>8.2</option><option selected>8.3</option><option>8.4</option></select></div>
<button class="mp-primary-btn" type="submit">Provision</button>
</form>
@if($errors->any())<div style="margin-top:12px;color:#b42318">{{ $errors->first() }}</div>@endif
</div>
@endsection
