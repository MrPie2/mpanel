<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    public function heartbeat(Request $request, Server $server): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token || ! $server->agent_token_hash || ! Hash::check($token, $server->agent_token_hash)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'cpu_percent' => ['required','numeric','between:0,100'],
            'memory_percent' => ['required','numeric','between:0,100'],
            'disk_percent' => ['required','numeric','between:0,100'],
        ]);

        $server->update([...$data, 'status'=>'online', 'last_seen_at'=>now()]);

        return response()->json([
            'status'=>'ok',
            'server_id'=>$server->id,
            'received_at'=>now()->toIso8601String(),
        ]);
    }
}
