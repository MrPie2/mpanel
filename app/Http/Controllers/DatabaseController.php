<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\HostingDatabase;
use App\Models\Server;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DatabaseController extends Controller
{
    public function index(Request $request): View
    {
        $databases = HostingDatabase::where('user_id', $request->user()->id)
            ->with(['server','website'])
            ->latest()
            ->paginate(12);

        return view('databases.index', compact('databases'));
    }

    public function create(Request $request): View
    {
        $websites = Website::whereHas('server', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('server')->orderBy('domain')->get();

        $servers = Server::where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('databases.create', compact('websites','servers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'server_id' => ['required','integer','exists:servers,id'],
            'website_id' => ['nullable','integer','exists:websites,id'],
            'name' => ['required','string','max:32','regex:/^[A-Za-z][A-Za-z0-9_]+$/'],
            'username' => ['required','string','max:16','regex:/^[A-Za-z][A-Za-z0-9_]+$/'],
            'password' => ['required','string','min:12','max:128'],
        ]);

        $server = Server::where('id',$data['server_id'])->where('user_id',$request->user()->id)->firstOrFail();
        $website = null;

        if (!empty($data['website_id'])) {
            $website = Website::where('id',$data['website_id'])
                ->whereHas('server', fn ($q) => $q->where('user_id',$request->user()->id))
                ->firstOrFail();

            abort_unless($website->server_id === $server->id, 422);
        }

        $dbName = 'mp_'.$request->user()->id.'_'.$data['name'];
        $dbUser = 'mp_'.$request->user()->id.'_'.$data['username'];

        if (strlen($dbName) > 64 || strlen($dbUser) > 32) {
            return back()->withErrors(['name' => 'The generated database name or username is too long.'])->withInput();
        }

        $database = HostingDatabase::create([
            'server_id'=>$server->id,
            'website_id'=>$website?->id,
            'user_id'=>$request->user()->id,
            'name'=>$dbName,
            'username'=>$dbUser,
            'password_encrypted'=>Crypt::encryptString($data['password']),
            'status'=>'provisioning',
        ]);

        AgentJob::create([
            'server_id'=>$server->id,
            'type'=>'create_database',
            'payload'=>[
                'database_id'=>$database->id,
                'database_name'=>$dbName,
                'username'=>$dbUser,
                'password'=>$data['password'],
            ],
        ]);

        return redirect()->route('databases.show',$database)
            ->with('success','Database provisioning job queued.');
    }

    public function show(Request $request, HostingDatabase $database): View
    {
        $this->authorizeDatabase($request,$database);
        return view('databases.show', compact('database'));
    }

    public function destroy(Request $request, HostingDatabase $database): RedirectResponse
    {
        $this->authorizeDatabase($request,$database);

        AgentJob::create([
            'server_id'=>$database->server_id,
            'type'=>'delete_database',
            'payload'=>[
                'database_id'=>$database->id,
                'database_name'=>$database->name,
                'username'=>$database->username,
            ],
        ]);

        $database->update(['status'=>'deleting']);
        return redirect()->route('databases.index')->with('success','Database deletion queued.');
    }

    private function authorizeDatabase(Request $request, HostingDatabase $database): void
    {
        abort_unless($database->user_id === $request->user()->id,403);
    }
}
