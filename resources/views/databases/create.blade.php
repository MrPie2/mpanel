@extends('layouts.app')
@section('title','Create database')
@section('content')
<div class="mp-page-head"><div><div class="mp-eyebrow">Data services</div><h1>Create database</h1><p class="mp-sub">Create a database and a dedicated database user.</p></div></div>
<div class="mp-card" style="max-width:720px">
@if($errors->any())<div class="mp-alert" style="margin-bottom:18px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('databases.store') }}">
@csrf
<label class="mp-label">Server</label>
<select name="server_id" class="mp-input" style="width:100%;margin-bottom:18px" required>
<option value="">Select server</option>
@foreach($servers as $server)<option value="{{ $server->id }}" @selected(old('server_id')==$server->id)>{{ $server->name }} · {{ $server->ip_address }}</option>@endforeach
</select>
<label class="mp-label">Website (optional)</label>
<select name="website_id" class="mp-input" style="width:100%;margin-bottom:18px">
<option value="">Not linked to a website</option>
@foreach($websites as $website)<option value="{{ $website->id }}" @selected(old('website_id')==$website->id)>{{ $website->domain }}</option>@endforeach
</select>
<label class="mp-label">Database name</label>
<input name="name" value="{{ old('name') }}" class="mp-input" style="width:100%;margin-bottom:6px" placeholder="wordpress" required>
<div class="mp-sub" style="margin-bottom:18px">mPanel adds a safe server/user prefix automatically.</div>
<label class="mp-label">Database username</label>
<input name="username" value="{{ old('username') }}" class="mp-input" style="width:100%;margin-bottom:18px" placeholder="wordpress" required>
<label class="mp-label">Password</label>
<input name="password" type="password" class="mp-input" style="width:100%;margin-bottom:6px" minlength="12" required>
<div class="mp-sub">Use at least 12 characters. The password is encrypted in mPanel and sent to the Agent only for provisioning.</div>
<div style="display:flex;gap:10px;margin-top:22px"><button class="mp-primary-btn" type="submit">Create database</button><a class="mp-secondary-btn" href="{{ route('databases.index') }}">Cancel</a></div>
</form>
</div>
@endsection
