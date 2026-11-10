<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->assertSafeTestingDatabase();

        parent::setUp();
    }

    public function test_mysql_testing_database_can_insert_read_and_report_its_engine(): void
    {
        $this->assertSame('mysql', DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertStringStartsWith('8.4.', (string) DB::scalar('select version()'));

        $user = User::factory()->create(['email' => 'integration@example.test']);

        $this->assertSame(
            'integration@example.test',
            User::query()->whereKey($user->getKey())->value('email'),
        );
    }
}
