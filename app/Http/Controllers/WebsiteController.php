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
        $websites = Website::whereHas('server', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('server')
            ->latest()
            ->paginate(12);

        return view('websites.index', compact('websites'));
    }

    public function show(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);
        return view('websites.show', compact('website'));
    }

    public function ssl(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);
        return view('websites.ssl', compact('website'));
    }

    public function sslIssue(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($website->ssl_enabled && $website->ssl_expires_at?->isFuture()) {
            return response()->json(['message' => 'SSL is already active for this website.'], 422);
        }

        $job = AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'issue_ssl',
            'payload' => [
                'website_id' => $website->id,
                'domain' => $website->domain,
                'document_root' => $website->document_root,
                'php_version' => $website->php_version,
                'email' => $data['email'],
            ],
        ]);

        return response()->json(['job_id' => $job->id, 'status' => $job->status], 202);
    }

    public function sslIssueStatus(Request $request, Website $website, AgentJob $job): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($job->server_id === $website->server_id && (($job->payload['website_id'] ?? null) === $website->id), 404);

        if ($job->status === 'completed' && ($job->result['ssl_enabled'] ?? false)) {
            $website->update([
                'ssl_enabled' => true,
                'ssl_issued_at' => isset($job->result['issued_at']) ? now()->parse($job->result['issued_at']) : now(),
                'ssl_expires_at' => isset($job->result['expires_at']) ? now()->parse($job->result['expires_at']) : null,
            ]);
        } elseif ($job->status === 'failed' && $website->ssl_enabled && !$website->ssl_expires_at?->isFuture()) {
            $website->update(['ssl_enabled' => false]);
        }

        return response()->json(['status' => $job->status, 'result' => $job->result, 'error' => $job->error]);
    }

    public function sslDisable(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);

        $job = AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'disable_ssl',
            'payload' => [
                'website_id' => $website->id,
                'domain' => $website->domain,
                'document_root' => $website->document_root,
            ],
        ]);

        return response()->json(['job_id' => $job->id, 'status' => $job->status], 202);
    }

    public function sslDisableStatus(Request $request, Website $website, AgentJob $job): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($job->server_id === $website->server_id && (($job->payload['website_id'] ?? null) === $website->id), 404);

        if ($job->status === 'completed') {
            $website->update(['ssl_enabled' => false, 'ssl_expires_at' => null]);
        }

        return response()->json(['status' => $job->status, 'result' => $job->result, 'error' => $job->error]);
    }

    public function files(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);
        return view('websites.files', compact('website'));
    }

    public function filesJob(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:1000'],
        ]);
        $path = trim((string) ($data['path'] ?? ''), '/');
        $this->rejectTraversal($path);

        $job = AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'list_files',
            'payload' => ['website_id' => $website->id, 'document_root' => $website->document_root, 'path' => $path],
        ]);

        return response()->json(['job_id' => $job->id, 'status' => $job->status], 202);
    }

    public function filesJobStatus(Request $request, Website $website, AgentJob $job): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($job->server_id === $website->server_id && (($job->payload['website_id'] ?? null) === $website->id), 404);

        return response()->json([
            'status' => $job->status,
            'result' => $job->result,
            'error' => $job->error,
        ]);
    }

    public function fileOperation(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'operation' => ['required', 'string', 'in:create_folder,create_file,rename,delete,write_file'],
            'path' => ['nullable', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:255'],
            'new_name' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:524288'],
        ]);

        $path = trim((string) ($data['path'] ?? ''), '/');
        $this->rejectTraversal($path);

        foreach (['name', 'new_name'] as $field) {
            if (isset($data[$field])) {
                $this->validateFileName($data[$field]);
            }
        }

        if ($data['operation'] === 'write_file' && !array_key_exists('content', $data)) {
            abort(422, 'File content is required.');
        }

        $job = AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'file_operation',
            'payload' => [
                'website_id' => $website->id,
                'document_root' => $website->document_root,
                ...$data,
                'path' => $path,
            ],
        ]);

        return response()->json(['job_id' => $job->id, 'status' => $job->status], 202);
    }

    public function fileOperationStatus(Request $request, Website $website, AgentJob $job): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($job->server_id === $website->server_id && (($job->payload['website_id'] ?? null) === $website->id), 404);

        return response()->json([
            'status' => $job->status,
            'result' => $job->result,
            'error' => $job->error,
        ]);
    }

    public function upload(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $path = trim((string) ($data['path'] ?? ''), '/');
        $this->rejectTraversal($path);

        $name = $data['file']->getClientOriginalName();
        $this->validateFileName($name);

        $contents = file_get_contents($data['file']->getRealPath());
        $job = AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'upload_file',
            'payload' => [
                'website_id' => $website->id,
                'document_root' => $website->document_root,
                'path' => $path,
                'name' => $name,
                'content_base64' => base64_encode($contents),
            ],
        ]);

        return response()->json(['job_id' => $job->id, 'status' => $job->status], 202);
    }

    public function uploadStatus(Request $request, Website $website, AgentJob $job): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($job->server_id === $website->server_id && (($job->payload['website_id'] ?? null) === $website->id), 404);

        return response()->json([
            'status' => $job->status,
            'result' => $job->result,
            'error' => $job->error,
        ]);
    }

    public function download(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'path' => ['required', 'string', 'max:1000'],
        ]);
        $path = trim($data['path'], '/');
        $this->rejectTraversal($path);

        $job = AgentJob::create([
            'server_id' => $website->server_id,
            'type' => 'download_file',
            'payload' => ['website_id' => $website->id, 'document_root' => $website->document_root, 'path' => $path],
        ]);

        return response()->json(['job_id' => $job->id, 'status' => $job->status], 202);
    }

    public function downloadStatus(Request $request, Website $website, AgentJob $job): JsonResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($job->server_id === $website->server_id && (($job->payload['website_id'] ?? null) === $website->id), 404);

        return response()->json([
            'status' => $job->status,
            'result' => $job->result,
            'error' => $job->error,
        ]);
    }

    private function authorizeWebsite(Request $request, Website $website): void
    {
        abort_unless($website->server && $website->server->user_id === $request->user()->id, 403);
    }

    private function rejectTraversal(string $path): void
    {
        if ($path === '.' || Str::contains($path, ['..\\', '../', '\\..'])) {
            abort(422, 'Invalid path.');
        }
    }

    private function validateFileName(string $name): void
    {
        if ($name === '' || $name === '.' || $name === '..' || Str::contains($name, ['/', '\\'])) {
            abort(422, 'Invalid file name.');
        }
    }
}
