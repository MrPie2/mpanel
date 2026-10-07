@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mp-eyebrow">Mangrove Control Plane</div>
<h1>Good morning, {{ auth()->user()->name }}.</h1>
<p class="mp-sub">Manage your hosting infrastructure from one place.</p>

<div class="mp-grid">
    <div class="mp-card"><div class="mp-label">Servers</div><div class="mp-metric">0</div></div>
    <div class="mp-card"><div class="mp-label">Websites</div><div class="mp-metric">0</div></div>
    <div class="mp-card"><div class="mp-label">Databases</div><div class="mp-metric">0</div></div>
    <div class="mp-card"><div class="mp-label">Storage Used</div><div class="mp-metric">0 GB</div></div>
</div>

<div style="margin-top:22px">
    <h2 style="font-size:17px">Quick actions</h2>
    <div class="mp-actions">
        <a class="mp-action" href="#"><span>＋</span>Add Server</a>
        <a class="mp-action" href="#"><span>⌂</span>Create Website</a>
        <a class="mp-action" href="#"><span>◉</span>Create Database</a>
        <a class="mp-action" href="#"><span>↥</span>Deploy from Git</a>
    </div>
</div>

<div class="mp-card" style="margin-top:22px">
    <h2 style="font-size:17px;margin-top:0">System status</h2>
    <p class="mp-sub" style="margin-bottom:0">mPanel is ready for the Agent layer. Server telemetry and provisioning will appear here once a VPS is connected.</p>
</div>
@endsection
