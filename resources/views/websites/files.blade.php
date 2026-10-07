@extends('layouts.app')
@section('title','Files · '.$website->domain)
@section('content')
<div class="mp-page-head">
    <div><div class="mp-eyebrow">File Manager</div><h1>{{ $website->domain }}</h1><p class="mp-sub">Browse files inside {{ $website->document_root }}</p></div>
    <a class="mp-secondary-btn" href="{{ route('websites.show',$website) }}">Back to website</a>
</div>

<div class="mp-card">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px">
        <strong id="currentPath">/</strong>
        <button class="mp-primary-btn" type="button" onclick="loadFiles()">Refresh</button><button class="mp-secondary-btn" type="button" onclick="createItem('create_folder')">New folder</button><button class="mp-secondary-btn" type="button" onclick="createItem('create_file')">New file</button>
    </div>
    <div id="fileStatus" class="mp-sub">Loading files…</div>
    <div style="overflow-x:auto">
        <table class="mp-table" style="width:100%">
            <thead><tr><th>Name</th><th>Type</th><th>Size</th><th></th></tr></thead>
            <tbody id="fileRows"></tbody>
        </table>
    </div>
</div>

<script>
const rootPath = '';
let currentPath = '';
async function loadFiles() {
    const status=document.getElementById('fileStatus');
    status.textContent='Requesting directory contents…';
    const token=document.querySelector('meta[name="csrf-token"]').content;
    const response=await fetch(@json(route('websites.files.jobs',$website)),{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
        body:JSON.stringify({path:currentPath})
    });
    if(!response.ok){status.textContent='Unable to request directory contents.';return;}
    const job=await response.json();
    const timer=setInterval(async()=>{
        const result=await fetch(@json(route('websites.files.jobs.status',[$website,'JOB'])).replace('JOB',job.job_id),{headers:{'Accept':'application/json'}});
        const data=await result.json();
        if(data.status==='completed'){
            clearInterval(timer);
            renderFiles(data.result?.entries || []);
            status.textContent=(data.result?.entries || []).length+' item(s)';
        } else if(data.status==='failed'){
            clearInterval(timer);
            status.textContent=data.error || 'The Agent could not read this directory.';
        } else {
            status.textContent='Agent is reading the directory…';
        }
    },1000);
}
function renderFiles(entries){
    const rows=document.getElementById('fileRows');
    rows.innerHTML='';
    if(currentPath){
        const tr=document.createElement('tr');
        tr.innerHTML='<td colspan="4"><button class="mp-secondary-btn" type="button">↩ ..</button></td>';
        tr.querySelector('button').onclick=()=>{currentPath=currentPath.split('/').slice(0,-1).join('/');updatePath();loadFiles();};
        rows.appendChild(tr);
    }
    entries.forEach(entry=>{
        const tr=document.createElement('tr');
        tr.innerHTML='<td><strong></strong></td><td></td><td></td><td></td>';
        tr.children[0].querySelector('strong').textContent=(entry.type==='directory'?'▰ ':'')+entry.name;
        tr.children[1].textContent=entry.type;
        tr.children[2].textContent=entry.type==='file'?formatBytes(entry.size):'—';
        const actions=document.createElement('div'); actions.style.display='flex'; actions.style.gap='6px';
        const rename=document.createElement('button'); rename.className='mp-secondary-btn'; rename.textContent='Rename'; rename.onclick=()=>renameItem(entry.name);
        const del=document.createElement('button'); del.className='mp-secondary-btn'; del.textContent='Delete'; del.onclick=()=>deleteItem(entry.name,entry.type);
        actions.append(rename,del); tr.children[3].appendChild(actions);
        if(entry.type==='directory') tr.children[0].style.cursor='pointer';
        if(entry.type==='directory') tr.children[0].onclick=()=>{currentPath=currentPath?currentPath+'/'+entry.name:entry.name;updatePath();loadFiles();};
        rows.appendChild(tr);
    });
}
async function operation(payload){
    const token=document.querySelector('meta[name="csrf-token"]').content;
    const response=await fetch(@json(route('websites.files.operations',$website)),{
        method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
        body:JSON.stringify(payload)
    });
    if(!response.ok){document.getElementById('fileStatus').textContent='Unable to queue operation.';return;}
    const job=await response.json();
    const timer=setInterval(async()=>{
        const result=await fetch(@json(route('websites.files.operations.status',[$website,'JOB'])).replace('JOB',job.job_id),{headers:{'Accept':'application/json'}});
        const data=await result.json();
        if(data.status==='completed'){clearInterval(timer);loadFiles();}
        else if(data.status==='failed'){clearInterval(timer);document.getElementById('fileStatus').textContent=data.error||'Operation failed.';}
    },700);
}
function createItem(type){
    const name=prompt(type==='create_folder'?'Folder name:':'File name:');
    if(!name)return;
    operation({operation:type,path:currentPath,name});
}
function renameItem(name){
    const next=prompt('New name:',name);
    if(!next||next===name)return;
    operation({operation:'rename',path:currentPath,name,new_name:next});
}
function deleteItem(name,type){
    if(!confirm('Delete '+name+(type==='directory'?' and everything inside it':'')+'?'))return;
    operation({operation:'delete',path:currentPath+'/'+name});
}
function updatePath(){document.getElementById('currentPath').textContent='/'+(currentPath||'');}
function formatBytes(bytes){if(bytes===0)return '0 B';if(!bytes)return '—';const units=['B','KB','MB','GB'];const i=Math.floor(Math.log(bytes)/Math.log(1024));return (bytes/Math.pow(1024,i)).toFixed(i?1:0)+' '+units[i];}
loadFiles();
</script>
@endsection
