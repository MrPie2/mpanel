@extends('layouts.app')
@section('title','Files · '.$website->domain)
@section('content')
<div class="mp-page-head">
    <div><div class="mp-eyebrow">File Manager</div><h1>{{ $website->domain }}</h1><p class="mp-sub">Browse and manage files inside {{ $website->document_root }}</p></div>
    <a class="mp-secondary-btn" href="{{ route('websites.show',$website) }}">Back to website</a>
</div>

<div class="mp-card">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px">
        <strong id="currentPath">/</strong>
        <button class="mp-primary-btn" type="button" onclick="loadFiles()">Refresh</button>
        <button class="mp-secondary-btn" type="button" onclick="createItem('create_folder')">New folder</button>
        <button class="mp-secondary-btn" type="button" onclick="createItem('create_file')">New file</button>
        <button class="mp-secondary-btn" type="button" onclick="document.getElementById('uploadInput').click()">Upload</button>
        <input id="uploadInput" type="file" hidden onchange="uploadFile(this.files[0])">
    </div>
    <div id="fileStatus" class="mp-sub">Loading files…</div>
    <div style="overflow-x:auto">
        <table class="mp-table" style="width:100%">
            <thead><tr><th>Name</th><th>Type</th><th>Size</th><th></th></tr></thead>
            <tbody id="fileRows"></tbody>
        </table>
    </div>
</div>

<div id="editorCard" class="mp-card" style="display:none;margin-top:18px">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
        <div><div class="mp-eyebrow">Text Editor</div><strong id="editorName"></strong></div>
        <div style="display:flex;gap:8px">
            <button class="mp-secondary-btn" type="button" onclick="closeEditor()">Close</button>
            <button class="mp-primary-btn" type="button" onclick="saveEditor()">Save changes</button>
        </div>
    </div>
    <textarea id="editor" spellcheck="false" style="width:100%;min-height:420px;resize:vertical;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;line-height:1.6;padding:14px;border:1px solid rgba(127,127,127,.25);border-radius:12px;background:transparent;color:inherit"></textarea>
    <div id="editorStatus" class="mp-sub" style="margin-top:8px">Maximum editable text size: 512 KB.</div>
</div>

<script>
let currentPath='';
let editorPath='';
const token=()=>document.querySelector('meta[name="csrf-token"]').content;

async function loadFiles(){
    const status=document.getElementById('fileStatus');
    status.textContent='Requesting directory contents…';
    const response=await fetch(@json(route('websites.files.jobs',$website)),{
        method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token(),'Accept':'application/json'},
        body:JSON.stringify({path:currentPath})
    });
    if(!response.ok){status.textContent='Unable to request directory contents.';return;}
    const job=await response.json();
    poll(@json(route('websites.files.jobs.status',[$website,'JOB'])).replace('JOB',job.job_id),data=>{
        if(data.status==='completed'){
            renderFiles(data.result?.entries||[]);
            status.textContent=(data.result?.entries||[]).length+' item(s)';
            return true;
        }
        if(data.status==='failed'){status.textContent=data.error||'The Agent could not read this directory.';return true;}
        status.textContent='Agent is reading the directory…';
    });
}

function poll(url,callback){
    const timer=setInterval(async()=>{
        try{
            const response=await fetch(url,{headers:{'Accept':'application/json'}});
            const data=await response.json();
            if(callback(data)===true)clearInterval(timer);
        }catch(e){clearInterval(timer);document.getElementById('fileStatus').textContent='Request failed.';}
    },700);
}

function renderFiles(entries){
    const rows=document.getElementById('fileRows'); rows.innerHTML='';
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
        const actions=document.createElement('div'); actions.style.display='flex';actions.style.gap='6px';actions.style.flexWrap='wrap';
        if(entry.type==='file'){
            const edit=document.createElement('button'); edit.className='mp-secondary-btn';edit.textContent='Edit';edit.onclick=()=>openEditor(entry.name);
            const dl=document.createElement('button');dl.className='mp-secondary-btn';dl.textContent='Download';dl.onclick=()=>downloadItem(entry.name);
            actions.append(edit,dl);
        }
        const rename=document.createElement('button');rename.className='mp-secondary-btn';rename.textContent='Rename';rename.onclick=()=>renameItem(entry.name);
        const del=document.createElement('button');del.className='mp-secondary-btn';del.textContent='Delete';del.onclick=()=>deleteItem(entry.name,entry.type);
        actions.append(rename,del);tr.children[3].appendChild(actions);
        if(entry.type==='directory'){
            tr.children[0].style.cursor='pointer';
            tr.children[0].onclick=()=>{currentPath=currentPath?currentPath+'/'+entry.name:entry.name;updatePath();loadFiles();};
        }
        rows.appendChild(tr);
    });
}

