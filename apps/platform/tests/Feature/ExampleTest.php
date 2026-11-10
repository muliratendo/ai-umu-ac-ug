<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_public_pages_and_health_endpoint_are_available(): void
    {
        $this->get('/')->assertOk();
        $this->get('/community')->assertOk();
        $this->get('/health')->assertOk()->assertJson(['status' => 'ok']);
        $this->get('/admin')->assertNotFound();
        $this->get('/ai')->assertNotFound();
    }
}
