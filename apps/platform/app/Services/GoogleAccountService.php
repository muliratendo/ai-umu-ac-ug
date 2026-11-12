<?php

namespace App\Services;

use App\Exceptions\SsoAccessDenied;
use App\Models\GoogleIdentity;
use App\Models\SsoInvitation;
use App\Models\User;
use App\Support\GoogleSso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAccountService
{
    public function authenticate(SocialiteUser $providerUser): User
    {
        $raw = $providerUser->getRaw();
        $subject = trim((string) $providerUser->getId());
        $email = GoogleSso::normalizeEmail((string) $providerUser->getEmail());

        if ($subject === '' || $email === null || ($raw['email_verified'] ?? false) !== true) {
            throw new SsoAccessDenied;
        }

        try {
            return DB::transaction(function () use ($providerUser, $subject, $email): User {
                $identity = GoogleIdentity::query()
                    ->where('provider', 'google')
                    ->where('subject', $subject)
                    ->lockForUpdate()
                    ->first();

                if ($identity !== null) {
                    $invitation = SsoInvitation::query()->where('redeemed_by', $identity->user_id)->lockForUpdate()->first();

                    if ($invitation === null || ! $invitation->isActive()) {
                        throw new SsoAccessDenied;
                    }

                    return $identity->user;
                }

                $invitation = SsoInvitation::query()->where('email', $email)->lockForUpdate()->first();

                if ($invitation === null || ! $invitation->isActive() || $invitation->redeemed_at !== null) {
                    throw new SsoAccessDenied;
                }

                if (User::query()->where('email', $email)->exists()) {
                    throw new SsoAccessDenied;
                }

                $user = User::query()->create([
                    'name' => trim((string) $providerUser->getName()) ?: 'UMU AI Community member',
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(64)),
                ]);

                GoogleIdentity::query()->create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'subject' => $subject,
                    'email' => $email,
                ]);

                $invitation->update(['redeemed_at' => now(), 'redeemed_by' => $user->id]);

                return $user;
            }, 3);
        } catch (SsoAccessDenied $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw new SsoAccessDenied;
        }
    }

    public function canAccess(User $user): bool
    {
        $identity = GoogleIdentity::query()->where('user_id', $user->id)->where('provider', 'google')->first();
        $invitation = SsoInvitation::query()->where('redeemed_by', $user->id)->first();

        return $identity !== null && $invitation !== null && $invitation->isActive();
    }
}