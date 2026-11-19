<?php

namespace App\Authorization;

use App\Models\User;
use Closure;

final class ManagePrivateNote
{
    public const ABILITY = 'manage-private-note';

    /** @param Closure(int|string): bool $hasCurrentGrant */
    public function __construct(private readonly Closure $hasCurrentGrant) {}

    public function allows(User $user): bool
    {
        $id = $user->getAuthIdentifier();

        return (is_int($id) || (is_string($id) && $id !== ''))
            && ($this->hasCurrentGrant)($id);
    }
}
