@extends('layouts.app')
@section('title','Databases')
@section('content')
<div class="mp-page-head">
    <div><div class="mp-eyebrow">Data services</div><h1>Databases</h1><p class="mp-sub">Manage MySQL and MariaDB databases on your hosting servers.</p></div>
    <a class="mp-primary-btn" href="{{ route('databases.create') }}">+ Create database</a>
</div>

<div class="mp-card" style="padding:0;overflow:hidden">
<table style="width:100%;border-collapse:collapse">
<thead><tr><th style="text-align:left;padding:15px">Database</th><th style="text-align:left;padding:15px">User</th><th style="text-align:left;padding:15px">Website</th><th style="text-align:left;padding:15px">Server</th><th style="text-align:left;padding:15px">Status</th><th style="padding:15px"></th></tr></thead>
<tbody>
@forelse($databases as $database)
<tr style="border-top:1px solid var(--mp-border)">
<td style="padding:15px"><strong>{{ $database->name }}</strong></td>
<td style="padding:15px">{{ $database->username }}</td>
<td style="padding:15px">{{ $database->website?->domain ?? '—' }}</td>
<td style="padding:15px">{{ $database->server->name }}</td>
<td style="padding:15px"><span class="mp-status {{ $database->status }}">{{ ucfirst($database->status) }}</span></td>
<td style="padding:15px;text-align:right"><a class="mp-secondary-btn" href="{{ route('databases.show',$database) }}">Manage</a></td>
</tr>
@empty
<tr><td colspan="6" style="padding:45px;text-align:center"><strong>No databases yet</strong><div class="mp-sub" style="margin-top:6px">Create your first MySQL/MariaDB database.</div></td></tr>
@endforelse
</tbody>
</table>
</div>
<div style="margin-top:18px">{{ $databases->links() }}</div>
@endsection
