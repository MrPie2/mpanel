<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TerminalController extends Controller
{
    public function index(Request $request): View
    {
        $websites = Website::whereHas('server', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('server')
            ->latest()
            ->get();

        return view('terminal.index', compact('websites'));
    }

    public function show(Request $request, Website $website): View
    {
        abort_unless($website->server && $website->server->user_id === $request->user()->id, 403);

        $latestAudit = AgentJob::where('server_id', $website->server_id)
            ->where('type', 'terminal_isolation_audit')
            ->where('payload->website_id', $website->id)
            ->latest()
            ->first();

        return view('terminal.show', compact('website', 'latestAudit'));
    }

    public function audit(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->server && $website->server->user_id === $request->user()->id, 403);

        AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'terminal_isolation_audit',
            'payload' => [
                'website_id' => $website->id,
                'domain' => $website->domain,
            ],
            'status' => 'queued',
        ]);

        return redirect()
            ->route('websites.terminal', $website)
            ->with('status', 'Read-only isolation audit queued. Results will appear here after the server agent processes it.');
    }
}
