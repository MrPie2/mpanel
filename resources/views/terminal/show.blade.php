@extends('layouts.app')
@section('title','Terminal · '.$website->domain)
@push('head')
<style>
    .mp-terminal-window{overflow:hidden;border:1px solid var(--border);border-radius:14px;background:#0b100f;color:#e5eee9}
    .mp-terminal-top{display:flex;align-items:center;gap:8px;padding:13px 16px;border-bottom:1px solid #25312b;background:#121a16}
    .mp-terminal-dot{width:9px;height:9px;border-radius:50%;background:#5c7064}
    .mp-terminal-body{min-height:280px;padding:22px;font:13px/1.8 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
    .mp-terminal-prompt{color:#8bd6a0}
    .mp-terminal-disabled{opacity:.75;cursor:not-allowed}
    @media(max-width:650px){.mp-terminal-body{padding:14px;font-size:12px;overflow-wrap:anywhere}}
</style>
@endpush
@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap;margin-bottom:22px">
    <div>
        <div class="mp-eyebrow">Developer tools / Web Terminal</div>
        <h1 style="overflow-wrap:anywhere">{{ $website->domain }}</h1>
        <p class="mp-sub" style="margin-bottom:0">Terminal workspace for {{ $website->document_root }}</p>
    </div>
    <a class="mp-secondary-btn" href="{{ route('terminal.index') }}">← All websites</a>
</div>

<div class="mp-card" style="margin-bottom:18px;border-left:4px solid #d7a84b">
    <div style="display:flex;gap:12px;align-items:flex-start">
        <div style="font-size:22px">🔒</div>
        <div>
            <strong>Terminal execution is not enabled yet</strong>
            <p class="mp-sub" style="margin:4px 0 0">This workspace is a UI foundation only. Website isolation is currently {{ str_replace("-", " ", $website->terminal_isolation_status ?? "pending") }}. mPanel will enable commands only after a dedicated Linux identity, filesystem permissions, isolated PHP runtime, and terminal gateway are configured and verified. No commands are sent to the server from this page.</p>
        </div>
    </div>
</div>

<div class="mp-terminal-window">
    <div class="mp-terminal-top">
        <span class="mp-terminal-dot"></span><span class="mp-terminal-dot"></span><span class="mp-terminal-dot"></span>
        <span style="margin-left:8px;font-size:12px;color:#9dafa5">{{ $website->domain }} · Shell preview</span>
        <span style="margin-left:auto;font-size:11px;color:#d7b36a">Isolation: {{ ucfirst($website->terminal_isolation_status ?? "pending") }}</span>
    </div>
    <div class="mp-terminal-body">
        <div><span class="mp-terminal-prompt">preview@mpanel:~$</span> <span style="color:#91a39a">_</span></div>
        <div style="margin-top:12px;color:#82948a">Your interactive shell will appear here once secure terminal sessions are available.</div>
    </div>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
    <button class="mp-primary-btn mp-terminal-disabled" type="button" disabled aria-disabled="true">Start session</button>
    <span class="mp-sub" style="align-self:center">Planned: PHP · Composer · Git · Node.js (subject to plan)</span>
</div>
@endsection
