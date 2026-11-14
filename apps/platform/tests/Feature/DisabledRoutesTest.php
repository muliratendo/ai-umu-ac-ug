<?php

namespace Tests\Feature;

use Tests\TestCase;

class DisabledRoutesTest extends TestCase
{
    public function test_password_admin_register_and_ai_routes_are_always_unavailable(): void
    {
        foreach ([
            '/admin',
            '/admin/login',
            '/login',
            '/register',
            '/ai',
        ] as $uri) {
            $this->withoutAssets()->get($uri)->assertNotFound();
        }

        $this->assertContains($this->withoutAssets()->post('/admin/login')->status(), [404, 405]);
        $this->assertContains($this->withoutAssets()->post('/login')->status(), [404, 405]);
        $this->assertContains($this->withoutAssets()->post('/register')->status(), [404, 405]);
    }

    public function test_sso_routes_are_disabled_by_default(): void
    {
        // This contract is explicit even when the developer .env enables SSO.
        config(['sso.enabled' => false]);

        foreach (['/auth/google', '/auth/google/callback'] as $uri) {
            $this->withoutAssets()->get($uri)->assertNotFound();
        }

        $this->assertContains($this->withoutAssets()->post('/auth/google')->status(), [404, 405]);
    }
}
