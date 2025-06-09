<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidationController extends Controller
{
    public function markStep(Request $request)
    {
        try {
            $request->validate([
                'idea_id' => 'required|uuid',
                'step' => 'required|string|in:landing,tweet,competitor,discussion,survey'
            ]);

            $userId = $request->attributes->get('user_id');
            $ideaId = $request->input('idea_id');
            $step = $request->input('step');

            // Check if step already exists
            $existing = DB::table('validation_progress')
                ->where('user_id', $userId)
                ->where('idea_id', $ideaId)
                ->where('step', $step)
                ->first();

            if (!$existing) {
                // Create new progress entry
                DB::table('validation_progress')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $userId,
                    'idea_id' => $ideaId,
                    'step' => $step,
                    'completed' => true,
                    'completed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('✅ VALIDATION STEP MARKED:', [
                    'user_id' => $userId,
                    'idea_id' => $ideaId,
                    'step' => $step
                ]);
            } else if (!$existing->completed) {
                // Update existing entry
                DB::table('validation_progress')
                    ->where('id', $existing->id)
                    ->update([
                        'completed' => true,
                        'completed_at' => now(),
                        'updated_at' => now()
                    ]);

                Log::info('🔄 VALIDATION STEP UPDATED:', [
                    'user_id' => $userId,
                    'idea_id' => $ideaId,
                    'step' => $step
                ]);
            }

            // Get updated progress
            $progress = $this->getProgressData($userId, $ideaId);

            return response()->json([
                'success' => true,
                'message' => 'Step marked as completed',
                'progress' => $progress
            ]);

        } catch (\Exception $e) {
            Log::error('❌ MARK STEP ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to mark step'], 500);
        }
    }

    public function getProgress($ideaId, Request $request)
    {
        try {
            $userId = $request->attributes->get('user_id');
            $progress = $this->getProgressData($userId, $ideaId);

            return response()->json([
                'progress' => $progress
            ]);

        } catch (\Exception $e) {
            Log::error('❌ GET PROGRESS ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to fetch progress'], 500);
        }
    }

    private function getProgressData($userId, $ideaId)
    {
        $steps = ['landing', 'tweet', 'competitor', 'discussion', 'survey'];
        $progress = [];

        $completed = DB::table('validation_progress')
            ->where('user_id', $userId)
            ->where('idea_id', $ideaId)
            ->where('completed', true)
            ->get()
            ->keyBy('step');

        foreach ($steps as $step) {
            $progress[$step] = [
                'completed' => isset($completed[$step]),
                'completed_at' => $completed[$step]->completed_at ?? null
            ];
        }

        $totalSteps = count($steps);
        $completedSteps = count(array_filter($progress, fn($p) => $p['completed']));

        return [
            'steps' => $progress,
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'completion_percentage' => round(($completedSteps / $totalSteps) * 100)
        ];
    }
} 