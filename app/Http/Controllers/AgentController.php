<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\Server;
use App\Models\Website;
use App\Models\HostingDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Requeue abandoned claims after 30 minutes. This recovers jobs that were
        // claimed just before an agent crash or a temporary control-plane outage.
        AgentJob::where('server_id', $server->id)
            ->where('status', 'claimed')
            ->whereNotNull('claimed_at')
            ->where('claimed_at', '<', now()->subMinutes(30))
            ->update(['status' => 'queued', 'claimed_at' => null]);

        $jobs = AgentJob::where('server_id', $server->id)
            ->where('status', 'queued')
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

        return DB::transaction(function () use ($job, $server, $data): JsonResponse {
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

            if ($job->type === 'terminal_isolation_audit' && $data['status'] === 'completed') {
                $website = Website::find($job->payload['website_id'] ?? null);
                $audit = $data['result'] ?? [];

                // Treat agent output as untrusted input. A successful audit job is not
                // sufficient: require the complete read-only report and all expected checks.
                $requiredChecks = [
                    'canonical_document_root',
                    'dedicated_unprivileged_linux_account',
                    'document_root_ownership',
                    'dedicated_php_fpm_pool',
                    'nginx_uses_site_php_socket',
                    'terminal_gateway_and_sandbox',
                ];
                $checks = $audit['checks'] ?? null;
                $checkNames = is_array($checks)
                    ? array_column(array_filter($checks, fn ($check) => is_array($check)), 'name')
                    : [];
                $checksAreWellFormed = is_array($checks)
                    && count($checks) === count($requiredChecks)
                    && count(array_unique($checkNames)) === count($requiredChecks)
                    && empty(array_diff($requiredChecks, $checkNames))
                    && collect($checks)->every(fn ($check) =>
                        is_array($check)
                        && is_string($check['name'] ?? null)
                        && is_bool($check['passed'] ?? null)
                        && is_string($check['detail'] ?? null)
                    );
                $gatewayCheck = is_array($checks)
                    ? collect($checks)->firstWhere('name', 'terminal_gateway_and_sandbox')
                    : null;

                // This branch must never promote a website to ready. The gateway
                // check is required to fail until a sandboxed PTY implementation exists.
                if (
                    $website &&
                    $website->server_id === $server->id &&
                    (int) ($audit['website_id'] ?? 0) === (int) $website->id &&
                    ($audit['domain'] ?? null) === $website->domain &&
                    ($audit['status'] ?? null) === 'blocked' &&
                    ($audit['ready'] ?? true) === false &&
                    ($audit['read_only'] ?? false) === true &&
                    $checksAreWellFormed &&
                    is_array($gatewayCheck) &&
                    ($gatewayCheck['passed'] ?? true) === false
                ) {
                    $linuxUser = $audit['linux_user'] ?? null;
                    $website->update([
                        'terminal_linux_user' => is_string($linuxUser) && preg_match('/^mpw[0-9a-z]+$/', $linuxUser)
                            ? $linuxUser
                            : null,
                        'terminal_isolation_status' => 'blocked',
                        'terminal_ready_at' => null,
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
        });
    }

}
