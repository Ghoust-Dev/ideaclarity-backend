<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdeaWallController extends Controller
{
    /**
     * Get all public ideas ordered by demand score
     */
    public function index()
    {
        try {
            $ideas = DB::table('public_ideas')
                ->orderBy('demand_score', 'desc')
                ->get();

            return response()->json($ideas);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch public ideas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all public ideas (alias for index method)
     */
    public function getPublicIdeas()
    {
        return $this->index();
    }
}
