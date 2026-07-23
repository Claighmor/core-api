<?php

use Fleetbase\Support\Utils;

/*
 * Driver-adaptive connection map. The connection NAMES stay 'mysql' and
 * 'sandbox' (hardcoded across the codebase), but the underlying driver is
 * chosen from the DATABASE_URL scheme: mysql:// -> MySQL, pgsql://|postgres://
 * -> PostgreSQL. On Postgres there is ONE physical database (Supabase) with two
 * schemas: 'public' (main) and 'sandbox'.
 */

$host     = env('DB_HOST', '127.0.0.1');
$port     = null;
$database = env('DB_DATABASE', 'fleetbase');
$username = env('DB_USERNAME', 'fleetbase');
$password = env('DB_PASSWORD', '');
$driver   = env('DB_CONNECTION', 'mysql');
$sslmode  = env('PGSQL_SSLMODE');

$databaseUrl = getenv('DATABASE_URL');
if (!empty($databaseUrl)) {
    $url    = Utils::parseUrl($databaseUrl);
    $scheme = $url['scheme'] ?? $driver;

    if (in_array($scheme, ['pgsql', 'postgres', 'postgresql'])) {
        $driver = 'pgsql';
    } elseif (in_array($scheme, ['mysql', 'mariadb'])) {
        $driver = 'mysql';
    }

    $host     = $url['host'];
    $username = $url['user'] ?? $username;
    if (isset($url['port'])) {
        $port = $url['port'];
    }
    if (isset($url['pass'])) {
        $password = $url['pass'];
    }
    if (isset($url['path'])) {
        $database = ltrim($url['path'], '/');
    }
    if (isset($url['query'])) {
        parse_str($url['query'], $q);
        if (isset($q['sslmode'])) {
            $sslmode = $q['sslmode'];
        }
    }
}

/*
    |--------------------------------------------------------------------------
    | PostgreSQL (Supabase) connections
    |--------------------------------------------------------------------------
    | Single database, two schemas. search_path scopes each named connection.
    | sslmode defaults to 'require' (Supabase); override via ?sslmode= or env.
    */
if ($driver === 'pgsql') {
    $pg_port    = $port ?: env('DB_PORT', '5432');
    $pg_sslmode = $sslmode ?: env('PGSQL_SSLMODE', 'require');
    $pg_options = [
        // Persistent connections are avoided with Supavisor poolers.
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_TIMEOUT    => 5,
    ];

    $pg = [
        'driver'         => 'pgsql',
        'host'           => $host,
        'port'           => $pg_port,
        'database'       => $database,
        'username'       => $username,
        'password'       => $password,
        'charset'        => 'utf8',
        'prefix'         => '',
        'prefix_indexes' => true,
        'sslmode'        => $pg_sslmode,
        'options'        => $pg_options,
    ];

    return [
        'mysql' => array_merge($pg, [
            'search_path' => env('PGSQL_SEARCH_PATH', 'public'),
            'pool'        => [
                'size' => env('DB_CONNECTION_POOL_SIZE', 25),
            ],
        ]),

        'sandbox' => array_merge($pg, [
            'search_path' => env('PGSQL_SANDBOX_SEARCH_PATH', 'sandbox'),
        ]),
    ];
}

/*
    |--------------------------------------------------------------------------
    | MySQL connections (default)
    |--------------------------------------------------------------------------
    */
$mysql_options = [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_TIMEOUT    => 5,
];

if (env('APP_ENV') === 'local') {
    $mysql_options[PDO::ATTR_EMULATE_PREPARES] = true;
}

if (extension_loaded('pdo_mysql')) {
    if (env('MYSQL_ATTR_SSL_CA')) {
        $mysql_options[PDO::MYSQL_ATTR_SSL_CA] = env('MYSQL_ATTR_SSL_CA');
    }
    if (env('MYSQL_ATTR_SSL_CERT')) {
        $mysql_options[PDO::MYSQL_ATTR_SSL_CERT] = env('MYSQL_ATTR_SSL_CERT');
    }
    if (env('MYSQL_ATTR_SSL_KEY')) {
        $mysql_options[PDO::MYSQL_ATTR_SSL_KEY] = env('MYSQL_ATTR_SSL_KEY');
    }
    // Setting default SSL verification behavior based on environment
    $mysql_options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = env('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', env('APP_ENV') === 'production');
}

return [
    'mysql' => [
        'driver'    => 'mysql',
        'host'      => $host,
        'port'      => $port ?: env('DB_PORT', '3306'),
        'database'  => $database,
        'username'  => $username,
        'password'  => $password,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'strict'    => true,
        'engine'    => null,
        'options'   => $mysql_options,
        'pool'      => [
            'size' => env('DB_CONNECTION_POOL_SIZE', 25),
        ],
    ],

    'sandbox' => [
        'driver'    => 'mysql',
        'host'      => $host,
        'port'      => env('SANDBOX_DB_PORT', $port ?: env('DB_PORT', '3306')),
        'database'  => $database . '_sandbox',
        'username'  => $username,
        'password'  => $password,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'strict'    => true,
        'engine'    => null,
        'options'   => $mysql_options,
    ],
];
