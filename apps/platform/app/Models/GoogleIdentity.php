<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class GoogleIdentity extends Model
{
    protected $fillable = ['user_id', 'provider', 'subject', 'email'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}