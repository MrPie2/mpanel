<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    protected $fillable = ['website_id','type','name','value','ttl','priority'];

    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
}
