<?php

declare(strict_types=1);

$environment = getenv('APP_ENV') ?: 'production';
$database = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

if ($database === false || $database === '') {
    throw new RuntimeException('DB_DATABASE is not configured.');
}

if ($username === false || $username === '') {
    throw new RuntimeException('DB_USERNAME is not configured.');
}

if ($environment !== 'local' && ($password === false || $password === '')) {
    throw new RuntimeException('DB_PASSWORD must be configured outside local development.');
}

return [
    'driver'   => getenv('DB_DRIVER') ?: 'mysql',
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'database' => $database,
    'username' => $username,
    'password' => $password === false ? '' : $password,
    'charset'  => getenv('DB_CHARSET') ?: 'utf8mb4',
];
