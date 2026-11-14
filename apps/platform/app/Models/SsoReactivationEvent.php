<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A minimal durable record that a revoked invitation was reactivated by an
 * operator.  It preserves the cleared revocation timestamp so prior revocation
 * history remains visible after reactivation.
 */
class SsoReactivationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['sso_invitation_id', 'prior_revoked_at', 'reason'];

    protected function casts(): array
    {
        return ['prior_revoked_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(SsoInvitation::class, 'sso_invitation_id');
    }
}
