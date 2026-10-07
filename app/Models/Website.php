<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $fillable=['server_id','domain','document_root','php_version','status','ssl_enabled','ssl_issued_at','ssl_expires_at'];

    protected function casts(): array
    {
        return [
            'ssl_enabled' => 'boolean',
            'ssl_issued_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo { return $this->belongsTo(Server::class); }
    public function dnsRecords(): HasMany { return $this->hasMany(DnsRecord::class); }
    public function gitDeployment(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(GitDeployment::class); }
}