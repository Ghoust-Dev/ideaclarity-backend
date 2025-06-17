<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('DEEPSEEK_API_KEY');
        $this->baseUrl = 'https://api.deepseek.com/v1';
    }

    /**
     * Make a chat completion request to DeepSeek API
     */
    private function chatCompletion($messages, $maxTokens = 1000)
    {
        if (!$this->apiKey) {
            throw new \Exception('DeepSeek API key not configured');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
                'stream' => false
            ]);

            if ($response->failed()) {
                Log::error('DeepSeek API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('DeepSeek API request failed: ' . $response->body());
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';
        } catch (\Exception $e) {
            Log::error('DeepSeek API error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a tweet for an idea
     */
    public function generateTweet($ideaTitle, $ideaDescription)
    {
        $prompt = "Create an engaging tweet for a SaaS idea called '{$ideaTitle}'. Description: {$ideaDescription}. 
        Make it compelling, include relevant hashtags, and keep it under 280 characters. Focus on the problem it solves and value proposition.";

        $messages = [
            ['role' => 'system', 'content' => 'You are a social media expert specializing in SaaS marketing tweets.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        return $this->chatCompletion($messages, 100);
    }

    /**
     * Generate competitor analysis for an idea
     */
    public function generateCompetitors($ideaTitle, $ideaDescription)
    {
        $prompt = "Analyze competitors for the SaaS idea '{$ideaTitle}': {$ideaDescription}. 
        Return a JSON array of 3-5 competitors with: name, description, website, strengths, weaknesses, pricing_model.
        Focus on direct and indirect competitors in the same market space.";

        $messages = [
            ['role' => 'system', 'content' => 'You are a market research analyst. Always respond with valid JSON only.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->chatCompletion($messages, 800);
        
        try {
            return json_decode($response, true) ?: [];
        } catch (\Exception $e) {
            Log::error('Failed to parse competitor JSON: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate survey questions for an idea
     */
    public function generateSurvey($ideaTitle, $ideaDescription)
    {
        $prompt = "Create a market validation survey for '{$ideaTitle}': {$ideaDescription}. 
        Return JSON with: title, description, and questions array. Each question should have: question, type (multiple_choice, text, rating), and options (if applicable).
        Include 5-8 questions covering problem validation, solution fit, pricing sensitivity, and demographics.";

        $messages = [
            ['role' => 'system', 'content' => 'You are a product manager expert in market validation surveys. Always respond with valid JSON only.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->chatCompletion($messages, 1000);
        
        try {
            return json_decode($response, true) ?: [];
        } catch (\Exception $e) {
            Log::error('Failed to parse survey JSON: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate landing page content for an idea
     */
    public function generateLandingPage($ideaTitle, $ideaDescription)
    {
        $prompt = "Create landing page content for '{$ideaTitle}': {$ideaDescription}. 
        Return JSON with: headline, subheadline, hero_description, features (array of 3-4 features with title and description), 
        benefits (array of 3 key benefits), cta_text, and social_proof_text.
        Make it conversion-focused and compelling.";

        $messages = [
            ['role' => 'system', 'content' => 'You are a conversion copywriter specializing in SaaS landing pages. Always respond with valid JSON only.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->chatCompletion($messages, 1200);
        
        try {
            return json_decode($response, true) ?: [];
        } catch (\Exception $e) {
            Log::error('Failed to parse landing page JSON: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate SaaS ideas based on skills and interests
     */
    public function generateSaasIdeas($skills, $interests, $count = 6)
    {
        $prompt = "Generate {$count} innovative SaaS ideas based on these skills: {$skills} and interests: {$interests}. 
        Return JSON array with each idea having: title, description, category, target_market, revenue_model, difficulty_level, estimated_market_size.
        Focus on solving real problems and leveraging the given skills.";

        $messages = [
            ['role' => 'system', 'content' => 'You are a startup advisor and SaaS expert. Always respond with valid JSON only.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->chatCompletion($messages, 1500);
        
        try {
            return json_decode($response, true) ?: [];
        } catch (\Exception $e) {
            Log::error('Failed to parse SaaS ideas JSON: ' . $e->getMessage());
            return [];
        }
    }
} 