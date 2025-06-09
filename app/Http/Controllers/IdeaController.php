<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IdeaController extends Controller
{
    public function save(Request $request)
    {
        try {
            $request->validate([
                'idea_id' => 'required|uuid'
            ]);

            $userId = $request->attributes->get('user_id'); // From Supabase middleware
            $ideaId = $request->input('idea_id');

            // Check if idea exists in public_ideas
            $idea = DB::table('public_ideas')->where('id', $ideaId)->first();
            if (!$idea) {
                return response()->json(['error' => 'Idea not found'], 404);
            }

            // Save or get existing saved idea
            $savedIdea = DB::table('saved_ideas')
                ->where('user_id', $userId)
                ->where('idea_id', $ideaId)
                ->first();

            if (!$savedIdea) {
                DB::table('saved_ideas')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $userId,
                    'idea_id' => $ideaId,
                    'saved_at' => now()
                ]);

                Log::info('💾 IDEA SAVED:', ['user_id' => $userId, 'idea_id' => $ideaId]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Idea saved successfully',
                'saved' => !$savedIdea
            ]);

        } catch (\Exception $e) {
            Log::error('❌ SAVE IDEA ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to save idea'], 500);
        }
    }

    public function getSaved(Request $request)
    {
        try {
            $userId = $request->attributes->get('user_id');

            $savedIdeas = DB::table('saved_ideas')
                ->join('public_ideas', 'saved_ideas.idea_id', '=', 'public_ideas.id')
                ->where('saved_ideas.user_id', $userId)
                ->select([
                    'public_ideas.id',
                    'public_ideas.title',
                    'public_ideas.domain',
                    'public_ideas.description',
                    'public_ideas.difficulty',
                    'saved_ideas.saved_at'
                ])
                ->orderBy('saved_ideas.saved_at', 'desc')
                ->get();

            return response()->json([
                'ideas' => $savedIdeas,
                'count' => $savedIdeas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ GET SAVED IDEAS ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to fetch saved ideas'], 500);
        }
    }

    public function unsave(Request $request)
    {
        try {
            $request->validate([
                'idea_id' => 'required|uuid'
            ]);

            $userId = $request->attributes->get('user_id');
            $ideaId = $request->input('idea_id');

            $deleted = DB::table('saved_ideas')
                ->where('user_id', $userId)
                ->where('idea_id', $ideaId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => $deleted ? 'Idea unsaved successfully' : 'Idea was not saved',
                'removed' => $deleted > 0
            ]);

        } catch (\Exception $e) {
            Log::error('❌ UNSAVE IDEA ERROR:', [
                'message' => $e->getMessage(),
                'user_id' => $request->attributes->get('user_id')
            ]);
            return response()->json(['error' => 'Failed to unsave idea'], 500);
        }
    }
} 