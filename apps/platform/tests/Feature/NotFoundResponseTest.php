<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NotFoundResponseTest extends TestCase
{
    public function test_unknown_browser_get_uses_the_not_found_inertia_page_and_keeps_404_status(): void
    {
        $response = $this->withoutAssets()->get('/unknown-route-marker');

        $response->assertNotFound()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('"component":"NotFound"', false)
            ->assertDontSee('unknown-route-marker');
    }

    public function test_unknown_inertia_navigation_returns_not_found_page_payload(): void
    {
        $this->withoutAssets()
            ->get('/unknown-route-marker', [
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html, application/xhtml+xml',
            ])
            ->assertNotFound()
            ->assertHeader('X-Inertia', 'true')
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('component', 'NotFound')
            ->assertJsonPath('url', '/404')
            ->assertDontSee('unknown-route-marker');
    }

    public function test_disabled_sso_entrypoint_uses_the_same_not_found_page(): void
    {
        config(['sso.enabled' => false]);

        $this->withoutAssets()
            ->get('/auth/google')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('"component":"NotFound"', false)
            ->assertDontSee('auth/google');
    }

    public function test_unknown_json_requests_keep_json_404_semantics(): void
    {
        $this->getJson('/unknown-json-route')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found']);

        $this->get('/api/unknown-route')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not Found']);
    }

    public function test_method_mismatch_keeps_405_semantics(): void
    {
        $this->withoutAssets()->post('/account')->assertStatus(405);
    }

    public function test_custom_404_rendering_does_not_change_forbidden_or_expired_statuses(): void
    {
        Route::get('/__test/forbidden', fn () => abort(403));
        Route::get('/__test/expired', fn () => abort(419));

        $this->withoutAssets()->get('/__test/forbidden')->assertForbidden();
        $this->withoutAssets()->get('/__test/expired')->assertStatus(419);
    }
}
