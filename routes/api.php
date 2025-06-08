<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IdeaWallController;
use App\Http\Controllers\TweetGenerationController;
use App\Http\Controllers\PromptController;

// Simplest possible health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

// More detailed health check
Route::get('/health/detailed', function () {
    try {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'IdeaClarity API',
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Simple ping endpoint
Route::get('/ping', function () {
    return response()->json(['message' => 'pong'], 200);
});

// Public endpoints (no auth required)
Route::get('/public-ideas', [IdeaWallController::class, 'getPublicIdeas']);

// Test endpoints (for debugging)
Route::get('/test/idea/{idea_id}', function ($idea_id) {
    $idea = \Illuminate\Support\Facades\DB::table('public_ideas')->where('id', $idea_id)->first();
    if (!$idea) {
        return response()->json(['error' => 'Idea not found', 'idea_id' => $idea_id], 404);
    }
    return response()->json([
        'message' => 'Idea found',
        'idea_id' => $idea_id,
        'idea' => $idea,
        'timestamp' => now()
    ]);
});

Route::get('/test/auth', function () {
    return response()->json([
        'message' => 'Auth test endpoint - no authentication required',
        'timestamp' => now(),
        'backend_status' => 'working'
    ]);
});

// Debug environment variables
Route::get('/debug/env', function () {
    return response()->json([
        'app_env' => app()->environment(),
        'app_key_set' => !empty(config('app.key')),
        'db_connection' => config('database.default'),
        'supabase_jwt_secret_set' => !empty(env('SUPABASE_JWT_SECRET')),
        'openai_key_set' => !empty(env('OPENAI_API_KEY')),
        'jwt_secret_length' => strlen(env('SUPABASE_JWT_SECRET', '')),
        'openai_key_length' => strlen(env('OPENAI_API_KEY', '')),
        'timestamp' => now()
    ]);
});

// Debug database tables
Route::get('/debug/tables', function () {
    try {
        $publicIdeasCount = \Illuminate\Support\Facades\DB::table('public_ideas')->count();
        
        // Check if competitor_results table exists
        $competitorTableExists = \Illuminate\Support\Facades\Schema::hasTable('competitor_results');
        
        return response()->json([
            'public_ideas_count' => $publicIdeasCount,
            'competitor_results_table_exists' => $competitorTableExists,
            'database_connected' => true,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Database error: ' . $e->getMessage(),
            'database_connected' => false,
            'timestamp' => now()
        ], 500);
    }
});

// Protected endpoints (require Supabase authentication)
Route::middleware(['supabase.auth'])->group(function () {
    // User info endpoint
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user_id' => $request->attributes->get('user_id'),
            'email' => $request->attributes->get('user_email'),
            'authenticated' => true
        ]);
    });
    
    // Debug auth middleware
    Route::get('/debug/auth', function (Request $request) {
        return response()->json([
            'message' => 'Auth middleware working',
            'user_id' => $request->attributes->get('user_id'),
            'user_email' => $request->attributes->get('user_email'),
            'user_data_keys' => array_keys($request->attributes->get('user_data', [])),
            'timestamp' => now()
        ]);
    });
    
    // Debug JWT token details
    Route::get('/debug/jwt', function (Request $request) {
        $authHeader = $request->header('Authorization');
        return response()->json([
            'auth_header_present' => !empty($authHeader),
            'auth_header_format' => $authHeader ? substr($authHeader, 0, 20) . '...' : null,
            'bearer_format' => str_starts_with($authHeader ?? '', 'Bearer '),
            'token_length' => $authHeader ? strlen(substr($authHeader, 7)) : 0,
            'jwt_secret_set' => !empty(env('SUPABASE_JWT_SECRET')),
            'timestamp' => now()
        ]);
    });
    
    // AI Prompt Generation Routes
    Route::prefix('prompts')->group(function () {
        Route::post('/tweet/{idea_id}', [PromptController::class, 'generateTweet']);
        Route::post('/competitors/{idea_id}', [PromptController::class, 'generateCompetitors']);
        Route::post('/landing-page/{idea_id}', [PromptController::class, 'regenerateLandingPrompt']);
        
        // GET routes for testing (these should work in browser)
        Route::get('/test/competitors/{idea_id}', function ($idea_id, Request $request) {
            return response()->json([
                'message' => 'Competitors endpoint test',
                'idea_id' => $idea_id,
                'user_id' => $request->attributes->get('user_id'),
                'user_email' => $request->attributes->get('user_email'),
                'note' => 'Use POST method to actually generate competitors',
                'timestamp' => now()
            ]);
        });
    });
    
    // Tweet generation routes (legacy - may remove later)
    Route::post('/generate-tweet', [TweetGenerationController::class, 'generateTweet']);
    Route::post('/post-tweet', [TweetGenerationController::class, 'postTweet']);
    Route::get('/tweet-history', [TweetGenerationController::class, 'getTweetHistory']);
}); 