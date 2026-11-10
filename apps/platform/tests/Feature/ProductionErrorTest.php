<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ProductionErrorTest extends TestCase
{
    public function test_production_exception_response_does_not_disclose_internal_details(): void
    {
        $marker = 'audit-only-secret-marker';

        Route::get('/__test/exception', function () use ($marker): never {
            throw new RuntimeException($marker);
        });

        $response = $this->withExceptionHandling()->get('/__test/exception');

        $response->assertStatus(500)
            ->assertSee('Server Error')
            ->assertDontSee($marker)
            ->assertDontSee('RuntimeException')
            ->assertDontSee('vendor/laravel');
    }
}
