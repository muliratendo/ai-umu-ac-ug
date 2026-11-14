<?php

namespace Tests\Feature;

use App\Models\GoogleIdentity;
use App\Models\SsoInvitation;
use App\Models\SsoReactivationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SsoReactivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $callback = route('auth.google.callback');

        config([
            'sso.enabled' => true,
            'sso.client_id' => 'client-id',
            'sso.client_secret' => 'client-secret',
            'sso.redirect_uri' => $callback,
            'services.google' => [
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
                'redirect' => $callback,
            ],
        ]);
    }

    public function test_reactivation_restores_only_the_original_redeemed_google_identity(): void
    {
        $invitation = SsoInvitation::query()->create([
            'email' => 'invited@example.test',
            'approved_at' => now()->subDay(),
        ]);
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'first'])->get('/auth/google/callback?state=first')
            ->assertRedirect('/account');

        $invitation->refresh();
        $user = User::query()->sole();
        $identity = GoogleIdentity::query()->sole();
        $original = [
            'invitation_id' => $invitation->id,
            'approved_at' => $invitation->approved_at->toISOString(),
            'redeemed_at' => $invitation->redeemed_at->toISOString(),
            'redeemed_by' => $invitation->redeemed_by,
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'provider' => $identity->provider,
            'subject' => $identity->subject,
            'identity_email' => $identity->email,
            'user_email' => $user->email,
        ];

        $this->artisan('sso:revoke invited@example.test')->assertSuccessful();
        $revokedAt = $invitation->fresh()->revoked_at->toISOString();

        $this->get('/account')->assertRedirect('/')->assertSessionHas('auth_outcome', 'eligibility_denied');
        $this->assertGuest();

        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'revoked-login'])->get('/auth/google/callback?state=revoked-login')
            ->assertRedirect('/')
            ->assertSessionHas('auth_outcome', 'eligibility_denied');
        $this->assertGuest();

        $this->artisan('sso:reactivate invited@example.test --reason="operator-approved test" --force')
            ->assertSuccessful();

        $invitation->refresh();
        $identity->refresh();
        $user->refresh();

        $this->assertNull($invitation->revoked_at);
        $this->assertSame($original['invitation_id'], $invitation->id);
        $this->assertSame($original['approved_at'], $invitation->approved_at->toISOString());
        $this->assertSame($original['redeemed_at'], $invitation->redeemed_at->toISOString());
        $this->assertSame($original['redeemed_by'], $invitation->redeemed_by);
        $this->assertSame($original['user_id'], $user->id);
        $this->assertSame($original['identity_id'], $identity->id);
        $this->assertSame($original['provider'], $identity->provider);
        $this->assertSame($original['subject'], $identity->subject);
        $this->assertSame($original['identity_email'], $identity->email);
        $this->assertSame($original['user_email'], $user->email);
        $this->assertDatabaseHas('sso_reactivation_events', [
            'sso_invitation_id' => $invitation->id,
            'prior_revoked_at' => $revokedAt,
            'reason' => 'operator-approved test',
        ]);
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, GoogleIdentity::query()->count());
        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
        $this->assertFalse(Schema::hasColumn('users', 'role'));

        Socialite::fake('google', $this->providerUser(['email' => 'changed@example.test']));
        $this->withSession(['state' => 'original-again'])->get('/auth/google/callback?state=original-again')
            ->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        Socialite::fake('google', $this->providerUser(['id' => 'different-subject']));
        $this->withSession(['state' => 'different'])->get('/auth/google/callback?state=different')
            ->assertRedirect('/')
            ->assertSessionHas('auth_outcome', 'eligibility_denied');
        $this->assertGuest();
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, GoogleIdentity::query()->count());
        $this->assertSame($original['subject'], $identity->fresh()->subject);
    }

    public function test_sso_approve_does_not_reactivate_a_redeemed_revoked_identity(): void
    {
        [$invitation] = $this->makeBoundInvitation(revoked: true);
        $revokedAt = $invitation->revoked_at->toISOString();

        $this->artisan('sso:approve invited@example.test')->assertFailed();

        $this->assertSame($revokedAt, $invitation->fresh()->revoked_at->toISOString());
        $this->assertSame(0, SsoReactivationEvent::query()->count());
    }

    public function test_reactivation_cancellation_and_noninteractive_refusal_leave_data_unchanged(): void
    {
        [$invitation] = $this->makeBoundInvitation(revoked: true);
        $revokedAt = $invitation->revoked_at->toISOString();

        $this->artisan('sso:reactivate invited@example.test')
            ->expectsConfirmation('Reactivate Google sign-in eligibility for this approved email?', 'no')
            ->assertFailed();

        $this->artisan('sso:reactivate invited@example.test --no-interaction')->assertFailed();

        $this->assertSame($revokedAt, $invitation->fresh()->revoked_at->toISOString());
        $this->assertSame(0, SsoReactivationEvent::query()->count());
    }

    public function test_reactivation_requires_a_redeemed_consistently_bound_record(): void
    {
        $unredeemed = SsoInvitation::query()->create([
            'email' => 'unredeemed@example.test',
            'approved_at' => now(),
            'revoked_at' => now(),
        ]);

        $this->artisan('sso:reactivate missing@example.test --force')->assertFailed();
        $this->artisan('sso:reactivate unredeemed@example.test --force')->assertFailed();

        $malformedUser = User::factory()->create(['email' => 'malformed@example.test']);
        $malformed = SsoInvitation::query()->create([
            'email' => 'malformed@example.test',
            'approved_at' => now(),
            'revoked_at' => now(),
            'redeemed_by' => $malformedUser->id,
        ]);
        $this->artisan('sso:reactivate malformed@example.test --force')->assertFailed();

        [$mismatched] = $this->makeBoundInvitation(
            email: 'bound@example.test',
            identityEmail: 'other@example.test',
            revoked: true,
        );
        $mismatchedRevokedAt = $mismatched->revoked_at->toISOString();

        $this->artisan('sso:reactivate bound@example.test --force')->assertFailed();

        $this->assertNotNull($unredeemed->fresh()->revoked_at);
        $this->assertNotNull($malformed->fresh()->revoked_at);
        $this->assertSame($mismatchedRevokedAt, $mismatched->fresh()->revoked_at->toISOString());
        $this->assertSame(0, SsoReactivationEvent::query()->count());
    }

    public function test_reactivation_is_idempotent_for_an_already_active_consistent_binding(): void
    {
        [$invitation] = $this->makeBoundInvitation(revoked: false);

        $this->artisan('sso:reactivate invited@example.test --force')->assertSuccessful();
        $this->artisan('sso:reactivate invited@example.test --force')->assertSuccessful();

        $this->assertNull($invitation->fresh()->revoked_at);
        $this->assertSame(0, SsoReactivationEvent::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, GoogleIdentity::query()->count());
    }

    /**
     * @return array{SsoInvitation, User, GoogleIdentity}
     */
    private function makeBoundInvitation(
        string $email = 'invited@example.test',
        ?string $identityEmail = null,
        bool $revoked = true,
    ): array {
        $user = User::factory()->create(['email' => $email]);
        $invitation = SsoInvitation::query()->create([
            'email' => $email,
            'approved_at' => now()->subDay(),
            'revoked_at' => $revoked ? now()->subHour() : null,
            'redeemed_at' => now()->subMinute(),
            'redeemed_by' => $user->id,
        ]);
        $identity = GoogleIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'subject' => 'stable-subject',
            'email' => $identityEmail ?? $email,
        ]);

        return [$invitation, $user, $identity];
    }

    private function providerUser(array $attributes = []): SocialiteUser
    {
        return SocialiteUser::fake(array_merge([
            'id' => 'subject-1',
            'name' => 'Invited User',
            'email' => 'invited@example.test',
            'email_verified' => true,
        ], $attributes));
    }
}
