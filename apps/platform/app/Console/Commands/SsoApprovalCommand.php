<?php

namespace App\Console\Commands;

use App\Models\SsoInvitation;
use App\Support\GoogleSso;
use Illuminate\Console\Command;

class SsoApprovalCommand extends Command
{
    protected $signature = 'sso:approve {email}';
    protected $description = 'Approve one exact email address for Google sign-in';

    public function handle(): int
    {
        $email = GoogleSso::normalizeEmail((string) $this->argument('email'));

        if ($email === null) {
            $this->error('Provide a valid email address.');
            return self::FAILURE;
        }

        $invitation = SsoInvitation::query()->firstOrNew(['email' => $email]);

        // A redeemed invitation is a distinct, already-bound identity.  First-time
        // approval must never silently reactivate it; that needs an explicit
        // operator action via sso:reactivate.
        if ($invitation->redeemed_at !== null) {
            $this->error('This approval has already been redeemed. Use sso:reactivate for an explicit reactivation.');
            return self::FAILURE;
        }

        $invitation->forceFill(['approved_at' => now(), 'revoked_at' => null])->save();
        $this->info('Approval saved.');
        return self::SUCCESS;
    }
}