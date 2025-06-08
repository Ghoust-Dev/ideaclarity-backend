<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Re-enable CORS middleware for frontend communication
        $middleware->api(prepend: [
            \App\Http\Middleware\Cors::class,
        ]);
        
        // Register custom middleware aliases
        $middleware->alias([
            'supabase.auth' => \App\Http\Middleware\SupabaseAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
