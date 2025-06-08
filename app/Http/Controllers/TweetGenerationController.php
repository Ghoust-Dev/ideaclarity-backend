<?php

namespace App\Http\Controllers;

use App\Models\GeneratedPrompt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TweetGenerationController extends Controller
{
    /**
     * Generate a tweet using GPT-4 based on idea details
     */
    public function generateTweet(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'idea_title' => 'required|string|max:255',
                'idea_description' => 'required|string',
                'user_id' => 'required|string',
                'idea_id' => 'required|string',
            ]);

            $ideaTitle = $request->input('idea_title');
            $ideaDescription = $request->input('idea_description');

            // Create GPT-4 prompt for tweet generation
            $prompt = "Generate an engaging tweet for a startup idea called '{$ideaTitle}'. 
                      Description: {$ideaDescription}
                      
                      The tweet should:
                      - Be under 280 characters
                      - Ask for feedback from the community
                      - Include relevant hashtags like #startup #buildinpublic #feedback
                      - Be conversational and genuine
                      - Encourage engagement
                      
                      Return only the tweet text, nothing else.";

            // Call OpenAI GPT-4 API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a social media expert who creates engaging tweets for startup founders.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to generate tweet',
                    'details' => $response->body()
                ], 500);
            }

            $responseData = $response->json();
            $generatedTweet = trim($responseData['choices'][0]['message']['content']);

            // Save the generated tweet to database
            $generatedPrompt = GeneratedPrompt::create([
                'id' => Str::uuid(),
                'user_id' => $request->input('user_id'),
                'idea_id' => $request->input('idea_id'),
                'type' => 'tweet',
                'content' => $generatedTweet,
                'generated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'tweet' => $generatedTweet,
                'prompt_id' => $generatedPrompt->id,
                'character_count' => strlen($generatedTweet)
            ]);

        } catch (\Exception $e) {
            Log::error('Tweet generation failed: ' . $e->getMessage());
            
            return response()->json([
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
            $userId = $request->input('user_id');
            
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