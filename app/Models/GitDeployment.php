<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitDeployment extends Model
{
    protected $fillable = [
        'website_id','repository_url','branch','deploy_path',
        'webhook_secret_hash','webhook_secret_encrypted','status','last_commit','last_deployed_at','last_error',
    ];

    protected function casts(): array
    {
        return ['last_deployed_at' => 'datetime'];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}