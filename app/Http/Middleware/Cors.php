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
            // Add your actual frontend domain here
        ];

        $origin = $request->header('Origin');

        $response = $next($request);

        // Set CORS headers
        if (in_array($origin, $allowedOrigins)) {
            $response->header('Access-Control-Allow-Origin', $origin);
        } else {
            // For development, allow all origins (remove in production)
            $response->header('Access-Control-Allow-Origin', '*');
        }
        
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->header('Access-Control-Allow-Credentials', 'true');

        // Handle preflight requests
        if ($request->method() === 'OPTIONS') {
            $response->setStatusCode(200);
        }

        return $response;
    }
} 