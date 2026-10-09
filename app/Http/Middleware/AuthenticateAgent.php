<?php

namespace App\Http\Middleware;

use App\Models\AgentJob;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeServer = $request->route('server');
        $server = null;

        if ($routeServer instanceof Server) {
            $server = $routeServer;
        } else {
            $serverId = is_numeric($routeServer)
                ? (int) $routeServer
                : $request->header('X-mPanel-Server');

            if ($serverId) {
                $server = Server::find($serverId);
            }
        }

        // Job completion routes contain {job}, not {server}.
        // Resolve the owning server from the job when no server was found above.
        if (!$server) {
            $routeJob = $request->route('job');

            if ($routeJob instanceof AgentJob) {
                $server = Server::find($routeJob->server_id);
            } elseif (is_numeric($routeJob)) {
                $job = AgentJob::find((int) $routeJob);
                $server = $job ? Server::find($job->server_id) : null;
            }
        }

        $token = $request->bearerToken();

        if (
            !$server ||
            !$token ||
            !$server->agent_token_hash ||
            !Hash::check($token, $server->agent_token_hash)
        ) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->attributes->set('agent_server', $server);

        $server->forceFill([
            'last_seen_at' => now(),
            'status' => 'online',
        ])->save();

        return $next($request);
    }
}
