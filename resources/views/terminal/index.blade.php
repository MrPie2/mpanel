@extends('layouts.app')
@section('title','Web Terminal')
@section('content')
<div style="margin-bottom:24px">
    <div class="mp-eyebrow">Developer tools</div>
    <h1>Web Terminal</h1>
    <p class="mp-sub" style="margin-bottom:0">Choose a website to open its terminal workspace.</p>
</div>

@if($websites->isEmpty())
    <div class="mp-card" style="padding:36px;text-align:center">
        <div style="font-size:34px;margin-bottom:10px">⌘</div>
        <h2>No websites available</h2>
        <p class="mp-sub">Provision a website on one of your servers before setting up its terminal.</p>
        <a class="mp-primary-btn" href="{{ route('websites.index') }}">View websites</a>
    </div>
@else
    <div class="mp-grid">
        @foreach($websites as $website)
            <article class="mp-card" style="display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;align-items:flex-start;gap:12px">
                    <div style="width:44px;height:44px;display:grid;place-items:center;border-radius:12px;background:var(--brand-soft);color:var(--brand);font-size:22px">⌘</div>
                    <div style="min-width:0;flex:1">
                        <h2 style="margin:0 0 3px;font-size:16px;overflow-wrap:anywhere">{{ $website->domain }}</h2>
                        <p class="mp-sub" style="margin:0">{{ $website->server->name }}</p>
                    </div>
                </div>
                <div style="font-size:12px;color:var(--muted);overflow-wrap:anywhere">{{ $website->document_root }}</div>
                <div style="margin-top:auto"><a class="mp-secondary-btn" href="{{ route('websites.terminal', $website) }}">Open terminal workspace</a></div>
            </article>
        @endforeach
    </div>
@endif
@endsection
