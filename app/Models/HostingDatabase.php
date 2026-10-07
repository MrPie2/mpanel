<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingDatabase extends Model
{
    protected $table = 'databases';

    protected $fillable = [
        'server_id','website_id','user_id','name','username',
        'password_encrypted','host','port','status','last_error',
    ];

    public function server(): BelongsTo { return $this->belongsTo(Server::class); }
    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
