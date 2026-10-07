@extends('layouts.app')
@section('title','SSL · '.$website->domain)
@section('content')
<div class="mp-page-head">
    <div><div class="mp-eyebrow">Security</div><h1>SSL certificate</h1><p class="mp-sub">{{ $website->domain }} · Let's Encrypt</p></div>
    <a class="mp-secondary-btn" href="{{ route('websites.show',$website) }}">Back to website</a>
</div>

<div class="mp-card" style="max-width:760px">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
        <div>
            <div class="mp-label">HTTPS status</div>
            <div class="mp-metric" style="font-size:24px" id="sslStatus">{{ $website->ssl_enabled ? 'Active' : 'Not active' }}</div>
            <div class="mp-sub" id="sslExpiry">
                @if($website->ssl_expires_at)
                    Expires {{ $website->ssl_expires_at->format('M d, Y H:i') }}
                @else
                    No certificate is currently recorded.
                @endif
            </div>
        </div>
        <span class="mp-status {{ $website->ssl_enabled ? 'active' : 'pending' }}" id="sslBadge">{{ $website->ssl_enabled ? 'HTTPS active' : 'Not configured' }}</span>
    </div>

    <div style="margin-top:24px">
        <label class="mp-label" for="sslEmail">Certificate email</label>
        <input id="sslEmail" type="email" value="{{ auth()->user()->email }}" class="mp-input" placeholder="you@example.com" style="width:100%;max-width:520px">
        <div class="mp-sub" style="margin-top:7px">Let's Encrypt uses this address for important certificate notices.</div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
        <button class="mp-primary-btn" type="button" id="issueBtn" onclick="issueSsl()">{{ $website->ssl_enabled ? 'Reissue certificate' : 'Enable HTTPS' }}</button>
        @if($website->ssl_enabled)
            <button class="mp-secondary-btn" type="button" onclick="disableSsl()">Disable HTTPS</button>
        @endif
    </div>
    <div id="sslMessage" class="mp-sub" style="margin-top:14px"></div>
</div>

<div class="mp-card" style="max-width:760px;margin-top:18px">
    <h2 style="font-size:17px;margin:0 0 10px">Before enabling SSL</h2>
    <ul class="mp-sub" style="margin:0;padding-left:20px;line-height:1.8">
        <li>{{ $website->domain }} must resolve to this server.</li>
        <li>HTTP port 80 must be reachable from the internet for HTTP validation.</li>
        <li>Certbot must be installed on the mPanel Agent server.</li>
        <li>The Agent will validate Nginx before reloading it.</li>
    </ul>
</div>

<script>
const csrf=document.querySelector('meta[name="csrf-token"]').content;
const message=document.getElementById('sslMessage');
function setMessage(t){message.textContent=t;}
function poll(url,done){
    const timer=setInterval(async()=>{
        try{
            const r=await fetch(url,{headers:{'Accept':'application/json'}});
            const d=await r.json();
            if(d.status==='completed'||d.status==='failed'){clearInterval(timer);done(d);}
        }catch(e){clearInterval(timer);setMessage('Unable to check the Agent job.');}
    },1000);
}
async function issueSsl(){
    const email=document.getElementById('sslEmail').value;
    document.getElementById('issueBtn').disabled=true;
    setMessage('Queueing SSL certificate request…');
    const r=await fetch(@json(route('websites.ssl.issue',$website)),{
        method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
        body:JSON.stringify({email})
    });
    if(!r.ok){document.getElementById('issueBtn').disabled=false;setMessage('Unable to queue SSL request. Check the email address.');return;}
    const job=await r.json();setMessage('The Agent is requesting and configuring the certificate…');
    poll(@json(route('websites.ssl.issue.status',[$website,'JOB'])).replace('JOB',job.job_id),d=>{
        document.getElementById('issueBtn').disabled=false;
        if(d.status==='completed'){setMessage('HTTPS is now active. Reloading…');setTimeout(()=>location.reload(),700);}
        else setMessage(d.error||'SSL issuance failed.');
    });
}
async function disableSsl(){
    if(!confirm('Disable HTTPS for {{ $website->domain }}?'))return;
    setMessage('Queueing SSL removal…');
    const r=await fetch(@json(route('websites.ssl.disable',$website)),{
        method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}
    });
    if(!r.ok){setMessage('Unable to queue SSL removal.');return;}
    const job=await r.json();setMessage('The Agent is updating Nginx…');
    poll(@json(route('websites.ssl.disable.status',[$website,'JOB'])).replace('JOB',job.job_id),d=>{
        if(d.status==='completed'){setMessage('HTTPS has been disabled. Reloading…');setTimeout(()=>location.reload(),700);}
        else setMessage(d.error||'SSL removal failed.');
    });
}
</script>
@endsection
