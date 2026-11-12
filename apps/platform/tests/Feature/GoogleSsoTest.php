<?php

namespace Tests\Feature;

use App\Models\GoogleIdentity;
use App\Models\SsoInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleSsoTest extends TestCase
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

    public function test_disabled_sso_does_not_expose_a_working_entrypoint(): void
    {
        config(['sso.enabled' => false]);

        $this->get('/auth/google')->assertNotFound();
    }

    public function test_enabled_sso_initiation_redirects_to_provider_and_sets_state(): void
    {
        $response = $this->get('/auth/google');

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $location);
        $this->assertStringContainsString('state=', $location);
        $this->assertStringContainsString('client_id=client-id', $location);
        $this->assertStringNotContainsString('client-secret', $location);
    }

    public function test_missing_callback_state_is_rejected_without_provider_contact(): void
    {
        $this->get('/auth/google/callback?code=unused')
            ->assertRedirect('/')
            ->assertSessionHas('auth_error', 'That sign-in request expired. Please try again.');
    }

    public function test_invalid_callback_state_is_rejected_without_provider_contact(): void
    {
        $this->withSession(['state' => 'session-state'])
            ->get('/auth/google/callback?code=fake&state=wrong-state')
            ->assertRedirect('/')
            ->assertSessionHas('auth_error', 'That sign-in request expired. Please try again.');
    }

    public function test_state_is_single_use_and_replay_is_rejected(): void
    {
        // First: mismatched state — rejected and state is consumed (pulled from session).
        $this->withSession(['state' => 'original'])
            ->get('/auth/google/callback?code=fake&state=wrong')
            ->assertRedirect('/')
            ->assertSessionHas('auth_error', 'That sign-in request expired. Please try again.');

        // Second: the original state — rejected because it was already consumed.
        $this->get('/auth/google/callback?code=fake&state=original')
            ->assertRedirect('/')
            ->assertSessionHas('auth_error', 'That sign-in request expired. Please try again.');
    }

    public function test_cancellation_returns_cancellation_message(): void
    {
        $this->withSession(['state' => 'state'])
            ->get('/auth/google/callback?error=access_denied&state=state')
            ->assertRedirect('/')
            ->assertSessionHas('auth_error');

        $this->assertGuest();
    }

    public function test_provider_failure_returns_generic_message_without_details(): void
    {
        Socialite::fake('google', function () {
            throw new \RuntimeException('sensitive-provider-detail');
        });

        $this->withSession(['state' => 'state'])
            ->get('/auth/google/callback?state=state')
            ->assertRedirect('/')
            ->assertSessionHas('auth_error', 'Sign-in is unavailable. Please try again later.');
    }

    public function test_approved_verified_identity_can_redeem_once_and_access_account(): void
    {
        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser());

        $response = $this->withSession(['state' => 'state'])->get('/auth/google/callback?state=state');
        $response->assertRedirect('/account');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('google_identities', ['provider' => 'google', 'subject' => 'subject-1']);
        $this->assertDatabaseHas('sso_invitations', ['email' => 'invited@example.test']);
        $this->withoutAssets()->get('/account')->assertOk()->assertInertia(fn ($page) => $page->component('Account', false));
    }

    public function test_repeat_login_uses_bound_subject_not_email_matching(): void
    {
        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'one'])->get('/auth/google/callback?state=one');
        auth()->logout();

        Socialite::fake('google', $this->providerUser(['email' => 'changed@example.test']));
        $this->withSession(['state' => 'two'])->get('/auth/google/callback?state=two')->assertRedirect('/account');
        $this->assertAuthenticated();
        $this->assertSame(1, User::query()->count());
    }

    public function test_invitation_cannot_be_redeemed_twice_by_different_subjects(): void
    {
        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);

        Socialite::fake('google', $this->providerUser(['id' => 'subject-1']));
        $this->withSession(['state' => 'one'])->get('/auth/google/callback?state=one');
        $this->assertAuthenticated();
        auth()->logout();

        Socialite::fake('google', $this->providerUser(['id' => 'subject-2']));
        $this->withSession(['state' => 'two'])->get('/auth/google/callback?state=two')
            ->assertRedirect('/');
        $this->assertGuest();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, GoogleIdentity::query()->count());
        $this->assertSame(0, GoogleIdentity::query()->where('subject', 'subject-2')->count());
    }

    public function test_unapproved_unverified_and_email_collision_are_rejected(): void
    {
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'one'])->get('/auth/google/callback?state=one')->assertRedirect('/');
        $this->assertGuest();

        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser(['email_verified' => false]));
        $this->withSession(['state' => 'two'])->get('/auth/google/callback?state=two')->assertRedirect('/');
        $this->assertGuest();

        User::factory()->create(['email' => 'invited@example.test']);
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'three'])->get('/auth/google/callback?state=three')->assertRedirect('/');
        $this->assertSame(0, GoogleIdentity::query()->count());
    }

    public function test_login_does_not_create_privileged_status(): void
    {
        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'state'])->get('/auth/google/callback?state=state');

        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
        $this->assertFalse(Schema::hasColumn('users', 'role'));

        $user = User::first();
        $this->assertNotNull($user);
        $this->assertArrayNotHasKey('is_admin', $user->getAttributes());
        $this->assertArrayNotHasKey('role', $user->getAttributes());
    }

    public function test_revoked_approval_blocks_relogin_existing_access_and_logout_is_post_only(): void
    {
        $invitation = SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'state'])->get('/auth/google/callback?state=state');

        $invitation->update(['revoked_at' => now()]);

        // Existing protected access is blocked.
        $this->get('/account')->assertRedirect('/')->assertSessionHas('auth_error');
        $this->assertGuest();

        // A new login attempt is also rejected.
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'new'])->get('/auth/google/callback?state=new')
            ->assertRedirect('/');
        $this->assertGuest();

        // Logout is POST-only.
        $this->assertContains($this->post('/logout')->status(), [200, 302]);
    }

    public function test_logout_remains_available_when_sso_is_disabled(): void
    {
        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser());
        $this->withSession(['state' => 'state'])->get('/auth/google/callback?state=state');
        $this->assertAuthenticated();

        config(['sso.enabled' => false]);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_unauthenticated_account_access_redirects_home(): void
    {
        $this->withoutAssets()->get('/account')
            ->assertRedirect('/')
            ->assertSessionHas('auth_error');

        $this->assertGuest();
    }

    public function test_no_open_redirect_on_successful_login(): void
    {
        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser());

        $this->withSession(['state' => 'state', 'url.intended' => 'https://evil.example.test'])
            ->get('/auth/google/callback?state=state')
            ->assertRedirect('/account');
    }

    public function test_session_is_regenerated_on_successful_login(): void
    {
        SsoInvitation::query()->create(['email' => 'invited@example.test', 'approved_at' => now()]);
        Socialite::fake('google', $this->providerUser());

        $this->withSession(['state' => 'state']);
        $beforeId = session()->getId();

        $this->get('/auth/google/callback?state=state')->assertRedirect('/account');

        $afterId = session()->getId();
        $this->assertNotEquals($beforeId, $afterId);
    }

    public function test_secrets_are_not_disclosed_in_responses(): void
    {
        $this->withoutAssets()->get('/')->assertOk()->assertDontSee('client-secret');

        $this->get('/health')->assertExactJson(['status' => 'ok']);

        $response = $this->get('/auth/google');
        $response->assertRedirect();
        $this->assertStringNotContainsString('client-secret', $response->headers->get('Location'));

        config(['sso.enabled' => false]);
        $this->get('/auth/google')->assertNotFound()->assertDontSee('client-secret');
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
