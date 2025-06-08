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

// Public ideas endpoint
Route::get('/public-ideas', [IdeaWallController::class, 'getPublicIdeas']);

// User authentication endpoints
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Tweet generation routes
Route::post('/generate-tweet', [TweetGenerationController::class, 'generateTweet']);
Route::post('/post-tweet', [TweetGenerationController::class, 'postTweet']);
Route::get('/tweet-history', [TweetGenerationController::class, 'getTweetHistory']);

// AI Prompt Generation Routes
Route::prefix('prompts')->group(function () {
    Route::post('/tweet/{idea_id}', [PromptController::class, 'generateTweet']);
    Route::post('/competitors/{idea_id}', [PromptController::class, 'generateCompetitors']);
    Route::post('/landing-page/{idea_id}', [PromptController::class, 'regenerateLandingPrompt']);
}); 