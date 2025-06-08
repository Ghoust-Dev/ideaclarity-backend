<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'IdeaClarity API is running!',
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version()
    ]);
});

Route::get('/up', function () {
    return response('OK', 200);
});

Route::get('/debug', function () {
    return response()->json([
        'environment' => app()->environment(),
        'debug' => config('app.debug'),
        'url' => config('app.url'),
        'timezone' => config('app.timezone'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'port' => $_ENV['PORT'] ?? 'not set',
        'app_key_set' => !empty(config('app.key')),
        'database_configured' => !empty(config('database.connections.pgsql.host'))
    ]);
});

// Simple diagnostic endpoint
Route::get('/diagnostic', function () {
    return response()->json([
        'status' => 'Laravel is working',
        'php_version' => PHP_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
    ]);
});

// Test database connection
Route::get('/test-db', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return response()->json([
            'database' => 'connected',
            'connection' => config('database.default'),
            'host' => config('database.connections.pgsql.host'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'database' => 'failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});
