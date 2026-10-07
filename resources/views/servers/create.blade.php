@extends('layouts.app')
@section('title','Add Server')
@section('content')
<div style="max-width:760px"><div class="mp-eyebrow">Infrastructure</div><h1>Add a server</h1><p class="mp-sub">Register an Ubuntu VPS. mPanel will use its Agent for privileged server operations.</p><div class="mp-card">
@if($errors->any())<div class="mp-form-errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('servers.store') }}">@csrf
<div class="mp-form-grid"><div class="mp-field"><label>Server name</label><input name="name" value="{{ old('name') }}" required placeholder="Vessel Host VPS 01"></div><div class="mp-field"><label>Hostname</label><input name="hostname" value="{{ old('hostname') }}" placeholder="server1.example.com"></div><div class="mp-field"><label>IP address</label><input name="ip_address" value="{{ old('ip_address') }}" required placeholder="203.0.113.10"></div><div class="mp-field"><label>Agent port</label><input name="port" type="number" min="1" max="65535" value="{{ old('port',8443) }}" required></div></div>
<div class="mp-field"><label>Notes</label><textarea name="notes" rows="4" placeholder="Optional infrastructure notes">{{ old('notes') }}</textarea></div>
<div style="display:flex;justify-content:flex-end;gap:10px"><a href="{{ route('servers.index') }}" class="mp-secondary-btn">Cancel</a><button class="mp-primary-btn" type="submit">Add server</button></div>
</form></div></div>
@endsection
@push('head')<style>
.mp-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.mp-field{margin-bottom:18px}.mp-field label{display:block;font-weight:750;margin-bottom:7px}.mp-field input,.mp-field textarea{width:100%;border:1px solid var(--border);background:var(--surface-2);color:var(--text);padding:11px 12px;border-radius:10px;outline:0;font:inherit}.mp-field input:focus,.mp-field textarea:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(17,74,67,.11)}.mp-secondary-btn{display:inline-flex;align-items:center;padding:11px 16px;border:1px solid var(--border);border-radius:11px;text-decoration:none;font-weight:750}.mp-form-errors{padding:12px 14px;margin-bottom:18px;background:#fff1f1;color:#9b2525;border:1px solid #f0cccc;border-radius:11px}@media(max-width:650px){.mp-form-grid{grid-template-columns:1fr}}
</style>@endpush
