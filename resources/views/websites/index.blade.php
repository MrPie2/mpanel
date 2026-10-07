@extends('layouts.app')
@section('title','Websites')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px">
    <div><div class="mp-eyebrow">Hosting</div><h1>Websites</h1><p class="mp-sub" style="margin-bottom:0">Manage websites provisioned across your mPanel servers.</p></div>
    <a class="mp-primary-btn" href="{{ route('servers.index') }}">Add website</a>
</div>

<div class="mp-card" style="padding:0;overflow:hidden">
@if($websites->count())
<div style="overflow-x:auto"><table class="mp-table" style="width:100%">
<thead><tr><th>Website</th><th>Server</th><th>PHP</th><th>Status</th><th></th></tr></thead>
<tbody>
@foreach($websites as $website)
<tr>
<td><strong>{{ $website->domain }}</strong><div class="mp-sub" style="font-size:12px">{{ $website->document_root }}</div></td>
<td>{{ $website->server->name }}</td>
<td>{{ $website->php_version }}</td>
<td><span class="mp-status {{ $website->status }}">{{ ucfirst($website->status) }}</span></td>
<td style="text-align:right"><a class="mp-secondary-btn" href="{{ route('websites.show',$website) }}">Manage</a></td>
</tr>
@endforeach
</tbody></table></div>
<div style="padding:16px">{{ $websites->links() }}</div>
@else
<div style="padding:52px 24px;text-align:center"><div style="font-size:38px;margin-bottom:12px">◈</div><h2 style="margin-bottom:8px">No websites yet</h2><p class="mp-sub">Add a server and provision your first website to see it here.</p><a class="mp-primary-btn" href="{{ route('servers.index') }}">View servers</a></div>
@endif
</div>
@endsection
