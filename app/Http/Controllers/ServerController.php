<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(): View
    {
        return view('servers.index', ['servers' => Server::latest()->paginate(12)]);
    }

    public function create(): View { return view('servers.create'); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'=>['required','string','max:120'],
            'hostname'=>['nullable','string','max:255'],
            'ip_address'=>['required','ip'],
            'port'=>['required','integer','between:1,65535'],
            'notes'=>['nullable','string','max:2000'],
        ]);

        $server = Server::create([...$data, 'status'=>'pending', 'user_id'=>$request->user()->id]);

        return redirect()->route('servers.show',$server)->with('success','Server added. Install the mPanel Agent to complete the connection.');
    }

    public function show(Server $server): View { return view('servers.show', compact('server')); }

    public function destroy(Server $server): RedirectResponse
    {
        $server->delete();
        return redirect()->route('servers.index')->with('success','Server removed from mPanel.');
    }
}
