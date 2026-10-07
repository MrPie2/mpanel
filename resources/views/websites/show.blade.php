@extends('layouts.app')
@section('title', $website->domain)
@section('content')
<div class="mp-page-head">
    <div>
        <div class="mp-eyebrow">Website management</div>
        <h1>{{ $website->domain }}</h1>
        <p class="mp-sub">{{ $website->server->name }} · {{ $website->document_root }}</p>
    </div>
    <span class="mp-status {{ $website->status }}">{{ ucfirst($website->status) }}</span>
</div>

<div class="mp-grid" style="margin-bottom:22px">
    <div class="mp-card"><div class="mp-label">Status</div><div class="mp-metric" style="font-size:20px">{{ ucfirst($website->status) }}</div><div class="mp-sub">Provisioning state</div></div>
    <div class="mp-card"><div class="mp-label">PHP version</div><div class="mp-metric">{{ $website->php_version }}</div><div class="mp-sub">PHP-FPM runtime</div></div>
    <div class="mp-card"><div class="mp-label">Server</div><div class="mp-metric" style="font-size:20px">{{ $website->server->name }}</div><div class="mp-sub">{{ $website->server->ip_address }}</div></div>
</div>

<div class="mp-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
    <a class="mp-card" href="{{ route('websites.files',$website) }}" style="text-decoration:none;color:inherit"><div style="font-size:24px;margin-bottom:12px">▣</div><strong>File Manager</strong><div class="mp-sub">Manage website files and directories.</div></a>
    <a class="mp-card" href="{{ route('websites.ssl',$website) }}" style="text-decoration:none;color:inherit"><div style="font-size:24px;margin-bottom:12px">◇</div><strong>SSL</strong><div class="mp-sub">Manage HTTPS certificates.</div></a>
    <a class="mp-card" href="{{ route('websites.dns',$website) }}" style="text-decoration:none;color:inherit"><div style="font-size:24px;margin-bottom:12px">◎</div><strong>DNS</strong><div class="mp-sub">Manage DNS records for this domain.</div></a>
    <a class="mp-card" href="#" style="text-decoration:none;color:inherit"><div style="font-size:24px;margin-bottom:12px">▤</div><strong>Databases</strong><div class="mp-sub">Create and manage website databases.</div></a>
    <a class="mp-card" href="{{ route('websites.git',$website) }}" style="text-decoration:none;color:inherit"><div style="font-size:24px;margin-bottom:12px">↗</div><strong>Git Deployment</strong><div class="mp-sub">Connect a repository and deploy changes.</div></a>
    <a class="mp-card" href="#" style="text-decoration:none;color:inherit"><div style="font-size:24px;margin-bottom:12px">◫</div><strong>Backups</strong><div class="mp-sub">Create and restore website backups.</div></a>
</div>

<div class="mp-card" style="margin-top:22px">
    <h2 style="font-size:17px;margin:0 0 16px">Website details</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px">
        <div><div class="mp-label">Domain</div><div>{{ $website->domain }}</div></div>
        <div><div class="mp-label">Document root</div><div style="word-break:break-all">{{ $website->document_root }}</div></div>
        <div><div class="mp-label">Server</div><div>{{ $website->server->name }}</div></div>
        <div><div class="mp-label">Created</div><div>{{ $website->created_at->format('M d, Y H:i') }}</div></div>
    </div>
</div>

<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px">
    <a class="mp-secondary-btn" href="{{ route('websites.index') }}">Back to websites</a>
    <a class="mp-primary-btn" href="{{ route('servers.show',$website->server) }}">Open server</a>
</div>
@endsection
