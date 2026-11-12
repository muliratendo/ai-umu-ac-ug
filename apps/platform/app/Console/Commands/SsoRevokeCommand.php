<?php

namespace App\Console\Commands;

use App\Models\SsoInvitation;
use App\Support\GoogleSso;
use Illuminate\Console\Command;

class SsoRevokeCommand extends Command
{
    protected $signature = 'sso:revoke {email}';
    protected $description = 'Revoke Google sign-in access for one exact email address';

    public function handle(): int
    {
        $email = GoogleSso::normalizeEmail((string) $this->argument('email'));
        $invitation = $email === null ? null : SsoInvitation::query()->where('email', $email)->first();

        if ($invitation === null) {
            $this->error('Approval not found.');
            return self::FAILURE;
        }

        $invitation->forceFill(['revoked_at' => now()])->save();
        $this->info('Approval revoked.');
        return self::SUCCESS;
    }
}