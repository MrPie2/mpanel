<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    protected $fillable = ['name','hostname','ip_address','port','agent_token_hash','status','last_seen_at','cpu_percent','memory_percent','disk_percent','notes','user_id'];

    protected function casts(): array
    {
        return ['last_seen_at'=>'datetime','cpu_percent'=>'decimal:2','memory_percent'=>'decimal:2','disk_percent'=>'decimal:2'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
