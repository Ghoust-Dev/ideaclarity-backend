<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IdeaWallController;

// Health check endpoint for Railway
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'IdeaClarity API'
    ]);
});

// Public ideas endpoint
Route::get('/public-ideas', [IdeaWallController::class, 'index']);

// User authentication endpoints
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
}); 