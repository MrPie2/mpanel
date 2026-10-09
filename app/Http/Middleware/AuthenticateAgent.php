<?php

namespace App\Http\Middleware;

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

        if ($routeServer instanceof Server) {
            $server = $routeServer;
        } else {
            $serverId = is_numeric($routeServer)
                ? $routeServer
                : $request->header('X-mPanel-Server');

            $server = $serverId ? Server::find($serverId) : null;
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
