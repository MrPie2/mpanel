<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\GitDeployment;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GitController extends Controller
{
    public function index(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);
        $git = $website->gitDeployment;
        return view('websites.git', compact('website', 'git'));
    }

    public function connect(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'repository_url' => ['required', 'url', 'max:500'],
            'branch' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\/-]+$/'],
        ]);

        $url = trim($data['repository_url']);
        if (!preg_match('#^https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+(?:\.git)?$#', $url)) {
            return response()->json(['message' => 'For the first Git release, use a public GitHub HTTPS repository URL.'], 422);
        }

        $secret = Str::random(48);
        $git = GitDeployment::updateOrCreate(
            ['website_id' => $website->id],
            [
                'repository_url' => $url,
                'branch' => $data['branch'],
                'deploy_path' => $website->document_root,
                'webhook_secret_hash' => Hash::make($secret),
                'status' => 'connected',
                'last_error' => null,
            ]
        );

        return response()->json([
            'message' => 'Repository connected.',
            'webhook_secret' => $secret,
            'webhook_url' => route('webhooks.github', $website),
            'status' => $git->status,
        ]);
    }

    public function deploy(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        $git = $website->gitDeployment;
        abort_unless($git, 422, 'Connect a GitHub repository first.');

        $git->update(['status' => 'deploying', 'last_error' => null]);

        $job = AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'git_deploy',
            'payload' => [
                'website_id' => $website->id,
                'domain' => $website->domain,
                'repository_url' => $git->repository_url,
                'branch' => $git->branch,
                'deploy_path' => $git->deploy_path,
            ],
        ]);

        return response()->json(['job_id' => $job->id, 'status' => $job->status], 202);
    }

    public function status(Request $request, Website $website, AgentJob $job): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($job->server_id === $website->server_id && (($job->payload['website_id'] ?? null) === $website->id) && $job->type === 'git_deploy', 404);

        $git = $website->gitDeployment;
        if ($git && $job->status === 'completed') {
            $git->update([
                'status' => 'deployed',
                'last_commit' => $job->result['commit'] ?? null,
                'last_deployed_at' => now(),
                'last_error' => null,
            ]);
        } elseif ($git && $job->status === 'failed') {
            $git->update(['status' => 'failed', 'last_error' => $job->error]);
        }

        return response()->json(['status' => $job->status, 'result' => $job->result, 'error' => $job->error]);
    }

    public function disconnect(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        $website->gitDeployment?->delete();
        return response()->json(['message' => 'Git integration disconnected.']);
    }

    public function webhook(Request $request, Website $website): JsonResponse
    {
        $git = $website->gitDeployment;
        abort_unless($git && $git->webhook_secret_hash, 404);

        $signature = $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $this->webhookSecretPlaceholder($git));
        if (!hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $payload = $request->json()->all();
        $ref = (string) ($payload['ref'] ?? '');
        if ($ref !== 'refs/heads/'.$git->branch) {
            return response()->json(['message' => 'Ignored branch.'], 202);
        }

        $git->update(['status' => 'deploying', 'last_error' => null]);
        AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'git_deploy',
            'payload' => [
                'website_id' => $website->id,
                'domain' => $website->domain,
                'repository_url' => $git->repository_url,
                'branch' => $git->branch,
                'deploy_path' => $git->deploy_path,
            ],
        ]);

        return response()->json(['message' => 'Deployment queued.'], 202);
    }

    private function webhookSecretPlaceholder(GitDeployment $git): string
    {
        abort(500, 'Webhook secret verification requires encrypted secret storage.');
    }

    private function authorizeWebsite(Request $request, Website $website): void
    {
        abort_unless($website->server && $website->server->user_id === $request->user()->id, 403);
    }
}