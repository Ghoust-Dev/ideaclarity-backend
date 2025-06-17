<?php

namespace App\Http\Controllers;

use App\Models\GeneratedPrompt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\DeepSeekService;

class TweetGenerationController extends Controller
{
    protected $deepseekService;

    public function __construct(DeepSeekService $deepseekService)
    {
        $this->deepseekService = $deepseekService;
    }

    /**
     * Generate a tweet using GPT-4 based on idea details
     */
    public function generateTweet(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'idea_title' => 'required|string|max:255',
                'idea_description' => 'required|string|max:1000',
            ]);

            $ideaTitle = $request->input('idea_title');
            $ideaDescription = $request->input('idea_description');

            // Generate tweet using DeepSeek
            $tweet = $this->deepseekService->generateTweet($ideaTitle, $ideaDescription);

            return response()->json([
                'success' => true,
                'tweet' => $tweet,
                'message' => 'Tweet generated successfully with DeepSeek AI'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Tweet generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate tweet',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Post tweet to X/Twitter
     */
    public function postTweet(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tweet_content' => 'required|string|max:280',
                'prompt_id' => 'required|string',
            ]);

            $tweetContent = $request->input('tweet_content');
            $promptId = $request->input('prompt_id');

            // Twitter API v2 endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('TWITTER_BEARER_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post('https://api.twitter.com/2/tweets', [
                'text' => $tweetContent
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to post tweet',
                    'details' => $response->body()
                ], 500);
            }

            $responseData = $response->json();
            $tweetId = $responseData['data']['id'];

            // Update the generated prompt record with used_tool
            $generatedPrompt = GeneratedPrompt::find($promptId);
            if ($generatedPrompt) {
                $generatedPrompt->update(['used_tool' => 'twitter']);
            }

            return response()->json([
                'success' => true,
                'tweet_id' => $tweetId,
                'tweet_url' => "https://twitter.com/user/status/{$tweetId}",
                'message' => 'Tweet posted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Tweet posting failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to post tweet',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's tweet history
     */
    public function getTweetHistory(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('user_id');
            
            $tweets = GeneratedPrompt::where('user_id', $userId)
                ->where('type', 'tweet')
                ->orderBy('generated_at', 'desc')
                ->get(['id', 'content', 'used_tool', 'generated_at']);

            return response()->json([
                'success' => true,
                'tweets' => $tweets
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get tweet history: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to get tweet history',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 