<?php

namespace Tests\Feature;

use App\Models\GoogleIdentity;
use App\Models\SsoInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('concurrency')]
class AuthConcurrencyAcceptanceTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUpTraits()
    {
        // This runs BEFORE DatabaseMigrations can issue migrate:fresh.
        if (getenv('AUTH_ACCEPTANCE_CONCURRENCY') !== '1') {
            $this->markTestSkipped('Concurrency requires explicit opt-in and a disposable testing database.');
        }
        if (is_file(base_path('bootstrap/cache/config.php'))) {
            throw new \RuntimeException('Refusing cached application configuration.');
        }
        if (! app()->environment('testing')) {
            throw new \RuntimeException('Concurrency requires APP_ENV=testing.');
        }
        $this->assertSafeTestingDatabase();
        return parent::setUpTraits();
    }

    public function test_two_distinct_subjects_cannot_both_redeem_one_approval(): void
    {
        $this->race(false);
    }

    public function test_same_subject_race_resolves_to_one_user_without_outage(): void
    {
        $this->race(true);
    }

    private function race(bool $sameSubject): void
    {
        $email = 'race-'.bin2hex(random_bytes(6)).'@example.test';
        $invitation = SsoInvitation::query()->create(['email' => $email, 'approved_at' => now()]);
        $directory = sys_get_temp_dir().'/umu-auth-race-'.bin2hex(random_bytes(10));
        if (! mkdir($directory, 0700)) { throw new \RuntimeException('Cannot create private barrier directory.'); }
        $env = [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'testing',
            'DB_URL' => '', 'DATABASE_URL' => '', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array',
            'AUTH_ACCEPTANCE_CONCURRENCY' => '1',
            'AUTH_ACCEPTANCE_DB_CONFIG' => json_encode(config('database.connections.mysql'), JSON_THROW_ON_ERROR),
        ];
        $processes = [];
        try {
            // Fixtures are COMMITTED. RefreshDatabase's outer transaction is intentionally NOT used.
            DB::beginTransaction();
            SsoInvitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            foreach (['a', 'b'] as $slot) {
                $subject = $sameSubject ? 'race-same' : 'race-'.$slot;
                $process = new Process([
                    PHP_BINARY, base_path('tests/Support/auth-redemption-worker.php'),
                    $directory, $slot, $email, $subject,
                ], base_path(), $env, null, 35);
                $process->start();
                $processes[$slot] = $process;
            }
            $this->waitForFiles($directory, ['a.ready', 'b.ready'], $processes);
            touch($directory.'/go');
            $this->waitForFiles($directory, ['a.attempting', 'b.attempting'], $processes);
            // Both workers are live; neither may complete while the parent owns the approval lock.
            usleep(250000);
            foreach ($processes as $process) {
                $this->assertTrue($process->isRunning(), 'Worker ended before held approval lock was released.');
            }
            DB::commit();
            $results = [];
            foreach ($processes as $slot => $process) {
                $process->wait();
                $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
                $results[] = json_decode(file_get_contents($directory.'/'.$slot.'.json'), true, 512, JSON_THROW_ON_ERROR);
            }
            $statuses = array_column($results, 'status');
            sort($statuses);
            $this->assertSame($sameSubject ? ['success', 'success'] : ['denied', 'success'], $statuses);
            $this->assertSame(1, User::query()->where('email', $email)->count());
            $this->assertSame(1, GoogleIdentity::query()->where('email', $email)->count());
            $identity = GoogleIdentity::query()->where('email', $email)->sole();
            $fresh = $invitation->fresh();
            $this->assertNotNull($fresh->redeemed_at);
            $this->assertSame($identity->user_id, $fresh->redeemed_by);
            foreach ($results as $result) {
                if ($result['status'] === 'success') {
                    $this->assertSame($identity->user_id, $result['user_id']);
                }
            }
        } finally {
            while (DB::transactionLevel() > 0) { DB::rollBack(); }
            foreach ($processes as $process) { if ($process->isRunning()) { $process->stop(1); } }
            foreach (glob($directory.'/*') ?: [] as $file) { unlink($file); }
            rmdir($directory);
        }
    }

    private function waitForFiles(string $directory, array $names, array $processes): void
    {
        $deadline = microtime(true) + 20;
        do {
            if (count(array_filter($names, fn ($name) => is_file($directory.'/'.$name))) === count($names)) {
                return;
            }
            foreach ($processes as $process) {
                $this->assertTrue($process->isRunning(), 'Worker failed before barrier: '.$process->getErrorOutput());
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        $this->fail('Worker barrier timed out; do not treat this as a concurrency pass.');
    }
}
