<?php 
return [
    // 'driver' => env('SESSION_DRIVER', 'redis'), //file, cookie, database, memcached, redis, array
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/session'),
    'connection' => null,
    // 'connection' => 'session',//config/database.phpのredisに定義する名前、nullの場合、「default」設定で接続
    'table' => 'sessions',
    // 'table' => 1,
    'lottery' => [2, 100],
    'cookie' => 'laravel_session',
    'path' => '/',
    'domain' => null,
    'secure' => false,
];