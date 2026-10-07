@extends('layouts.app')
@section('title',$website->domain)
@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px">
<div><div class="mp-eyebrow">Website</div><h1>{{ $website->domain }}</h1><p class="mp-sub" style="margin-bottom:0">{{ $website->server->name }} · {{ $website->document_root }}</p></div>
<span class="mp-status {{ $website->status }}">{{ ucfirst($website->status) }}</span>
</div>
<div class="mp-grid">
<div class="mp-card"><div class="mp-label">PHP</div><div class="mp-metric">{{ $website->php_version }}</div></div>
<div class="mp-card"><div class="mp-label">Server</div><div class="mp-metric" style="font-size:19px">{{ $website->server->name }}</div></div>
<div class="mp-card"><div class="mp-label">Status</div><div class="mp-metric" style="font-size:19px">{{ ucfirst($website->status) }}</div></div>
</div>
<div class="mp-card" style="margin-top:22px"><h2 style="font-size:17px;margin-top:0">Website management</h2><p class="mp-sub">Website tools will appear here as mPanel provisioning capabilities expand.</p>
<div style="display:flex;gap:10px;flex-wrap:wrap"><a class="mp-secondary-btn" href="{{ route('websites.index') }}">Back to websites</a><a class="mp-primary-btn" href="{{ route('servers.show',$website->server) }}">Open server</a></div></div>
@endsection
