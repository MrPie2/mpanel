@extends('layouts.app')
@section('title', 'Git · '.$website->domain)
@section('content')
<div class="mp-page-head">
    <div>
        <div class="mp-eyebrow">Deployment</div>
        <h1>Git deployment</h1>
        <p class="mp-sub">{{ $website->domain }} · public GitHub repositories</p>
    </div>
    <a class="mp-secondary-btn" href="{{ route('websites.show',$website) }}">Back</a>
</div>

<div class="mp-card" style="margin-bottom:22px">
    <h2 style="font-size:17px;margin:0 0 18px">Connect repository</h2>
    <form id="git-connect-form">
        @csrf
        <div style="display:grid;gap:16px;grid-template-columns:2fr 1fr">
            <div><label class="mp-label">GitHub HTTPS URL</label><input id="repository_url" class="form-control" placeholder="https://github.com/owner/repository" required></div>
            <div><label class="mp-label">Branch</label><input id="branch" class="form-control" value="{{ $git->branch ?? 'main' }}" required></div>
        </div>
        <button class="mp-primary-btn" style="margin-top:16px">Connect repository</button>
    </form>
</div>

@if($git)
<div class="mp-card">
    <div style="display:flex;justify-content:space-between;gap:15px;align-items:flex-start;flex-wrap:wrap">
        <div>
            <div class="mp-eyebrow">Connected repository</div>
            <h2 style="font-size:18px;margin:4px 0">{{ $git->repository_url }}</h2>
            <div class="mp-sub">Branch: {{ $git->branch }} · Deploy path: {{ $git->deploy_path }}</div>
        </div>
        <span class="mp-status {{ $git->status }}">{{ ucfirst($git->status) }}</span>
    </div>
    <div class="mp-grid" style="margin-top:20px">
        <div><div class="mp-label">Last commit</div><div>{{ $git->last_commit ?? '—' }}</div></div>
        <div><div class="mp-label">Last deployed</div><div>{{ $git->last_deployed_at?->format('M d, Y H:i') ?? '—' }}</div></div>
    </div>
    @if($git->last_error)<div style="margin-top:18px;padding:12px;border:1px solid #f0caca;border-radius:10px">{{ $git->last_error }}</div>@endif
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
        <button class="mp-primary-btn" id="deploy-btn">Deploy now</button>
        <button class="mp-secondary-btn" id="disconnect-btn">Disconnect</button>
    </div>
    <div id="webhook-secret" style="display:none;margin-top:18px"></div>
</div>
@endif

<script>
const connectForm=document.getElementById('git-connect-form');
connectForm?.addEventListener('submit',async e=>{
 e.preventDefault();
 const r=await fetch('{{ route('websites.git.connect',$website) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({repository_url:document.getElementById('repository_url').value,branch:document.getElementById('branch').value})});
 const d=await r.json(); if(!r.ok){alert(d.message||'Unable to connect repository.');return;}
 document.getElementById('webhook-secret').style.display='block';
 document.getElementById('webhook-secret').innerHTML='<strong>GitHub webhook secret:</strong><br><code>'+d.webhook_secret+'</code><br><small>Copy this now. mPanel does not display it again.</small><br><strong>Webhook URL:</strong> '+d.webhook_url;
 location.reload();
});
document.getElementById('deploy-btn')?.addEventListener('click',async()=>{
 const r=await fetch('{{ route('websites.git.deploy',$website) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
 const d=await r.json(); if(!r.ok){alert(d.message||'Unable to queue deployment.');return;}
 alert('Deployment queued. Refresh shortly to see the result.'); location.reload();
});
document.getElementById('disconnect-btn')?.addEventListener('click',async()=>{
 if(!confirm('Disconnect Git deployment?')) return;
 const r=await fetch('{{ route('websites.git.disconnect',$website) }}',{method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
 if(r.ok) location.reload(); else alert('Unable to disconnect Git.');
});
</script>
@endsection