@extends('layouts.app')
@section('title','DNS · '.$website->domain)
@section('content')
<div class="mp-page-head">
<div><div class="mp-eyebrow">Network</div><h1>DNS Manager</h1><p class="mp-sub">{{ $website->domain }}</p></div>
<a class="mp-secondary-btn" href="{{ route('websites.show',$website) }}">Back to website</a>
</div>

<div class="mp-card" style="margin-bottom:20px">
<h2 style="font-size:17px;margin:0 0 18px">Add DNS record</h2>
<form method="POST" action="{{ route('websites.dns.store',$website) }}" style="display:grid;grid-template-columns:110px 1fr 1fr 110px 120px auto;gap:10px;align-items:end">
@csrf
<div><label class="mp-label">Type</label><select name="type" class="mp-input" style="width:100%"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option><option>NS</option></select></div>
<div><label class="mp-label">Name</label><input name="name" class="mp-input" style="width:100%" placeholder="@ or www" required></div>
<div><label class="mp-label">Value</label><input name="value" class="mp-input" style="width:100%" placeholder="Record value" required></div>
<div><label class="mp-label">TTL</label><input name="ttl" type="number" value="3600" min="60" max="86400" class="mp-input" style="width:100%" required></div>
<div><label class="mp-label">Priority</label><input name="priority" type="number" min="0" max="65535" class="mp-input" style="width:100%" placeholder="MX"></div>
<button class="mp-primary-btn" type="submit">Add</button>
</form>
</div>

<div class="mp-card" style="padding:0;overflow:hidden">
<div style="padding:18px;border-bottom:1px solid var(--mp-border)"><strong>DNS records</strong><div class="mp-sub">Changes are sent to the mPanel Agent.</div></div>
<div style="overflow-x:auto"><table class="mp-table" style="width:100%">
<thead><tr><th>Type</th><th>Name</th><th>Value</th><th>TTL</th><th>Priority</th><th></th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td><strong>{{ $record->type }}</strong></td><td>{{ $record->name }}</td><td style="word-break:break-all">{{ $record->value }}</td><td>{{ $record->ttl }}</td><td>{{ $record->priority ?? '—' }}</td><td style="text-align:right"><form method="POST" action="{{ route('websites.dns.destroy',[$website,$record]) }}" onsubmit="return confirm('Delete this DNS record?')">@csrf @method('DELETE')<button class="mp-secondary-btn" type="submit">Delete</button></form></td></tr>
@empty
<tr><td colspan="6" style="padding:45px;text-align:center"><strong>No DNS records</strong><div class="mp-sub">Add an A, CNAME, MX, TXT or other supported record.</div></td></tr>
@endforelse
</tbody>
</table></div></div>
@endsection