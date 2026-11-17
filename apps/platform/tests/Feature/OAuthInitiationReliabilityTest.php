<?php

namespace Tests\Feature;

use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class OAuthInitiationReliabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutAssets();
        config([
            'sso.enabled' => true,
            'sso.client_id' => 'reliability-client',
            'sso.client_secret' => 'reliability-secret',
            'sso.redirect_uri' => route('auth.google.callback'),
            'services.google' => [
                'client_id' => 'reliability-client',
                'client_secret' => 'reliability-secret',
                'redirect' => route('auth.google.callback'),
            ],
        ]);
    }

    public function test_document_request_keeps_ordinary_redirect_and_establishes_state(): void
    {
        $response = $this->get('/auth/google');
        $response->assertStatus(302)->assertHeaderMissing('X-Inertia-Location');
        $this->assertDestination($response->headers->get('Location'));
    }

    public function test_inertia_request_gets_external_location_without_a_302(): void
    {
        $response = $this->get('/auth/google', ['X-Inertia' => 'true']);
        $response->assertStatus(409)->assertHeaderMissing('Location');
        $this->assertDestination($response->headers->get('X-Inertia-Location'));
    }

    public function test_query_parameters_cannot_override_the_destination_or_provider_parameters(): void
    {
        $response = $this->get('/auth/google?url=https%3A%2F%2Fevil.example.test&redirect_uri=https%3A%2F%2Fevil.example.test&state=attacker&scope=drive', [
            'X-Inertia' => 'true',
        ]);
        $response->assertStatus(409);
        $target = $response->headers->get('X-Inertia-Location');
        $this->assertDestination($target);
        $this->assertStringNotContainsString('evil.example.test', $target);
        parse_str(parse_url($target, PHP_URL_QUERY), $query);
        $this->assertNotSame('attacker', $query['state']);
    }

    public function test_unavailable_sso_never_calls_provider_for_either_request_type(): void
    {
        Socialite::shouldReceive('driver')->never();
        foreach (['sso.enabled' => false, 'sso.client_id' => null,
            'sso.client_secret' => null, 'sso.redirect_uri' => null] as $key => $missing) {
            $original = config($key);
            config([$key => $missing]);
            foreach ([[], ['X-Inertia' => 'true']] as $headers) {
                $response = $this->get('/auth/google', $headers);
                $response->assertNotFound()->assertHeaderMissing('Location')->assertHeaderMissing('X-Inertia-Location');
            }
            config([$key => $original]);
        }
    }

    private function assertDestination(?string $target): void
    {
        $this->assertNotNull($target);
        $this->assertSame('https', parse_url($target, PHP_URL_SCHEME));
        $this->assertSame('accounts.google.com', parse_url($target, PHP_URL_HOST));
        parse_str(parse_url($target, PHP_URL_QUERY), $query);
        $this->assertSame('reliability-client', $query['client_id']);
        $this->assertSame(route('auth.google.callback'), $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('select_account', $query['prompt']);
        $scopes = preg_split('/\s+/', trim($query['scope']));
        sort($scopes);
        $this->assertSame(['email', 'openid', 'profile'], $scopes);
        $this->assertIsString(session('state'));
        $this->assertNotSame('', session('state'));
        $this->assertSame(session('state'), $query['state']);
        $this->assertArrayNotHasKey('client_secret', $query);
        $this->assertStringNotContainsString('reliability-secret', $target);
        $this->assertGuest();
    }
}
