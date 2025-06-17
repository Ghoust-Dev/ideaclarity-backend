<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Idea;
use App\Services\DeepSeekService;

class PromptController extends Controller
{
    protected $deepseekService;

    public function __construct(DeepSeekService $deepseekService)
    {
        $this->deepseekService = $deepseekService;
    }

    public function generateTweet($ideaId)
    {
        try {
            $idea = Idea::findOrFail($ideaId);
            
            // Check cache first
            $cacheKey = "tweet_prompt_{$ideaId}";
            $cachedTweet = Cache::get($cacheKey);
            
            if ($cachedTweet) {
                return response()->json([
                    'tweet' => $cachedTweet,
                    'cached' => true
                ]);
            }

            // Generate new tweet using DeepSeek
            $tweet = $this->deepseekService->generateTweet($idea->title, $idea->problem_summary ?? $idea->description);
            
            // Cache for 1 hour
            Cache::put($cacheKey, $tweet, 3600);
            
            return response()->json([
                'tweet' => $tweet,
                'cached' => false
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tweet generation failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to generate tweet',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateCompetitors($ideaId)
    {
        try {
            $idea = Idea::findOrFail($ideaId);
            
            // Check cache first
            $cacheKey = "competitors_prompt_{$ideaId}";
            $cachedCompetitors = Cache::get($cacheKey);
            
            if ($cachedCompetitors) {
                return response()->json([
                    'competitors' => $cachedCompetitors,
                    'cached' => true
                ]);
            }

            // Generate new competitor analysis using DeepSeek
            $competitors = $this->deepseekService->generateCompetitors($idea->title, $idea->problem_summary ?? $idea->description);
            
            // Cache for 2 hours
            Cache::put($cacheKey, $competitors, 7200);
            
            return response()->json([
                'competitors' => $competitors,
                'cached' => false
            ]);
            
        } catch (\Exception $e) {
            Log::error('Competitor analysis failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to generate competitor analysis',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateLandingPagePrompt($ideaId)
    {
        try {
            $idea = Idea::findOrFail($ideaId);
            
            // Check cache first
            $cacheKey = "landing_page_prompt_{$ideaId}";
            $cachedPrompt = Cache::get($cacheKey);
            
            if ($cachedPrompt) {
                return response()->json([
                    'prompt' => $cachedPrompt,
                    'cached' => true
                ]);
            }

            // Generate new landing page content using DeepSeek
            $landingPageContent = $this->deepseekService->generateLandingPage($idea->title, $idea->problem_summary ?? $idea->description);
            
            // Cache for 2 hours
            Cache::put($cacheKey, $landingPageContent, 7200);
            
            return response()->json([
                'prompt' => $landingPageContent,
                'cached' => false
            ]);
            
        } catch (\Exception $e) {
            Log::error('Landing page generation failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to generate landing page content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateSurvey($ideaId)
    {
        try {
            $idea = Idea::findOrFail($ideaId);
            
            // Check cache first
            $cacheKey = "survey_prompt_{$ideaId}";
            $cachedSurvey = Cache::get($cacheKey);
            
            if ($cachedSurvey) {
                return response()->json([
                    'survey' => $cachedSurvey,
                    'cached' => true
                ]);
            }

            // Generate new survey using DeepSeek
            $survey = $this->deepseekService->generateSurvey($idea->title, $idea->problem_summary ?? $idea->description);
            
            // Cache for 2 hours
            Cache::put($cacheKey, $survey, 7200);
            
            return response()->json([
                'survey' => $survey,
                'cached' => false
            ]);
            
        } catch (\Exception $e) {
            Log::error('Survey generation failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to generate survey',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 