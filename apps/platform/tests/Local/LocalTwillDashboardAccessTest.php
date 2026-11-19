<?php

namespace Tests\Local;

use A17\Twill\TwillServiceProvider;
use App\Models\User;
use Tests\TestCase;

final class LocalTwillDashboardAccessTest extends TestCase
{
    public function test_basic_web_account_is_redirected_to_twill_login(): void
    {
        $this->assertTrue(
            $this->app->environment('local'),
            'Run this local-only test with APP_ENV=local.'
        );
        $this->assertNotNull(
            $this->app->getProvider(TwillServiceProvider::class),
            'Twill must be loaded; a disabled route does not prove guard separation.'
        );

        $user = new User;
        $user->setAttribute($user->getAuthIdentifierName(), 401);

        $this->actingAs($user, 'web');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('twill_users');

        $this->get('/admin')->assertRedirectToRoute('twill.login.form');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('twill_users');
    }
}
