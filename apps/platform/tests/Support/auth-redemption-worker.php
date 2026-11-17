<?php

// Test-only CLI worker. No routes or application provider registration.
if (PHP_SAPI !== 'cli' || getenv('AUTH_ACCEPTANCE_CONCURRENCY') !== '1') {
    fwrite(STDERR, "Worker requires explicit CLI concurrency opt-in.\n"); exit(90);
}
$base = dirname(__DIR__, 2);
if (is_file($base.'/bootstrap/cache/config.php')) {
    fwrite(STDERR, "Refusing cached application configuration.\n"); exit(91);
}
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    if (! $app->environment('testing')) { throw new RuntimeException('environment'); }
    $db = json_decode((string) getenv('AUTH_ACCEPTANCE_DB_CONFIG'), true, 512, JSON_THROW_ON_ERROR);
    if (($db['driver'] ?? '') !== 'mysql' || ($db['database'] ?? '') !== 'testing' || ! empty($db['url'])) {
        throw new RuntimeException('database configuration');
    }
    config(['database.default' => 'mysql', 'database.connections.mysql' => $db]);
    Illuminate\Support\Facades\DB::purge('mysql');
    $connection = Illuminate\Support\Facades\DB::connection();
    if ($connection->scalar('select database()') !== 'testing') { throw new RuntimeException('database identity'); }
    $connection->statement('SET SESSION innodb_lock_wait_timeout = 15');
    [$script, $directory, $slot, $email, $subject] = $argv;
    if (! is_dir($directory) || ! in_array($slot, ['a', 'b'], true) || ! str_ends_with($email, '@example.test')) {
        throw new RuntimeException('worker inputs');
    }
    touch($directory.'/'.$slot.'.ready');
    $deadline = microtime(true) + 20;
    while (! is_file($directory.'/go')) {
        if (microtime(true) > $deadline) { throw new RuntimeException('barrier timeout'); }
        usleep(10000);
    }
    touch($directory.'/'.$slot.'.attempting');
    $provider = Laravel\Socialite\Two\User::fake([
        'id' => $subject, 'email' => $email, 'email_verified' => true, 'name' => 'Concurrent acceptance',
    ]);
    try {
        $user = app(App\Services\GoogleAccountService::class)->authenticate($provider);
        $result = ['status' => 'success', 'user_id' => $user->id];
    } catch (App\Exceptions\SsoAccessDenied) {
        $result = ['status' => 'denied'];
    }
    file_put_contents($directory.'/'.$slot.'.json', json_encode($result, JSON_THROW_ON_ERROR));
    exit(0);
} catch (Throwable $error) {
    // Never print connection credentials or exception messages from a subprocess.
    fwrite(STDERR, 'Worker failed: '.get_class($error)."\n"); exit(92);
}
