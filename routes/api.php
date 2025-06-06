<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IdeaWallController;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Public Ideas API endpoint
Route::get('/public-ideas', [IdeaWallController::class, 'index']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); 