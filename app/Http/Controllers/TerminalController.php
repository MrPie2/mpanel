<?php

namespace App\\Http\\Controllers;

use App\\Models\\Website;
use Illuminate\\Http\\Request;
use Illuminate\\View\\View;

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

        return view('terminal.show', compact('website'));
    }
}
