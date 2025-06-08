<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
            $supabaseUrl = env('SUPABASE_URL', 'https://gyckxadiumjtdpxpsmbn.supabase.co');
            
            // DEBUG: Log setup
            Log::info('🔑 SUPABASE VERIFICATION:', [
                'token_length' => strlen($token),
                'supabase_url' => $supabaseUrl
            ]);
            
            // Verify token by calling Supabase user endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'apikey' => env('SUPABASE_ANON_KEY', ''),
                'Content-Type' => 'application/json'
            ])->timeout(10)->get($supabaseUrl . '/auth/v1/user');
            
            Log::info('📡 SUPABASE API CALL:', [
                'status' => $response->status(),
                'success' => $response->successful()
            ]);
            
            if (!$response->successful()) {
                Log::error('❌ SUPABASE VERIFICATION FAILED:', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json(['error' => 'Invalid or expired token'], 401);
            }
            
            $userData = $response->json();
            
            Log::info('✅ SUPABASE VERIFICATION SUCCESS:', [
                'user_id' => $userData['id'] ?? 'not_found',
                'user_email' => $userData['email'] ?? 'not_found'
            ]);
            
            // Add user info to request for use in controllers
            $request->merge([
                'user_id' => $userData['id'] ?? null,
                'user_email' => $userData['email'] ?? null,
                'user_data' => (object) $userData
            ]);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('❌ AUTH ERROR:', [
                'message' => $e->getMessage(),
                'class' => get_class($e)
            ]);
            return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 401);
        }
    }
} 