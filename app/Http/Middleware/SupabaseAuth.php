<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;

class SupabaseAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized - No token provided'], 401);
        }

        try {
            $token = substr($authHeader, 7); // Remove 'Bearer ' prefix
            
            // Get Supabase JWT secret from environment
            $jwtSecret = env('SUPABASE_JWT_SECRET');
            
            if (!$jwtSecret) {
                return response()->json(['error' => 'JWT secret not configured'], 500);
            }
            
            // Decode the JWT token
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            
            // Add user info to request for use in controllers
            $request->merge([
                'user_id' => $decoded->sub ?? null,
                'user_email' => $decoded->email ?? null,
                'user_data' => $decoded
            ]);
            
            return $next($request);
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 401);
        }
    }
} 