@extends('layouts.app')
@section('title',$database->name)
@section('content')
<div class="mp-page-head">
<div><div class="mp-eyebrow">Database management</div><h1>{{ $database->name }}</h1><p class="mp-sub">{{ $database->server->name }} · MySQL/MariaDB</p></div>
<a class="mp-secondary-btn" href="{{ route('databases.index') }}">Back to databases</a>
</div>
<div class="mp-grid" style="margin-bottom:22px">
<div class="mp-card"><div class="mp-label">Status</div><div class="mp-metric" style="font-size:22px" id="dbStatus">{{ ucfirst($database->status) }}</div><div class="mp-sub">Provisioning state</div></div>
<div class="mp-card"><div class="mp-label">Database user</div><div class="mp-metric" style="font-size:18px;word-break:break-all">{{ $database->username }}</div><div class="mp-sub">Dedicated account</div></div>
<div class="mp-card"><div class="mp-label">Host</div><div class="mp-metric" style="font-size:18px">{{ $database->host }}:{{ $database->port }}</div><div class="mp-sub">Application connection endpoint</div></div>
</div>
<div class="mp-card" style="max-width:760px">
<h2 style="font-size:17px;margin:0 0 18px">Connection details</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px">
<div><div class="mp-label">Database</div><div style="word-break:break-all">{{ $database->name }}</div></div>
<div><div class="mp-label">Username</div><div style="word-break:break-all">{{ $database->username }}</div></div>
<div><div class="mp-label">Host</div><div>{{ $database->host }}</div></div>
<div><div class="mp-label">Port</div><div>{{ $database->port }}</div></div>
</div>
@if($database->website)<div class="mp-sub" style="margin-top:18px">Linked website: {{ $database->website->domain }}</div>@endif
@if($database->last_error)<div class="mp-alert" style="margin-top:18px">{{ $database->last_error }}</div>@endif
<div style="margin-top:24px"><button class="mp-secondary-btn" onclick="deleteDatabase()">Delete database</button></div>
</div>
<form id="deleteForm" method="POST" action="{{ route('databases.destroy',$database) }}" style="display:none">@csrf @method('DELETE')</form>
<script>
async function deleteDatabase(){
 if(!confirm('Delete {{ $database->name }} and its database user? This cannot be undone.'))return;
 document.getElementById('deleteForm').submit();
}
</script>
@endsection
