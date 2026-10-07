<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\Server;
use App\Models\Website;
use App\Models\HostingDatabase;
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
    public function jobs(Request $request): JsonResponse
    {
        $server=$request->attributes->get('agent_server');

        $jobs=AgentJob::where('server_id',$server->id)
            ->where('status','queued')
            ->orderBy('id')
            ->limit(10)
            ->get();

        $jobs->each(fn (AgentJob $job) => $job->update([
            'status'=>'claimed',
            'claimed_at'=>now(),
        ]));

        return response()->json([
            'jobs'=>$jobs->map(fn ($job)=>[
                'id'=>$job->id,
                'type'=>$job->type,
                'payload'=>$job->payload,
            ]),
        ]);
    }

    public function completeJob(Request $request, AgentJob $job): JsonResponse
    {
        $server=$request->attributes->get('agent_server');

        if (!$server || $job->server_id !== $server->id) {
            return response()->json(['message'=>'Forbidden.'],403);
        }

        $data=$request->validate([
            'status'=>['required','in:completed,failed'],
            'result'=>['nullable','array'],
            'error'=>['nullable','string','max:5000'],
        ]);

        $job->update([
            'status'=>$data['status'],
            'result'=>$data['result'] ?? null,
            'error'=>$data['error'] ?? null,
            'completed_at'=>now(),
        ]);

        if (in_array($job->type, ['create_database','delete_database'], true) && !empty($job->payload['database_id'])) {
            $database=HostingDatabase::find($job->payload['database_id']);

            if ($database && $database->server_id === $server->id) {
                $database->update([
                    'status'=>$data['status'] === 'completed'
                        ? ($job->type === 'delete_database' ? 'deleted' : 'active')
                        : 'failed',
                    'last_error'=>$data['status'] === 'failed' ? $data['error'] : null,
                ]);
            }
        }

        if ($job->type === 'create_site' && !empty($job->payload['website_id'])) {
            $website=Website::find($job->payload['website_id']);

            if ($website && $website->server_id === $server->id) {
                $website->update([
                    'status'=>$data['status'] === 'completed' ? 'active' : 'failed',
                ]);
            }
        }

        return response()->json(['status'=>'ok']);
    }

}
