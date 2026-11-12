<?php

namespace App\Models;

use App\Support\GoogleSso;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SsoInvitation extends Model
{
    protected $fillable = ['email', 'approved_at', 'revoked_at', 'redeemed_at', 'redeemed_by'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'revoked_at' => 'datetime', 'redeemed_at' => 'datetime'];
    }

    public static function forEmail(string $email): ?self
    {
        $normalized = GoogleSso::normalizeEmail($email);
        return $normalized === null ? null : static::query()->where('email', $normalized)->first();
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function isActive(): bool
    {
        return $this->approved_at !== null && $this->revoked_at === null;
    }
}