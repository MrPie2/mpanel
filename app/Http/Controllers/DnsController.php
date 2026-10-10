<?php

namespace App\Http\Controllers;

use App\Models\AgentJob;
use App\Models\DnsRecord;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DnsController extends Controller
{
    public function index(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);
        $records = $website->dnsRecords()->orderBy('type')->orderBy('name')->get();
        return view('dns.index', compact('website','records'));
    }

    public function store(Request $request, Website $website): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'type' => ['required','in:A,AAAA,CNAME,MX,TXT,NS,CAA,SRV,SVCB,HTTPS'],
            'name' => ['required','string','max:253'],
            'value' => ['required','string','max:2000','not_regex:/[\r\n]/'],
            'ttl' => ['required','integer','between:60,86400'],
            'priority' => [in_array($request->input('type'), ['MX','SRV'], true) ? 'required' : 'nullable','integer','between:0,65535'],
        ]);

        $record = DnsRecord::create(['website_id'=>$website->id,...$data]);

        AgentJob::create([
            'server_id'=>$website->server_id,
            'type'=>'dns_upsert',
            'payload'=>[
                'website_id'=>$website->id,
                'domain'=>$website->domain,
                'record_id'=>$record->id,
                ...$data,
            ],
        ]);

        return back()->with('success','DNS record queued for the mPanel Agent.');
    }

    public function destroy(Request $request, Website $website, DnsRecord $record): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($record->website_id === $website->id,404);

        AgentJob::create([
            'server_id'=>$website->server_id,
            'type'=>'dns_delete',
            'payload'=>[
                'website_id'=>$website->id,
                'domain'=>$website->domain,
                'record_id'=>$record->id,
                'type'=>$record->type,
                'name'=>$record->name,
                'value'=>$record->value,
                'ttl'=>$record->ttl,
                'priority'=>$record->priority,
            ],
        ]);

        $record->delete();
        return back()->with('success','DNS record deletion queued.');
    }

    private function authorizeWebsite(Request $request, Website $website): void
    {
        abort_unless($website->server && $website->server->user_id === $request->user()->id,403);
    }
}
