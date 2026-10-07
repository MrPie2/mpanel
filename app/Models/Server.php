<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = ['name','hostname','ip_address','port','agent_token_hash','pairing_token_hash','pairing_token_expires_at','agent_paired_at','status','last_seen_at','cpu_percent','memory_percent','disk_percent','notes','user_id'];

    protected function casts(): array
    {
        return ['last_seen_at'=>'datetime','pairing_token_expires_at'=>'datetime','agent_paired_at'=>'datetime','cpu_percent'=>'decimal:2','memory_percent'=>'decimal:2','disk_percent'=>'decimal:2'];
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
