<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Website extends Model
{
    protected $fillable=['server_id','domain','document_root','php_version','status'];

    public function server(): BelongsTo { return $this->belongsTo(Server::class); }
}
