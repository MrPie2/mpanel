@extends('layouts.app')
@section('title','Files · '.$website->domain)

@push('head')
<style>
    .mp-files-toolbar{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-bottom:16px}
    .mp-files-path{display:flex;align-items:center;gap:7px;min-width:0;margin-right:auto;font-weight:750;overflow-wrap:anywhere}
    .mp-files-path span{color:var(--muted);font-weight:500}
    .mp-files-status{color:var(--muted);font-size:12px;min-height:20px;margin:8px 0 12px}
    .mp-files-table-wrap{overflow-x:auto}
    .mp-files-table{width:100%;border-collapse:collapse}
    .mp-files-table th,.mp-files-table td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--border);vertical-align:middle}
    .mp-files-table th{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.07em}
    .mp-files-table tr:last-child td{border-bottom:0}
    .mp-file-name{border:0;background:transparent;color:var(--text);font:inherit;font-weight:700;padding:3px 0;text-align:left;overflow-wrap:anywhere}
    .mp-file-name.is-folder{cursor:pointer;color:var(--brand)}
    .mp-file-actions{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
    .mp-editor-card{margin-top:18px;overflow:hidden;padding:0}
    .mp-editor-header{padding:16px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:1px solid var(--border)}
    .mp-editor-title{min-width:0}
    .mp-editor-title strong{display:block;overflow-wrap:anywhere}
    .mp-editor-subtitle{font-size:12px;color:var(--muted);margin-top:3px}
    .mp-editor-actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap}
    .mp-editor-tabs{display:flex;gap:2px;overflow-x:auto;background:var(--surface-2);border-bottom:1px solid var(--border);padding:6px 8px 0}
    .mp-editor-tab{flex:0 0 auto;display:flex;align-items:center;gap:9px;max-width:240px;padding:10px 11px;border:1px solid transparent;border-bottom:0;border-radius:9px 9px 0 0;background:transparent;color:var(--muted);font:inherit;font-size:12px;cursor:pointer}
    .mp-editor-tab.active{background:var(--surface);color:var(--text);border-color:var(--border)}
    .mp-editor-tab-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .mp-editor-tab-dirty{color:var(--brand);font-size:17px;line-height:10px}
    .mp-editor-tab-close{border:0;background:transparent;color:inherit;padding:0 1px;cursor:pointer;font-size:15px}
    .mp-editor-tools{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:10px 14px;border-bottom:1px solid var(--border)}
    .mp-editor-tools label{display:flex;align-items:center;gap:7px;color:var(--muted);font-size:12px}
    .mp-editor-tools select{max-width:190px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:8px;padding:7px 9px}
    .mp-editor-meta{margin-left:auto;font-size:12px;color:var(--muted)}
    #editorHost{height: min(66vh, 680px);min-height:360px;width:100%;background:var(--surface)}
    #editorFallback{display:none;width:100%;height:min(66vh,680px);min-height:360px;resize:vertical;border:0;outline:0;padding:16px;background:var(--surface);color:var(--text);font:13px/1.65 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;tab-size:4}
    .mp-editor-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 14px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
    .mp-status-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#9ca3af;margin-right:6px}
    .mp-status-dot.ready{background:#22a06b}.mp-status-dot.dirty{background:#d97706}.mp-status-dot.error{background:#dc4444}
    .mp-editor-card.is-fullscreen{position:fixed;z-index:100;inset:10px;margin:0;overflow:auto;box-shadow:0 20px 70px rgba(0,0,0,.35)}
    .mp-editor-card.is-fullscreen #editorHost,.mp-editor-card.is-fullscreen #editorFallback{height:calc(100vh - 230px);max-height:none}
    .mp-files-notice{padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--surface-2);color:var(--muted);font-size:12px;margin-top:12px}
    @media(max-width:650px){.mp-files-table th:nth-child(2),.mp-files-table td:nth-child(2){display:none}.mp-editor-header{padding:13px}.mp-editor-actions{width:100%}.mp-editor-actions button{flex:1}.mp-editor-tools{gap:8px}.mp-editor-meta{margin-left:0;width:100%}#editorHost,#editorFallback{min-height:300px;height:55vh}.mp-editor-card.is-fullscreen{inset:0;border-radius:0}.mp-editor-card.is-fullscreen #editorHost,.mp-editor-card.is-fullscreen #editorFallback{height:calc(100vh - 250px)}}
</style>
@endpush

@section('content')
<div class="mp-page-head">
    <div>
        <div class="mp-eyebrow">File Manager</div>
        <h1>{{ $website->domain }}</h1>
        <p class="mp-sub">Browse and edit files inside <code>{{ $website->document_root }}</code></p>
    </div>
    <a class="mp-secondary-btn" href="{{ route('websites.show',$website) }}">Back to website</a>
</div>

<div class="mp-card">
    <div class="mp-files-toolbar">
        <div class="mp-files-path"><span>Path</span><strong id="currentPath">/</strong></div>
        <button class="mp-primary-btn" type="button" onclick="loadFiles()">Refresh</button>
        <button class="mp-secondary-btn" type="button" onclick="createItem('create_folder')">+ Folder</button>
        <button class="mp-secondary-btn" type="button" onclick="createItem('create_file')">+ File</button>
        <button class="mp-secondary-btn" type="button" onclick="document.getElementById('uploadInput').click()">Upload</button>
        <input id="uploadInput" type="file" hidden onchange="uploadFile(this.files[0]);this.value=''">
    </div>
    <div id="fileStatus" class="mp-files-status" role="status" aria-live="polite">Loading directory…</div>
    <div class="mp-files-table-wrap">
        <table class="mp-files-table">
            <thead><tr><th>Name</th><th>Type</th><th>Size</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody id="fileRows"></tbody>
        </table>
    </div>
    <div class="mp-files-notice">For safety, the editor is limited to text files up to 512 KB. Large files can still be downloaded. Saving reports success only after the mPanel Agent confirms the write.</div>
</div>

<section id="editorCard" class="mp-card mp-editor-card" style="display:none" aria-label="Code editor">
    <div class="mp-editor-header">
        <div class="mp-editor-title">
            <div class="mp-eyebrow">Code Editor</div>
            <strong id="editorName">No file open</strong>
            <div class="mp-editor-subtitle">Monaco Editor · VS Code editing engine</div>
        </div>
        <div class="mp-editor-actions">
            <button class="mp-secondary-btn" type="button" onclick="toggleEditorTheme()" id="editorThemeButton">Light editor</button>
            <button class="mp-secondary-btn" type="button" onclick="toggleEditorFullscreen()" id="fullscreenButton">Fullscreen</button>
            <button class="mp-secondary-btn" type="button" onclick="closeActiveEditor()">Close tab</button>
            <button class="mp-primary-btn" type="button" onclick="saveEditor()" id="saveButton" disabled>Save changes</button>
        </div>
    </div>
    <div class="mp-editor-tabs" id="editorTabs" role="tablist" aria-label="Open files"></div>
    <div class="mp-editor-tools">
        <label for="editorLanguage">Language
            <select id="editorLanguage" onchange="changeEditorLanguage(this.value)">
                <option value="plaintext">Plain text</option>
                <option value="php">PHP</option>
                <option value="html">HTML</option>
                <option value="css">CSS</option>
                <option value="javascript">JavaScript</option>
                <option value="typescript">TypeScript</option>
                <option value="json">JSON</option>
                <option value="xml">XML</option>
                <option value="sql">SQL</option>
                <option value="markdown">Markdown</option>
                <option value="yaml">YAML</option>
                <option value="shell">Shell</option>
                <option value="python">Python</option>
                <option value="ini">INI</option>
            </select>
        </label>
        <span class="mp-editor-meta" id="editorMeta">Open a text file to begin</span>
    </div>
    <div id="editorHost" aria-label="Source code editor"></div>
    <textarea id="editorFallback" spellcheck="false" autocapitalize="off" autocomplete="off" aria-label="Source code editor fallback"></textarea>
    <div class="mp-editor-footer">
        <span id="editorStatus" role="status" aria-live="polite"><span class="mp-status-dot"></span>Waiting for a file</span>
        <span>Ctrl/⌘ + S to save · Maximum 512 KB</span>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    'use strict';

    const LIMIT = 512 * 1024;
    const CDN = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/';
    const websiteId = @json($website->getRouteKey());
    const routes = {
        list: @json(route('websites.files.jobs', $website)),
        listStatus: @json(route('websites.files.jobs.status', [$website, 'JOB'])),
        operation: @json(route('websites.files.operations', $website)),
        operationStatus: @json(route('websites.files.operations.status', [$website, 'JOB'])),
        upload: @json(route('websites.files.upload', $website)),
        uploadStatus: @json(route('websites.files.upload.status', [$website, 'JOB'])),
        download: @json(route('websites.files.download', $website)),
        downloadStatus: @json(route('websites.files.download.status', [$website, 'JOB']))
    };

    let currentPath = '';
    let tabs = [];
    let activePath = null;
    let monacoEditor = null;
    let monacoReady = false;
    let monacoFailed = false;
    let monacoLoading = null;
    let editorTheme = document.documentElement.dataset.theme === 'dark' ? 'vs-dark' : 'vs';
    let fallbackMode = false;
    let isSaving = false;

    const byId = id => document.getElementById(id);
    const token = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const activeTab = () => tabs.find(tab => tab.path === activePath) || null;
    const routeForJob = (template, id) => template.replace('JOB', encodeURIComponent(id));

    async function responseMessage(response) {
        try {
            const body = await response.json();
            return body.message || body.error || body.detail || ('Request failed (' + response.status + ').');
        } catch (_) {
            return 'Request failed (' + response.status + ').';
        }
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token(), 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        if (!response.ok) throw new Error(await responseMessage(response));
        return response.json();
    }

    async function pollJob(url, onProgress) {
        const deadline = Date.now() + 120000;
        while (Date.now() < deadline) {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error(await responseMessage(response));
            const data = await response.json();
            if (onProgress) onProgress(data);
            if (data.status === 'completed') return data;
            if (data.status === 'failed') throw new Error(data.error || 'The mPanel Agent could not complete this operation.');
            await new Promise(resolve => setTimeout(resolve, 700));
        }
        throw new Error('The Agent took too long to respond. Refresh the page or check the server Agent status.');
    }

    async function queueOperation(payload, onProgress) {
        const job = await postJson(routes.operation, payload);
        return pollJob(routeForJob(routes.operationStatus, job.job_id), onProgress);
    }

    async function loadFiles() {
        const status = byId('fileStatus');
        status.textContent = 'Requesting directory contents…';
        try {
            const job = await postJson(routes.list, {path: currentPath});
            const result = await pollJob(routeForJob(routes.listStatus, job.job_id), data => {
                if (data.status === 'pending' || data.status === 'processing') status.textContent = 'mPanel Agent is reading the directory…';
            });
            const entries = result.result?.entries || [];
            renderFiles(entries);
            status.textContent = entries.length + (entries.length === 1 ? ' item' : ' items');
        } catch (error) {
            status.textContent = error.message || 'Unable to read this directory.';
        }
    }

    function renderFiles(entries) {
        const rows = byId('fileRows');
        rows.replaceChildren();
        if (currentPath) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 4;
            const back = document.createElement('button');
            back.type = 'button';
            back.className = 'mp-file-name is-folder';
            back.textContent = '↩  .. Parent directory';
            back.onclick = () => {
                currentPath = currentPath.split('/').slice(0, -1).join('/');
                updatePath();
                loadFiles();
            };
            td.append(back);
            tr.append(td);
            rows.append(tr);
        }

        if (!entries.length && !currentPath) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 4;
            td.textContent = 'This directory is empty.';
            td.style.color = 'var(--muted)';
            tr.append(td);
            rows.append(tr);
        }

        entries.forEach(entry => {
            const tr = document.createElement('tr');
            const nameCell = document.createElement('td');
            const nameButton = document.createElement('button');
            nameButton.type = 'button';
            nameButton.className = 'mp-file-name' + (entry.type === 'directory' ? ' is-folder' : '');
            nameButton.textContent = (entry.type === 'directory' ? '▰  ' : '▤  ') + entry.name;
            nameButton.title = entry.name;
            if (entry.type === 'directory') {
                nameButton.onclick = () => {
                    currentPath = currentPath ? currentPath + '/' + entry.name : entry.name;
                    updatePath();
                    loadFiles();
                };
            } else {
                nameButton.onclick = () => openEditor(entry.name);
            }
            nameCell.append(nameButton);

            const typeCell = document.createElement('td');
            typeCell.textContent = entry.type || 'file';
            const sizeCell = document.createElement('td');
            sizeCell.textContent = entry.type === 'file' ? formatBytes(entry.size) : '—';
            const actionCell = document.createElement('td');
            const actions = document.createElement('div');
            actions.className = 'mp-file-actions';

            if (entry.type === 'file') {
                actions.append(makeButton('Edit', () => openEditor(entry.name), 'mp-secondary-btn'));
                actions.append(makeButton('Download', () => downloadItem(entry.name), 'mp-secondary-btn'));
            }
            actions.append(makeButton('Rename', () => renameItem(entry.name), 'mp-secondary-btn'));
            actions.append(makeButton('Delete', () => deleteItem(entry.name, entry.type), 'mp-secondary-btn'));
            actionCell.append(actions);
            tr.append(nameCell, typeCell, sizeCell, actionCell);
            rows.append(tr);
        });
    }

    function makeButton(label, handler, className) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = label;
        button.onclick = handler;
        return button;
    }

    function updatePath() {
        byId('currentPath').textContent = '/' + currentPath;
    }

    async function createItem(operation) {
        const name = prompt(operation === 'create_folder' ? 'New folder name:' : 'New file name:');
        if (!name) return;
        try {
            byId('fileStatus').textContent = 'Creating ' + name + '…';
            await queueOperation({operation, path: currentPath, name});
            byId('fileStatus').textContent = name + ' created successfully.';
            await loadFiles();
        } catch (error) {
            byId('fileStatus').textContent = error.message;
        }
    }

    async function renameItem(name) {
        const newName = prompt('Rename "' + name + '" to:', name);
        if (!newName || newName === name) return;
        try {
            byId('fileStatus').textContent = 'Renaming…';
            await queueOperation({operation: 'rename', path: currentPath, name, new_name: newName});
            byId('fileStatus').textContent = 'Renamed successfully.';
            await loadFiles();
        } catch (error) {
            byId('fileStatus').textContent = error.message;
        }
    }

    async function deleteItem(name, type) {
        const message = type === 'directory'
            ? 'Delete "' + name + '" and everything inside it? This cannot be undone.'
            : 'Delete "' + name + '"? This cannot be undone.';
        if (!confirm(message)) return;
        try {
            byId('fileStatus').textContent = 'Deleting…';
            await queueOperation({operation: 'delete', path: (currentPath ? currentPath + '/' : '') + name});
            byId('fileStatus').textContent = 'Deleted successfully.';
            await loadFiles();
        } catch (error) {
            byId('fileStatus').textContent = error.message;
        }
    }

    async function uploadFile(file) {
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            byId('fileStatus').textContent = 'Uploads are limited to 5 MB.';
            return;
        }
        const form = new FormData();
        form.append('path', currentPath);
        form.append('file', file);
        byId('fileStatus').textContent = 'Uploading ' + file.name + '…';
        try {
            const response = await fetch(routes.upload, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': token(), 'Accept': 'application/json'},
                body: form
            });
            if (!response.ok) throw new Error(await responseMessage(response));
            const job = await response.json();
            await pollJob(routeForJob(routes.uploadStatus, job.job_id));
            byId('fileStatus').textContent = file.name + ' uploaded successfully.';
            await loadFiles();
        } catch (error) {
            byId('fileStatus').textContent = error.message || 'Upload failed.';
        }
    }

    function languageForPath(path) {
        const ext = (path.split('.').pop() || '').toLowerCase();
        const map = {
            php:'php',phtml:'php',php5:'php',html:'html',htm:'html',xhtml:'html',
            css:'css',scss:'scss',less:'less',js:'javascript',mjs:'javascript',cjs:'javascript',
            jsx:'javascript',ts:'typescript',tsx:'typescript',json:'json',jsonc:'json',
            xml:'xml',svg:'xml',sql:'sql',md:'markdown',markdown:'markdown',
            yml:'yaml',yaml:'yaml',sh:'shell',bash:'shell',py:'python',ini:'ini',
            conf:'ini',env:'ini',txt:'plaintext',log:'plaintext',csv:'plaintext',
            vue:'html',blade:'html'
        };
        if (path.toLowerCase().endsWith('.blade.php')) return 'php';
        return map[ext] || 'plaintext';
    }

    function fileUri(path) {
        return monaco.Uri.parse('inmemory://mpanel/' + encodeURIComponent(String(websiteId)) + '/' + path.split('/').map(encodeURIComponent).join('/'));
    }

    function ensureMonaco() {
        if (monacoReady) return Promise.resolve();
        if (monacoFailed) return Promise.reject(new Error('Monaco Editor could not load. Check this browser’s internet connection; the plain-text fallback is available.'));
        if (monacoLoading) return monacoLoading;

        monacoLoading = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = CDN + 'vs/loader.js';
            script.async = true;
            script.onload = () => {
                try {
                    window.MonacoEnvironment = {
                        getWorkerUrl: function () {
                            const worker = "self.MonacoEnvironment = { baseUrl: '" + CDN + "' }; importScripts('" + CDN + "vs/base/worker/workerMain.js');";
                            return 'data:text/javascript;charset=utf-8,' + encodeURIComponent(worker);
                        }
                    };
                    window.require.config({paths: {vs: CDN + 'vs'}});
                    window.require(['vs/editor/editor.main'], () => {
                        monacoReady = true;
                        resolve();
                    }, error => reject(error instanceof Error ? error : new Error('Monaco modules failed to load.')));
                } catch (error) {
                    reject(error);
                }
            };
            script.onerror = () => reject(new Error('Unable to download Monaco Editor from the CDN.'));
            document.head.append(script);
        }).catch(error => {
            monacoFailed = true;
            monacoLoading = null;
            throw error;
        });
        return monacoLoading;
    }

    function ensureEditor() {
        if (monacoEditor || fallbackMode) return;
        monacoEditor = monaco.editor.create(byId('editorHost'), {
            model: null,
            theme: editorTheme,
            automaticLayout: true,
            minimap: {enabled: false},
            fontSize: 13,
            lineHeight: 21,
            tabSize: 4,
            insertSpaces: true,
            wordWrap: 'off',
            scrollBeyondLastLine: false,
            renderLineHighlight: 'all',
            padding: {top: 12, bottom: 12},
            smoothScrolling: true,
            cursorBlinking: 'smooth',
            readOnly: true,
            ariaLabel: 'mPanel source code editor'
        });
        monacoEditor.onDidChangeModelContent(() => {
            const tab = activeTab();
            if (!tab || tab.readOnly || tab.loading) return;
            tab.content = monacoEditor.getValue();
            tab.dirty = tab.content !== tab.original;
            renderTabs();
            updateEditorStatus(tab.dirty ? 'Unsaved changes' : 'All changes saved', tab.dirty ? 'dirty' : 'ready');
            byId('saveButton').disabled = !tab.dirty || tab.readOnly || isSaving;
            byId('editorMeta').textContent = formatBytes(new TextEncoder().encode(tab.content).length);
        });
    }

    async function openEditor(name) {
        const path = (currentPath ? currentPath + '/' : '') + name;
        const existing = tabs.find(tab => tab.path === path);
        if (existing) {
            activateTab(path);
            return;
        }

        byId('editorCard').style.display = 'block';
        const tab = {path, name, content: '', original: '', dirty: false, loading: true, readOnly: false, model: null, language: languageForPath(path)};
        tabs.push(tab);
        activePath = path;
        renderTabs();
        byId('editorName').textContent = path;
        byId('editorStatus').textContent = 'Loading file…';
        byId('saveButton').disabled = true;
        byId('editorMeta').textContent = 'Loading…';
        byId('editorHost').style.display = 'block';
        byId('editorFallback').style.display = 'none';

        try {
            const job = await postJson(routes.download, {path});
            const response = await pollJob(routeForJob(routes.downloadStatus, job.job_id));
            const encoded = response.result?.content_base64;
            if (typeof encoded !== 'string') throw new Error('The Agent returned no file contents.');
            const raw = atob(encoded);
            const bytes = Uint8Array.from(raw, char => char.charCodeAt(0));
            if (bytes.includes(0)) throw new Error('This appears to be a binary file and cannot be edited as text.');
            const content = new TextDecoder('utf-8', {fatal: true}).decode(bytes);
            tab.content = content;
            tab.original = content;
            tab.readOnly = bytes.length > LIMIT;
            tab.loading = false;
            await activateTab(path);
            if (tab.readOnly) updateEditorStatus('Read-only: files larger than 512 KB cannot be saved here', 'error');
            else updateEditorStatus(monacoReady ? 'Ready' : 'Loading code editor…', 'ready');
        } catch (error) {
            tabs = tabs.filter(item => item.path !== path);
            activePath = tabs.length ? tabs[tabs.length - 1].path : null;
            renderTabs();
            byId('editorStatus').textContent = error.message || 'Unable to open this file.';
            byId('editorMeta').textContent = 'File could not be opened';
            if (activePath) activateTab(activePath);
            else {
                byId('editorName').textContent = 'No file open';
                byId('saveButton').disabled = true;
            }
        }
    }

    async function activateTab(path) {
        const tab = tabs.find(item => item.path === path);
        if (!tab) return;
        activePath = path;
        byId('editorCard').style.display = 'block';
        byId('editorName').textContent = tab.path;
        byId('editorLanguage').value = tab.language;
        byId('saveButton').disabled = tab.loading || tab.readOnly || !tab.dirty || isSaving;
        byId('editorMeta').textContent = tab.loading ? 'Loading…' : formatBytes(new TextEncoder().encode(tab.content).length);
        renderTabs();

        if (tab.loading) return;
        try {
            await ensureMonaco();
            ensureEditor();
            if (!tab.model) {
                tab.model = monaco.editor.createModel(tab.content, tab.language, fileUri(tab.path));
            }
            monacoEditor.setModel(tab.model);
            monacoEditor.updateOptions({readOnly: tab.readOnly});
            byId('editorHost').style.display = 'block';
            byId('editorFallback').style.display = 'none';
            fallbackMode = false;
            monacoEditor.layout();
            updateEditorStatus(tab.readOnly ? 'Read-only: exceeds 512 KB' : (tab.dirty ? 'Unsaved changes' : 'Ready'), tab.readOnly ? 'error' : (tab.dirty ? 'dirty' : 'ready'));
            byId('editorThemeButton').textContent = editorTheme === 'vs-dark' ? 'Light editor' : 'Dark editor';
        } catch (error) {
            fallbackMode = true;
            byId('editorHost').style.display = 'none';
            byId('editorFallback').style.display = 'block';
            byId('editorFallback').value = tab.content;
            byId('editorFallback').readOnly = tab.readOnly;
            byId('editorFallback').oninput = () => {
                tab.content = byId('editorFallback').value;
                tab.dirty = tab.content !== tab.original;
                renderTabs();
                updateEditorStatus(tab.dirty ? 'Unsaved changes' : 'All changes saved', tab.dirty ? 'dirty' : 'ready');
                byId('saveButton').disabled = !tab.dirty || tab.readOnly || isSaving;
                byId('editorMeta').textContent = formatBytes(new TextEncoder().encode(tab.content).length);
            };
            updateEditorStatus('Plain-text fallback: ' + (error.message || 'Monaco unavailable'), 'error');
        }
    }

    function renderTabs() {
        const container = byId('editorTabs');
        container.replaceChildren();
        tabs.forEach(tab => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'mp-editor-tab' + (tab.path === activePath ? ' active' : '');
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-selected', tab.path === activePath ? 'true' : 'false');
            const label = document.createElement('span');
            label.className = 'mp-editor-tab-label';
            label.textContent = tab.name + (tab.loading ? ' …' : '');
            button.append(label);
            if (tab.dirty) {
                const dirty = document.createElement('span');
                dirty.className = 'mp-editor-tab-dirty';
                dirty.textContent = '•';
                dirty.title = 'Unsaved changes';
                button.append(dirty);
            }
            const close = document.createElement('span');
            close.className = 'mp-editor-tab-close';
            close.textContent = '×';
            close.title = 'Close ' + tab.name;
            close.setAttribute('role', 'button');
            close.setAttribute('aria-label', 'Close ' + tab.name);
            close.onclick = event => {
                event.stopPropagation();
                closeTab(tab.path);
            };
            button.append(close);
            button.onclick = () => activateTab(tab.path);
            container.append(button);
        });
    }

    function closeTab(path) {
        const tab = tabs.find(item => item.path === path);
        if (!tab) return;
        if (tab.dirty && !confirm('"' + tab.name + '" has unsaved changes. Close without saving?')) return;
        if (tab.model) tab.model.dispose();
        tabs = tabs.filter(item => item.path !== path);
        if (activePath === path) {
            activePath = tabs.length ? tabs[tabs.length - 1].path : null;
            if (activePath) activateTab(activePath);
            else {
                if (monacoEditor) monacoEditor.setModel(null);
                byId('editorCard').style.display = 'none';
                byId('editorName').textContent = 'No file open';
                byId('saveButton').disabled = true;
            }
        }
        renderTabs();
    }

    function closeActiveEditor() {
        if (activePath) closeTab(activePath);
    }

    async function saveEditor() {
        const tab = activeTab();
        if (!tab || tab.readOnly || tab.loading || isSaving) return;
        const content = monacoEditor && !fallbackMode ? monacoEditor.getValue() : byId('editorFallback').value;
        const bytes = new TextEncoder().encode(content);
        if (bytes.length > LIMIT) {
            updateEditorStatus('Save blocked: file exceeds the 512 KB limit', 'error');
            return;
        }
        isSaving = true;
        byId('saveButton').disabled = true;
        updateEditorStatus('Saving to the hosting server…', 'dirty');
        try {
            const parent = tab.path.split('/').slice(0, -1).join('/');
            const name = tab.path.split('/').pop();
            await queueOperation({operation: 'write_file', path: parent, name, content});
            tab.content = content;
            tab.original = content;
            tab.dirty = false;
            renderTabs();
            updateEditorStatus('Saved successfully · Agent confirmed the write', 'ready');
            byId('fileStatus').textContent = tab.name + ' saved successfully.';
            await loadFiles();
        } catch (error) {
            updateEditorStatus('Save failed: ' + (error.message || 'Unknown error'), 'error');
        } finally {
            isSaving = false;
            byId('saveButton').disabled = !tab.dirty || tab.readOnly;
        }
    }

    function changeEditorLanguage(language) {
        const tab = activeTab();
        if (!tab) return;
        tab.language = language;
        if (tab.model && monacoReady) monaco.editor.setModelLanguage(tab.model, language);
        byId('editorMeta').textContent = language.toUpperCase() + ' · ' + formatBytes(new TextEncoder().encode(tab.content).length);
    }

    function toggleEditorTheme() {
        editorTheme = editorTheme === 'vs-dark' ? 'vs' : 'vs-dark';
        if (monacoReady) monaco.editor.setTheme(editorTheme);
        byId('editorThemeButton').textContent = editorTheme === 'vs-dark' ? 'Light editor' : 'Dark editor';
    }

    function toggleEditorFullscreen() {
        const card = byId('editorCard');
        const full = card.classList.toggle('is-fullscreen');
        byId('fullscreenButton').textContent = full ? 'Exit fullscreen' : 'Fullscreen';
        if (monacoEditor) setTimeout(() => monacoEditor.layout(), 50);
    }

    function updateEditorStatus(message, state) {
        const status = byId('editorStatus');
        status.replaceChildren();
        const dot = document.createElement('span');
        dot.className = 'mp-status-dot ' + (state || '');
        status.append(dot, document.createTextNode(message));
    }

    async function downloadItem(name) {
        const path = (currentPath ? currentPath + '/' : '') + name;
        byId('fileStatus').textContent = 'Preparing download…';
        try {
            const job = await postJson(routes.download, {path});
            const response = await pollJob(routeForJob(routes.downloadStatus, job.job_id));
            const encoded = response.result?.content_base64;
            if (typeof encoded !== 'string') throw new Error('The Agent returned no file contents.');
            const raw = atob(encoded);
            const bytes = Uint8Array.from(raw, char => char.charCodeAt(0));
            const blob = new Blob([bytes], {type: 'application/octet-stream'});
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = response.result?.name || name;
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            byId('fileStatus').textContent = 'Download started for ' + name + '.';
        } catch (error) {
            byId('fileStatus').textContent = error.message || 'Download failed.';
        }
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        if (!Number.isFinite(Number(bytes))) return '—';
        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return (bytes / Math.pow(1024, index)).toFixed(index ? 1 : 0) + ' ' + units[index];
    }

    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            if (activeTab()) {
                event.preventDefault();
                saveEditor();
            }
        }
        if (event.key === 'Escape' && byId('editorCard').classList.contains('is-fullscreen')) {
            toggleEditorFullscreen();
        }
    });

    window.loadFiles = loadFiles;
    window.createItem = createItem;
    window.renameItem = renameItem;
    window.deleteItem = deleteItem;
    window.uploadFile = uploadFile;
    window.openEditor = openEditor;
    window.saveEditor = saveEditor;
    window.closeActiveEditor = closeActiveEditor;
    window.changeEditorLanguage = changeEditorLanguage;
    window.toggleEditorTheme = toggleEditorTheme;
    window.toggleEditorFullscreen = toggleEditorFullscreen;

    loadFiles();
})();
</script>
@endpush
