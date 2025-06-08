<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
            $supabaseUrl = env('SUPABASE_URL', 'https://yjtcgxkadiuimjtdpxpsmbn.supabase.co');
            
            // DEBUG: Log JWT secret status
            Log::info('🔑 JWT SECRET DEBUG:', [
                'jwt_secret_set' => !empty($jwtSecret),
                'jwt_secret_length' => strlen($jwtSecret ?? ''),
                'token_length' => strlen($token),
                'supabase_url' => $supabaseUrl
            ]);
            
            if (!$jwtSecret) {
                Log::error('❌ JWT secret not configured');
                return response()->json(['error' => 'JWT secret not configured'], 500);
            }
            
            // Try RS256 verification first (for Supabase access tokens)
            try {
                $decoded = $this->verifyWithRS256($token, $supabaseUrl);
                Log::info('✅ JWT DECODE SUCCESS (RS256):', [
                    'user_id' => $decoded->sub ?? 'not_found',
                    'user_email' => $decoded->email ?? 'not_found',
                    'token_exp' => $decoded->exp ?? 'not_found',
                    'token_iat' => $decoded->iat ?? 'not_found'
                ]);
            } catch (\Exception $rsaError) {
                Log::info('⚠️ RS256 failed, trying HS256:', ['error' => $rsaError->getMessage()]);
                
                // Fallback to HS256 verification
                $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
                Log::info('✅ JWT DECODE SUCCESS (HS256):', [
                    'user_id' => $decoded->sub ?? 'not_found',
                    'user_email' => $decoded->email ?? 'not_found',
                    'token_exp' => $decoded->exp ?? 'not_found',
                    'token_iat' => $decoded->iat ?? 'not_found'
                ]);
            }
            
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
    
    private function verifyWithRS256($token, $supabaseUrl)
    {
        // Get JWKS from Supabase
        $jwksUrl = rtrim($supabaseUrl, '/') . '/auth/v1/jwks';
        
        // Cache JWKS for 1 hour
        $jwks = Cache::remember('supabase_jwks', 3600, function () use ($jwksUrl) {
            Log::info('📡 Fetching JWKS from:', ['url' => $jwksUrl]);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $jwksData = file_get_contents($jwksUrl, false, $context);
            if ($jwksData === false) {
                throw new \Exception('Failed to fetch JWKS');
            }
            
            return json_decode($jwksData, true);
        });
        
        if (empty($jwks['keys'])) {
            throw new \Exception('No keys found in JWKS');
        }
        
        // Parse token header to get key ID
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new \Exception('Invalid token format');
        }
        
        $header = json_decode(base64_decode(str_pad(strtr($tokenParts[0], '-_', '+/'), strlen($tokenParts[0]) % 4, '=', STR_PAD_RIGHT)), true);
        $kid = $header['kid'] ?? null;
        
        // Find the matching key
        $publicKey = null;
        foreach ($jwks['keys'] as $key) {
            if ($key['kid'] === $kid) {
                // Convert JWK to PEM format
                $publicKey = $this->jwkToPem($key);
                break;
            }
        }
        
        if (!$publicKey) {
            throw new \Exception('Public key not found for kid: ' . $kid);
        }
        
        // Verify with RS256
        return JWT::decode($token, new Key($publicKey, 'RS256'));
    }
    
    private function jwkToPem($jwk)
    {
        if ($jwk['kty'] !== 'RSA') {
            throw new \Exception('Only RSA keys are supported');
        }
        
        $n = $this->base64UrlDecode($jwk['n']);
        $e = $this->base64UrlDecode($jwk['e']);
        
        // Create RSA public key in DER format
        $der = pack('C*', 0x30, 0x82) . pack('n', strlen($n) + strlen($e) + 22) .
               pack('C*', 0x30, 0x0d, 0x06, 0x09, 0x2a, 0x86, 0x48, 0x86, 0xf7, 0x0d, 0x01, 0x01, 0x01, 0x05, 0x00, 0x03, 0x82) .
               pack('n', strlen($n) + strlen($e) + 5) .
               pack('C*', 0x00, 0x30, 0x82) .
               pack('n', strlen($n) + strlen($e) + 2) .
               pack('C*', 0x02, 0x82) .
               pack('n', strlen($n)) . $n .
               pack('C*', 0x02) . chr(strlen($e)) . $e;
        
        return "-----BEGIN PUBLIC KEY-----\n" . 
               chunk_split(base64_encode($der), 64, "\n") . 
               "-----END PUBLIC KEY-----\n";
    }
    
    private function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
} 