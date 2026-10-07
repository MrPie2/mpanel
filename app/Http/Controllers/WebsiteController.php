<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function index(Request $request): View
    {
        $websites=Website::whereHas('server', fn ($q) => $q->where('user_id',$request->user()->id))
            ->with('server')
            ->latest()
            ->paginate(12);

        return view('websites.index', compact('websites'));
    }

    public function show(Request $request, Website $website): View
    {
        abort_unless($website->server && $website->server->user_id === $request->user()->id, 403);

        return view('websites.show', compact('website'));
    }
}
