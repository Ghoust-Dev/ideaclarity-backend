<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChallengeController extends Controller
{
    public function start(Request $request)
    {
        try {
            $request->validate([
                'idea_id' => 'required|uuid'
            ]);

            $userId = $request->attributes->get('user_id');
            $ideaId = $request->input('idea_id');

            // Check if challenge already exists
            $existing = DB::table('validation_challenges')
                ->where('user_id', $userId)
                ->where('idea_id', $ideaId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Challenge already started for this idea',
                    'challenge' => $this->getChallengeData($existing, $userId, $ideaId)
                ]);
            }

            // Start new challenge
            $challengeId = \Illuminate\Support\Str::uuid();
            DB::table('validation_challenges')->insert([
                'id' => $challengeId,
                'user_id' => $userId,
                'idea_id' => $ideaId,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $challenge = DB::table('validation_challenges')->where('id', $challengeId)->first();

            Log::info('🚀 48H CHALLENGE STARTED:', [
                'user_id' => $userId,
                'idea_id' => $ideaId,
                'challenge_id' => $challengeId
            ]);

            return response()->json([
                'success' => true,
                'message' => '48-hour challenge started!',
                'challenge' => $this->getChallengeData($challenge, $userId, $ideaId)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ START CHALLENGE ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to start challenge'], 500);
        }
    }

    public function status($ideaId, Request $request)
    {
        try {
            $userId = $request->attributes->get('user_id');

            $challenge = DB::table('validation_challenges')
                ->where('user_id', $userId)
                ->where('idea_id', $ideaId)
                ->first();

            if (!$challenge) {
                return response()->json([
                    'challenge_started' => false,
                    'message' => 'No challenge started for this idea'
                ]);
            }

            return response()->json([
                'challenge_started' => true,
                'challenge' => $this->getChallengeData($challenge, $userId, $ideaId)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ GET CHALLENGE STATUS ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to fetch challenge status'], 500);
        }
    }

    private function getChallengeData($challenge, $userId, $ideaId)
    {
        $startedAt = Carbon::parse($challenge->started_at);
        $now = Carbon::now();
        $endTime = $startedAt->copy()->addHours(48);
        
        $timeRemaining = $now->lt($endTime) ? $now->diffInSeconds($endTime) : 0;
        $isExpired = $timeRemaining === 0;

        // Get validation progress
        $completedSteps = DB::table('validation_progress')
            ->where('user_id', $userId)
            ->where('idea_id', $ideaId)
            ->where('completed', true)
            ->count();

        $isCompleted = $completedSteps >= 3 && !$isExpired;

        // Auto-complete challenge if criteria met
        if ($isCompleted && !$challenge->completed_at) {
            DB::table('validation_challenges')
                ->where('id', $challenge->id)
                ->update([
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);
            
            $challenge->completed_at = now();
            
            Log::info('🏆 48H CHALLENGE COMPLETED:', [
                'user_id' => $userId,
                'idea_id' => $ideaId,
                'completed_steps' => $completedSteps
            ]);
        }

        return [
            'id' => $challenge->id,
            'started_at' => $challenge->started_at,
            'completed_at' => $challenge->completed_at,
            'time_remaining_seconds' => $timeRemaining,
            'is_expired' => $isExpired,
            'is_completed' => (bool) $challenge->completed_at,
            'completed_steps' => $completedSteps,
            'required_steps' => 3,
            'success' => $isCompleted
        ];
    }
} 