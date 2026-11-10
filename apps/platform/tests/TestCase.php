<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Http\Middleware\HandleInertiaRequests;

abstract class TestCase extends BaseTestCase
{
    protected function withoutAssets(): static
    {
        return $this->withoutVite()
            ->withoutMiddleware(HandleInertiaRequests::class);
    }

    protected function assertSafeTestingDatabase(): void
    {
        $database = (string) getenv('DB_DATABASE');
        $connection = (string) getenv('DB_CONNECTION');

        if ($connection !== 'mysql' || $database !== 'testing') {
            throw new \RuntimeException('Database integration tests require the isolated MySQL testing database.');
        }
    }
}