async function operation(payload){
    const response=await fetch(@json(route('websites.files.operations',$website)),{
        method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token(),'Accept':'application/json'},
        body:JSON.stringify(payload)
    });
    if(!response.ok){document.getElementById('fileStatus').textContent='Unable to queue operation.';return;}
    const job=await response.json();
    poll(@json(route('websites.files.operations.status',[$website,'JOB'])).replace('JOB',job.job_id),data=>{
        if(data.status==='completed'){loadFiles();return true;}
        if(data.status==='failed'){document.getElementById('fileStatus').textContent=data.error||'Operation failed.';return true;}
    });
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
    operation({operation:'delete',path:(currentPath?currentPath+'/':'')+name});
}

async function uploadFile(file){
    if(!file)return;
    if(file.size>5*1024*1024){document.getElementById('fileStatus').textContent='Upload is limited to 5 MB.';return;}
    const form=new FormData();form.append('path',currentPath);form.append('file',file);
    document.getElementById('fileStatus').textContent='Uploading '+file.name+'…';
    const response=await fetch(@json(route('websites.files.upload',$website)),{method:'POST',headers:{'X-CSRF-TOKEN':token(),'Accept':'application/json'},body:form});
    if(!response.ok){document.getElementById('fileStatus').textContent='Unable to queue upload.';return;}
    const job=await response.json();
    poll(@json(route('websites.files.upload.status',[$website,'JOB'])).replace('JOB',job.job_id),data=>{
        if(data.status==='completed'){document.getElementById('fileStatus').textContent='Upload complete.';loadFiles();return true;}
        if(data.status==='failed'){document.getElementById('fileStatus').textContent=data.error||'Upload failed.';return true;}
        document.getElementById('fileStatus').textContent='Agent is writing '+file.name+'…';
    });
}

async function openEditor(name){
    const path=(currentPath?currentPath+'/':'')+name;
    editorPath=path;
    document.getElementById('editorCard').style.display='block';
    document.getElementById('editorName').textContent=path;
    document.getElementById('editorStatus').textContent='Loading file…';
    document.getElementById('editor').value='';
    const response=await fetch(@json(route('websites.files.download',$website)),{
        method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token(),'Accept':'application/json'},
        body:JSON.stringify({path})
    });
    if(!response.ok){document.getElementById('editorStatus').textContent='Unable to open file.';return;}
    const job=await response.json();
    poll(@json(route('websites.files.download.status',[$website,'JOB'])).replace('JOB',job.job_id),data=>{
        if(data.status==='completed'){
            try{
                const raw=atob(data.result.content_base64);
                const bytes=Uint8Array.from(raw,c=>c.charCodeAt(0));
                document.getElementById('editor').value=new TextDecoder().decode(bytes);
                if(bytes.length>512*1024)document.getElementById('editorStatus').textContent='File is larger than the editor limit and is read-only.';
                else document.getElementById('editorStatus').textContent='Editing '+formatBytes(bytes.length);
            }catch(e){document.getElementById('editorStatus').textContent='File is not valid text.';}
            return true;
        }
        if(data.status==='failed'){document.getElementById('editorStatus').textContent=data.error||'Unable to open file.';return true;}
    });
}
async function saveEditor(){
    const editor=document.getElementById('editor');
    if(new TextEncoder().encode(editor.value).length>512*1024){document.getElementById('editorStatus').textContent='File exceeds the 512 KB editor limit.';return;}
    const name=editorPath.split('/').pop();
    const path=editorPath.split('/').slice(0,-1).join('/');
    document.getElementById('editorStatus').textContent='Saving…';
    await operation({operation:'write_file',path,name,content:editor.value});
    document.getElementById('editorStatus').textContent='Save queued.';
}
function closeEditor(){document.getElementById('editorCard').style.display='none';editorPath='';}
async function downloadItem(name){
    const path=(currentPath?currentPath+'/':'')+name;
    document.getElementById('fileStatus').textContent='Preparing download…';
    const response=await fetch(@json(route('websites.files.download',$website)),{
        method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token(),'Accept':'application/json'},
        body:JSON.stringify({path})
    });
    if(!response.ok){document.getElementById('fileStatus').textContent='Unable to queue download.';return;}
    const job=await response.json();
    poll(@json(route('websites.files.download.status',[$website,'JOB'])).replace('JOB',job.job_id),data=>{
        if(data.status==='completed'){
            const raw=atob(data.result.content_base64),bytes=new Uint8Array(raw.length);
            for(let i=0;i<raw.length;i++)bytes[i]=raw.charCodeAt(i);
            const blob=new Blob([bytes]);const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=data.result.name;a.click();URL.revokeObjectURL(a.href);
            document.getElementById('fileStatus').textContent='Download ready.';return true;
        }
        if(data.status==='failed'){document.getElementById('fileStatus').textContent=data.error||'Download failed.';return true;}
    });
}
function updatePath(){document.getElementById('currentPath').textContent='/'+(currentPath||'');}
function formatBytes(bytes){if(bytes===0)return '0 B';if(!bytes)return '—';const units=['B','KB','MB','GB'];const i=Math.floor(Math.log(bytes)/Math.log(1024));return (bytes/Math.pow(1024,i)).toFixed(i?1:0)+' '+units[i];}
loadFiles();
</script>
@endsection
