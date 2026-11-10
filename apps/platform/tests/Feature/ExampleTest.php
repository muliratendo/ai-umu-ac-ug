<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_public_pages_and_health_endpoint_are_available(): void
    {
        $this->withoutAssets()->get('/')->assertOk();
        $this->withoutAssets()->get('/community')->assertOk();
        $this->get('/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_inertia_components_are_returned_without_build_assets(): void
    {
        $this->withoutAssets()
            ->get('/', ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('component', 'Home');

        $this->withoutAssets()
            ->get('/community', ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('component', 'Community');
    }
}
