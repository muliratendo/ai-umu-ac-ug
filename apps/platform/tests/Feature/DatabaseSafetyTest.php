<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseSafetyTest extends TestCase
{
    public function test_unsafe_effective_target_is_rejected_before_database_reset(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'platform',
            'database.connections.mysql.url' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('isolated MySQL testing database');

        $this->assertSafeTestingDatabase();
    }

    public function test_connection_url_is_rejected_even_when_database_name_looks_safe(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'testing',
            'database.connections.mysql.url' => 'mysql://sail_test:password@127.0.0.1/platform',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('isolated MySQL testing database');

        $this->assertSafeTestingDatabase();
    }
}