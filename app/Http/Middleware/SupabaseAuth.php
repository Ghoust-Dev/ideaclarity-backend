<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SupabaseAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        
        // DEBUG: Log auth header
        Log::info('🔍 SUPABASE AUTH DEBUG:', [
            'auth_header_present' => !empty($authHeader),
            'auth_header_format' => $authHeader ? substr($authHeader, 0, 20) . '...' : null,
            'bearer_format' => str_starts_with($authHeader ?? '', 'Bearer ')
        ]);
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            Log::warning('❌ AUTH: No valid Bearer token provided');
            return response()->json(['error' => 'Unauthorized - No token provided'], 401);
        }

        try {
            $token = substr($authHeader, 7); // Remove 'Bearer ' prefix
            
            // Get Supabase JWT secret from environment
            $jwtSecret = env('SUPABASE_JWT_SECRET');
            
            // DEBUG: Log JWT secret status
            Log::info('🔑 JWT SECRET DEBUG:', [
                'jwt_secret_set' => !empty($jwtSecret),
                'jwt_secret_length' => strlen($jwtSecret ?? ''),
                'token_length' => strlen($token)
            ]);
            
            if (!$jwtSecret) {
                Log::error('❌ JWT secret not configured');
                return response()->json(['error' => 'JWT secret not configured'], 500);
            }
            
            // Decode the JWT token
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            
            // DEBUG: Log successful decode
            Log::info('✅ JWT DECODE SUCCESS:', [
                'user_id' => $decoded->sub ?? 'not_found',
                'user_email' => $decoded->email ?? 'not_found',
                'token_exp' => $decoded->exp ?? 'not_found',
                'token_iat' => $decoded->iat ?? 'not_found'
            ]);
            
            // Add user info to request for use in controllers
            $request->merge([
                'user_id' => $decoded->sub ?? null,
                'user_email' => $decoded->email ?? null,
                'user_data' => $decoded
            ]);
            
            return $next($request);
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::warning('⏰ JWT EXPIRED:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Firebase\JWT\BeforeValidException $e) {
            Log::warning('📅 JWT NOT YET VALID:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Token not yet valid'], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Log::error('🔒 JWT SIGNATURE INVALID:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid token signature'], 401);
        } catch (\Exception $e) {
            Log::error('❌ JWT DECODE ERROR:', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 401);
        }
    }
} 