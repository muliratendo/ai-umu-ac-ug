<?php

namespace Tests\Feature;

use App\Models\GoogleIdentity;
use App\Models\SsoInvitation;
use App\Models\SsoReactivationEvent;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class EnforcedAcceptanceCsrf extends PreventRequestForgery
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}

class AuthAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutAssets();
        $callback = route('auth.google.callback');
        config([
            'sso.enabled' => true,
            'sso.client_id' => 'acceptance-client',
            'sso.client_secret' => 'acceptance-secret',
            'sso.redirect_uri' => $callback,
            'services.google' => [
                'client_id' => 'acceptance-client',
                'client_secret' => 'acceptance-secret',
                'redirect' => $callback,
            ],
        ]);
    }

    private function approvedLogin(): SsoInvitation
    {
        $invitation = SsoInvitation::query()->create([
            'email' => 'acceptance@example.test', 'approved_at' => now(),
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'acceptance-subject', 'name' => 'Acceptance member',
            'email' => $invitation->email, 'email_verified' => true,
        ]));
        $this->withSession(['state' => 'fixture-state'])
            ->get('/auth/google/callback?state=fixture-state')->assertRedirect('/account');
        $this->assertAuthenticated();
        return $invitation->fresh();
    }

    private function enforceCsrf(): void
    {
        // Keep web/session middleware active, overriding ONLY Laravel's test bypass.
        $this->app->bind(PreventRequestForgery::class, EnforcedAcceptanceCsrf::class);
    }

    public function test_logout_really_requires_csrf_and_valid_token_still_works_when_disabled(): void
    {
        $this->approvedLogin();
        $this->enforceCsrf();
        config(['sso.enabled' => false]);
        $this->withSession(['_token' => 'csrf-before', 'private-sentinel' => 'keep-until-logout']);
        $this->withHeader('Sec-Fetch-Site', 'cross-site')->post('/logout')->assertStatus(419);
        $this->assertAuthenticated();
        $this->post('/logout', ['_token' => 'wrong-token'])->assertStatus(419);
        $this->assertAuthenticated();
        $beforeId = session()->getId();
        $this->post('/logout', ['_token' => 'csrf-before'])->assertRedirect('/');
        $this->assertGuest();
        $this->assertNotSame($beforeId, session()->getId());
        $this->assertNotSame('csrf-before', session()->token());
        $this->assertNull(session('private-sentinel'));
    }

    public function test_laravel_same_origin_policy_is_not_mistaken_for_missing_csrf_protection(): void
    {
        $this->approvedLogin();
        $this->enforceCsrf();
        $this->withHeader('Sec-Fetch-Site', 'same-origin')->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_get_logout_never_logs_out(): void
    {
        $this->approvedLogin();
        $this->get('/logout')->assertStatus(405);
        $this->assertAuthenticated();
    }

    public function test_disabled_session_boundary_rotates_id_token_and_removes_data(): void
    {
        $this->assertBoundary('disabled');
    }

    public function test_missing_configuration_boundary_rotates_id_token_and_removes_data(): void
    {
        $this->assertBoundary('missing-secret');
    }

    public function test_revocation_boundary_rotates_id_token_and_removes_data(): void
    {
        $this->assertBoundary('revoked');
    }

    private function assertBoundary(string $cause): void
    {
        $invitation = $this->approvedLogin();
        $this->withSession(['_token' => 'old-csrf', 'private-sentinel' => 'must-disappear']);
        $beforeId = session()->getId();
        if ($cause === 'disabled') {
            config(['sso.enabled' => false]);
        } elseif ($cause === 'missing-secret') {
            config(['sso.client_secret' => null]);
        } else {
            $invitation->update(['revoked_at' => now()]);
        }
        $outcome = $cause === 'revoked' ? 'eligibility_denied' : 'signin_unavailable';
        $this->get('/account')->assertRedirect('/')->assertSessionHas('auth_outcome', $outcome);
        $this->assertGuest();
        $this->assertNotSame($beforeId, session()->getId());
        $this->assertNotSame('old-csrf', session()->token());
        $this->assertNull(session('private-sentinel'));
        config(['sso.enabled' => true, 'sso.client_secret' => 'acceptance-secret']);
        if ($cause === 'revoked') {
            $this->artisan('sso:reactivate', ['email' => $invitation->email, '--force' => true])
                ->assertSuccessful();
        }
        $this->get('/account')->assertRedirect('/')->assertSessionHas('auth_outcome', 'signin_required');
        $this->assertGuest();
        $this->get('/')->assertOk();
        $this->get('/community')->assertOk();
    }

    public function test_successful_real_provider_state_is_consumed_and_cannot_be_replayed(): void
    {
        SsoInvitation::query()->create([
            'email' => 'acceptance@example.test', 'approved_at' => now(),
        ]);
        // Real GoogleProvider validation and mapping; only outbound HTTP is stubbed.
        $queue = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'fake-access-token', 'token_type' => 'Bearer', 'expires_in' => 3600,
            ], JSON_THROW_ON_ERROR)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'sub' => 'real-provider-fixture-subject', 'name' => 'Acceptance member',
                'email' => 'acceptance@example.test', 'email_verified' => true,
            ], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client(['handler' => HandlerStack::create($queue)]);
        Socialite::shouldReceive('driver')->with('google')->andReturnUsing(function () use ($client) {
            return (new GoogleProvider(app('request'), 'acceptance-client', 'acceptance-secret',
                route('auth.google.callback')))->setHttpClient($client);
        });
        $this->withSession(['state' => 'single-use', '_token' => 'login-token']);
        $oldId = session()->getId();
        $this->get('/auth/google/callback?code=fixture-code&state=single-use')->assertRedirect('/account');
        $this->assertAuthenticated();
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNull(session('state'));
        $this->assertSame(0, $queue->count(), 'The success path must exercise both fake HTTP responses.');
        $this->post('/logout')->assertRedirect('/');
        $this->get('/auth/google/callback?code=fixture-code&state=single-use')
            ->assertRedirect('/')->assertSessionHas('auth_outcome', 'expired_request');
        $this->assertGuest();
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, GoogleIdentity::query()->count());
    }

    public function test_valid_cancellation_consumes_state_without_contacting_provider(): void
    {
        // user() or an HTTP-client call on this mock is an unexpected call and fails.
        Socialite::shouldReceive('driver')->with('google')->twice()->andReturn(new \stdClass());
        $this->withSession(['state' => 'cancel-once'])
            ->get('/auth/google/callback?error=access_denied&state=cancel-once')
            ->assertRedirect('/')->assertSessionHas('auth_outcome', 'cancelled');
        $this->assertNull(session('state'));
        $this->get('/auth/google/callback?error=access_denied&state=cancel-once')
            ->assertRedirect('/')->assertSessionHas('auth_outcome', 'expired_request');
        $this->assertGuest();
    }

    public function test_malformed_cancellation_state_is_an_invalid_request_not_an_outage(): void
    {
        // Deliberately strict: malformed input should not masquerade as provider downtime.
        $this->withSession(['state' => 'expected'])
            ->get('/auth/google/callback?error=access_denied&state%5B0%5D=expected')
            ->assertRedirect('/')->assertSessionHas('auth_outcome', 'expired_request');
        $this->assertGuest();
    }

    public function test_reactivation_event_write_failure_rolls_back_revocation_clear(): void
    {
        $invitation = $this->approvedLogin();
        $this->post('/logout');
        $invitation->update(['revoked_at' => now()->subMinute()]);
        $before = $invitation->fresh()->getAttributes();
        $eventAttempted = false;
        SsoReactivationEvent::creating(function () use (&$eventAttempted) {
            $eventAttempted = true;
            throw new \RuntimeException('acceptance-injected-event-failure');
        });
        $caught = false;
        try {
            $this->artisan('sso:reactivate', ['email' => $invitation->email, '--force' => true])->run();
        } catch (\Throwable $error) {
            $caught = $error->getMessage() === 'acceptance-injected-event-failure';
        }
        $this->assertTrue($eventAttempted, 'The durable-event write was never reached.');
        $this->assertTrue($caught, 'Injected durable-event failure must not silently succeed.');
        $this->assertSame($before, $invitation->fresh()->getAttributes());
        $this->assertSame(0, SsoReactivationEvent::query()->count());
    }
}
