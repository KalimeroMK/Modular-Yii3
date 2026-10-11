<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Database connection.
 *
 * The driver is picked from the DSN scheme, so switching from SQLite to MySQL is one line in `.env`:
 *
 * ```
 * DB_DSN=mysql:host=127.0.0.1;dbname=app
 * ```
 */
$dsn = (string) ($_ENV['DB_DSN'] ?? '') ?: 'sqlite:' . dirname(__DIR__, 3) . '/runtime/app.sq3';
$scheme = (string) strtok($dsn, ':');

$drivers = [
    'sqlite' => ['Yiisoft\Db\Sqlite\Connection', 'Yiisoft\Db\Sqlite\Driver'],
    'mysql' => ['Yiisoft\Db\Mysql\Connection', 'Yiisoft\Db\Mysql\Driver'],
    'pgsql' => ['Yiisoft\Db\Pgsql\Connection', 'Yiisoft\Db\Pgsql\Driver'],
];

if (!isset($drivers[$scheme])) {
    throw new RuntimeException(
        sprintf('Unsupported DB_DSN scheme "%s". Supported: %s.', $scheme, implode(', ', array_keys($drivers))),
    );
}

[$connectionClass, $driverClass] = $drivers[$scheme];

if (!class_exists($driverClass)) {
    throw new RuntimeException(
        sprintf('DB_DSN uses "%s", install the driver with `composer require yiisoft/db-%s`.', $scheme, $scheme),
    );
}

return [
    ConnectionInterface::class => [
        'class' => $connectionClass,
        '__construct()' => [
            'driver' => new $driverClass(
                $dsn,
                (string) ($_ENV['DB_USERNAME'] ?? ''),
                (string) ($_ENV['DB_PASSWORD'] ?? ''),
            ),
        ],
    ],
];
