<?php

namespace Tests\Feature;

use Tests\TestCase;

class DisabledRoutesTest extends TestCase
{
    public function test_disabled_features_do_not_perform_operations(): void
    {
        foreach ([
            '/admin',
            '/admin/login',
            '/login',
            '/register',
            '/auth/google',
            '/auth/google/callback',
            '/ai',
        ] as $uri) {
            $this->withoutAssets()->get($uri)->assertNotFound();
        }

        $this->assertContains($this->withoutAssets()->post('/admin/login')->status(), [404, 405]);
        $this->assertContains($this->withoutAssets()->post('/auth/google')->status(), [404, 405]);
    }
}
