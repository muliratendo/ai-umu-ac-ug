<?php

// Standalone preflight: no migrations or writes. Does not read the developer .env.
try {
    if (is_file(dirname(__DIR__, 2).'/bootstrap/cache/config.php')) {
        throw new RuntimeException('Cached Laravel config exists; do not run acceptance tests.');
    }
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=testing', 'auth_acceptance',
        'acceptance-only-password', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'testing') {
        throw new RuntimeException('Wrong database');
    }
    if (! str_starts_with((string) $pdo->query('SELECT VERSION()')->fetchColumn(), '8.4.')) {
        throw new RuntimeException('Expected MySQL 8.4');
    }
    foreach (['laravel', 'platform'] as $forbidden) {
        $allowed = false;
        try { $pdo->exec('USE `'.$forbidden.'`'); $allowed = true; }
        catch (PDOException) {}
        if ($allowed) { throw new RuntimeException('Test account has forbidden development database access.'); }
    }
    echo "PASS: isolated MySQL 8.4 testing connection on loopback port 3307.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'STOP: '.get_class($error).'. Check the disposable container; do not change the test target to development.' . "\n");
    exit(1);
}
