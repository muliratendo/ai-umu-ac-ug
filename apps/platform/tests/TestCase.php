<?php

namespace Tests;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetIndependentInertiaMiddleware extends HandleInertiaRequests
{
    public function version(Request $request): ?string
    {
        return '';
    }
}

abstract class TestCase extends BaseTestCase
{
    protected function withoutAssets(): static
    {
        $this->app->bind(HandleInertiaRequests::class, fn () => new AssetIndependentInertiaMiddleware());

        return $this->withoutVite();
    }

    protected function setUpTraits()
    {
        if (isset(class_uses_recursive(static::class)[RefreshDatabase::class])) {
            $this->assertSafeTestingDatabase();
        }

        return parent::setUpTraits();
    }

    protected function assertSafeTestingDatabase(): void
    {
        $connectionName = (string) config('database.default');
        $connectionConfig = config("database.connections.{$connectionName}");

        if (
            $connectionName !== 'mysql'
            || ! is_array($connectionConfig)
            || ($connectionConfig['driver'] ?? null) !== 'mysql'
            || ($connectionConfig['database'] ?? null) !== 'testing'
            || filled($connectionConfig['url'] ?? null)
        ) {
            throw new \RuntimeException('Database integration tests require the isolated MySQL testing database.');
        }

        $connection = DB::connection($connectionName);
        $actualDatabase = (string) $connection->scalar('select database()');

        if (
            $connection->getDriverName() !== 'mysql'
            || $connection->getDatabaseName() !== 'testing'
            || $actualDatabase !== 'testing'
        ) {
            throw new \RuntimeException('Database integration tests require the isolated MySQL testing database.');
        }
    }
}