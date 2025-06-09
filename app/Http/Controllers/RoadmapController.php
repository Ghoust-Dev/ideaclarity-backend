<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoadmapController extends Controller
{
    public function update(Request $request)
    {
        try {
            $request->validate([
                'idea_id' => 'required|uuid',
                'step_name' => 'required|string',
                'completed' => 'boolean',
                'notes' => 'nullable|string',
                'link' => 'nullable|string'
            ]);

            $userId = $request->attributes->get('user_id');
            $ideaId = $request->input('idea_id');
            $stepName = $request->input('step_name');
            $completed = $request->input('completed', false);
            $notes = $request->input('notes');
            $link = $request->input('link');

            // Check if step already exists
            $existing = DB::table('roadmap_progress')
                ->where('user_id', $userId)
                ->where('idea_id', $ideaId)
                ->where('step_name', $stepName)
                ->first();

            if ($existing) {
                // Update existing step
                DB::table('roadmap_progress')
                    ->where('id', $existing->id)
                    ->update([
                        'completed' => $completed,
                        'completed_at' => $completed ? now() : null,
                        'notes' => $notes,
                        'link' => $link,
                        'updated_at' => now()
                    ]);

                Log::info('🔄 ROADMAP STEP UPDATED:', [
                    'user_id' => $userId,
                    'idea_id' => $ideaId,
                    'step_name' => $stepName,
                    'completed' => $completed
                ]);
            } else {
                // Create new step
                DB::table('roadmap_progress')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $userId,
                    'idea_id' => $ideaId,
                    'step_name' => $stepName,
                    'completed' => $completed,
                    'completed_at' => $completed ? now() : null,
                    'notes' => $notes,
                    'link' => $link,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('✅ ROADMAP STEP CREATED:', [
                    'user_id' => $userId,
                    'idea_id' => $ideaId,
                    'step_name' => $stepName,
                    'completed' => $completed
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Roadmap step updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ UPDATE ROADMAP ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to update roadmap step'], 500);
        }
    }

    public function get($ideaId, Request $request)
    {
        try {
            $userId = $request->attributes->get('user_id');

            $roadmapSteps = DB::table('roadmap_progress')
                ->where('user_id', $userId)
                ->where('idea_id', $ideaId)
                ->orderBy('created_at', 'asc')
                ->get();

            $completedSteps = $roadmapSteps->where('completed', true)->count();
            $totalSteps = $roadmapSteps->count();

            return response()->json([
                'roadmap' => $roadmapSteps,
                'summary' => [
                    'total_steps' => $totalSteps,
                    'completed_steps' => $completedSteps,
                    'completion_percentage' => $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ GET ROADMAP ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to fetch roadmap'], 500);
        }
    }
} 