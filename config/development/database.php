<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */
    'default' => env('DB_CONNECTION', 'write'),
    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */
    'connections' => [
        'write' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '47.104.162.228'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'drink_alcohol'),
            'username' => env('DB_USERNAME', 'drink_alcohol'),
            'password' => env('DB_PASSWORD', 'y3b8wLPppGPbTwx8'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'strict' => env('DB_STRICT_MODE', false),
            'engine' => env('DB_ENGINE', null),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
        ],
        'read' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '47.104.162.228'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'drink_alcohol'),
            'username' => env('DB_USERNAME', 'drink_alcohol'),
            'password' => env('DB_PASSWORD', 'y3b8wLPppGPbTwx8'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'strict' => env('DB_STRICT_MODE', false),
            'engine' => env('DB_ENGINE', null),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */
    'migrations' => 'migrations',
    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */
    'redis' => [
        'client' => env('REDIS_CLIENT', 'predis'),
        'cluster' => env('REDIS_CLUSTER', true),
        'default' => [
            'host' => env('REDIS_HOST', '47.104.162.228'),
            'password' => env('REDIS_PASSWORD', ''),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
            'read_write_timeout' => env('REDIS_READ_WRITE_TIMEOUT', 60),
        ],
        'cache' => [
            'host' => env('REDIS_HOST', '47.104.162.228'),
            'password' => env('REDIS_PASSWORD', ''),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
            'read_write_timeout' => env('REDIS_READ_WRITE_TIMEOUT', 60),
        ],
    ],
];