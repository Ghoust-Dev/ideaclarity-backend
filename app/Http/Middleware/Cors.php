<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        // Allow requests from localhost and production domains
        $allowedOrigins = [
            'http://localhost:3000',
            'https://localhost:3000',
            'https://ideaclarity-m9.vercel.app',
            'https://ideaclarity-frontend.vercel.app',
            'https://ideaclarity-backend-production.up.railway.app',
            // Add your actual frontend domain here
        ];

        $origin = $request->header('Origin');

        $response = $next($request);

        // Set CORS headers - be more permissive for now
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->header('Access-Control-Allow-Credentials', 'false');

        // Handle preflight requests
        if ($request->method() === 'OPTIONS') {
            $response->setStatusCode(200);
            return $response;
        }

        return $response;
    }
} 