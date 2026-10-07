<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function pair(Request $request): JsonResponse
    {
        $data=$request->validate([
            'server_id'=>['required','integer','exists:servers,id'],
            'pairing_token'=>['required','string'],
        ]);

        $server=Server::findOrFail($data['server_id']);

        if (!$server->pairing_token_hash || !$server->pairing_token_expires_at ||
            $server->pairing_token_expires_at->isPast() ||
            !Hash::check($data['pairing_token'],$server->pairing_token_hash)) {
            return response()->json(['message'=>'Invalid or expired pairing token.'],401);
        }

        $agentToken=Str::random(96);
        $server->update([
            'agent_token_hash'=>Hash::make($agentToken),
            'pairing_token_hash'=>null,
            'pairing_token_expires_at'=>null,
            'agent_paired_at'=>now(),
            'status'=>'online',
            'last_seen_at'=>now(),
        ]);

        return response()->json([
            'status'=>'paired',
            'server_id'=>$server->id,
            'agent_token'=>$agentToken,
        ]);
    }

    public function heartbeat(Request $request, Server $server): JsonResponse
    {
        $token=$request->bearerToken();

        if (!$token || !$server->agent_token_hash || !Hash::check($token,$server->agent_token_hash)) {
            return response()->json(['message'=>'Unauthenticated.'],401);
        }

        $data=$request->validate([
            'cpu_percent'=>['required','numeric','between:0,100'],
            'memory_percent'=>['required','numeric','between:0,100'],
            'disk_percent'=>['required','numeric','between:0,100'],
        ]);

        $server->update([...$data,'status'=>'online','last_seen_at'=>now()]);

        return response()->json(['status'=>'ok','server_id'=>$server->id,'received_at'=>now()->toIso8601String()]);
    }
}
