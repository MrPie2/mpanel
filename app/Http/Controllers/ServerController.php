<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\Server;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(Request $request): View
    {
        return view('servers.index', ['servers'=>$request->user()->servers()->latest()->paginate(12)]);
    }

    public function create(): View { return view('servers.create'); }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'name'=>['required','string','max:120'],
            'hostname'=>['nullable','string','max:255'],
            'ip_address'=>['required','ip'],
            'port'=>['required','integer','between:1,65535'],
            'notes'=>['nullable','string','max:2000'],
        ]);

        $plainToken=Str::random(64);
        $server=Server::create([...$data,'status'=>'pending','agent_token_hash'=>null,'pairing_token_hash'=>Hash::make($plainToken),'pairing_token_expires_at'=>now()->addMinutes(15),'user_id'=>$request->user()->id]);

        return redirect()->route('servers.show',$server)->with('agent_token',$plainToken)->with('success','Server added. Copy the pairing token now and install the mPanel Agent.');
    }

    public function show(Request $request, Server $server): View
    {
        abort_unless($server->user_id === $request->user()->id, 403);
        return view('servers.show', compact('server'));
    }

    public function destroy(Request $request, Server $server): RedirectResponse
    {
        abort_unless($server->user_id === $request->user()->id, 403);
        $server->delete();
        return redirect()->route('servers.index')->with('success','Server removed from mPanel.');
    }
    public function websiteCreate(Request $request, Server $server): RedirectResponse
    {
        abort_unless($server->user_id === $request->user()->id, 403);

        $data=$request->validate([
            'domain'=>['required','string','max:253','regex:/^(?=.{1,253}$)(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,63}$/'],
            'php_version'=>['required','string','in:8.2,8.3,8.4'],
        ]);

        $domain=strtolower($data['domain']);
        $documentRoot='/var/www/'.$domain;

        $website=Website::create([
            'server_id'=>$server->id,
            'domain'=>$domain,
            'document_root'=>$documentRoot,
            'php_version'=>$data['php_version'],
            'status'=>'provisioning',
        ]);

        AgentJob::create([
            'server_id'=>$server->id,
            'type'=>'create_site',
            'payload'=>[
                'website_id'=>$website->id,
                'domain'=>$domain,
                'document_root'=>$documentRoot,
                'php_version'=>$data['php_version'],
            ],
        ]);

        return redirect()->route('servers.show',$server)->with('success','Website provisioning job queued.');
    }

}
