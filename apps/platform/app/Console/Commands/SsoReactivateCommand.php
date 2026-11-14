<?php

namespace App\Console\Commands;

use App\Models\GoogleIdentity;
use App\Models\SsoInvitation;
use App\Models\SsoReactivationEvent;
use App\Models\User;
use App\Support\GoogleSso;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Explicit, local operator action that restores sign-in eligibility for a
 * previously revoked, redeemed invitation.  This is not self-service recovery:
 * it only clears revocation, never recreates an identity or user, never grants
 * privileges, and never rebinds the Google subject.
 */
class SsoReactivateCommand extends Command
{
    protected $signature = 'sso:reactivate
        {email : The exact approved email address of the redeemed invitation}
        {--reason= : Optional operator-supplied reason recorded with the event}
        {--force : Confirm without an interactive prompt (for automated use)}';

    protected $description = 'Reactivate a revoked, redeemed Google sign-in approval without changing its identity binding';

    public function handle(): int
    {
        $email = GoogleSso::normalizeEmail((string) $this->argument('email'));

        if ($email === null) {
            $this->error('Provide a valid email address.');
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                return $this->failed('Reactivation requires an interactive confirmation or the explicit --force option; no changes were made.');
            }

            // A non-interactive run cannot answer the confirmation, so it is
            // refused rather than silently defaulted.
            $confirmed = $this->confirm(
                'Reactivate Google sign-in eligibility for this approved email?',
                false,
            );

            if (! $confirmed) {
                $this->error('Reactivation refused; no changes were made. Re-run with --force for non-interactive use.');
                return self::FAILURE;
            }
        }

        $result = DB::transaction(fn (): array => $this->reactivate($email), 3);

        return $this->report($result);
    }

    /**
     * @return array{status: string, prior_revoked_at?: string}
     */
    private function reactivate(string $email): array
    {
        $invitation = SsoInvitation::query()->where('email', $email)->lockForUpdate()->first();

        if ($invitation === null) {
            return ['status' => 'missing'];
        }

        if ($invitation->approved_at === null) {
            return ['status' => 'malformed'];
        }

        if ($invitation->redeemed_at === null && $invitation->redeemed_by === null) {
            return ['status' => 'not_redeemed'];
        }

        if ($invitation->redeemed_at === null || $invitation->redeemed_by === null) {
            return ['status' => 'malformed'];
        }

        $user = User::query()->whereKey($invitation->redeemed_by)->lockForUpdate()->first();

        $identity = GoogleIdentity::query()
            ->where('user_id', $invitation->redeemed_by)
            ->where('provider', 'google')
            ->lockForUpdate()
            ->first();

        if (
            $user === null
            || $identity === null
            || trim((string) $identity->subject) === ''
            || GoogleSso::normalizeEmail((string) $user->email) !== $invitation->email
            || GoogleSso::normalizeEmail((string) $identity->email) !== $invitation->email
        ) {
            return ['status' => 'binding_mismatch'];
        }

        if ($invitation->revoked_at === null) {
            return ['status' => 'already_active'];
        }

        $priorRevokedAt = $invitation->revoked_at;

        $invitation->forceFill(['revoked_at' => null])->save();

        SsoReactivationEvent::query()->create([
            'sso_invitation_id' => $invitation->id,
            'prior_revoked_at' => $priorRevokedAt,
            'reason' => mb_substr(trim((string) $this->option('reason')) ?: 'operator reactivation', 0, 255),
        ]);

        return ['status' => 'reactivated', 'prior_revoked_at' => $priorRevokedAt->toDateTimeString()];
    }

    /**
     * @param  array{status: string, prior_revoked_at?: string}  $result
     */
    private function report(array $result): int
    {
        return match ($result['status']) {
            'reactivated' => $this->succeeded('Approval reactivated; the redeemed identity binding was preserved.'),
            'already_active' => $this->succeeded('That approval is already active; nothing to change.'),
            'missing' => $this->failed('No approval exists for that email. Use sso:approve to create one.'),
            'not_redeemed' => $this->failed('That approval has not been redeemed. Use sso:approve instead.'),
            'malformed' => $this->failed('That approval record is incomplete and cannot be reactivated.'),
            'binding_mismatch' => $this->failed('The redeemed account and its Google identity binding are inconsistent; refusing to reactivate.'),
            default => $this->failed('Reactivation could not be completed.'),
        };
    }

    private function succeeded(string $message): int
    {
        $this->info($message);
        return self::SUCCESS;
    }

    private function failed(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }
}
