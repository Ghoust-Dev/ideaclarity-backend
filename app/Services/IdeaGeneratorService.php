<?php

namespace App\Services;

use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

class IdeaGeneratorService
{
    protected $deepseekService;

    public function __construct(DeepSeekService $deepseekService)
    {
        $this->deepseekService = $deepseekService;
    }

    /**
     * Generate SaaS ideas based on user skills and interests
     */
    public function generateIdeas($skills = [], $interests = [], $count = 6)
    {
        try {
            $skillsString = is_array($skills) ? implode(', ', $skills) : $skills;
            $interestsString = is_array($interests) ? implode(', ', $interests) : $interests;

            // Use DeepSeek to generate SaaS ideas
            $ideas = $this->deepseekService->generateSaasIdeas($skillsString, $interestsString, $count);

            // Transform the ideas to match expected format
            return array_map(function ($idea) {
                return [
                    'title' => $idea['title'] ?? 'Untitled Idea',
                    'description' => $idea['description'] ?? 'No description available',
                    'category' => $idea['category'] ?? 'General',
                    'target_market' => $idea['target_market'] ?? 'General market',
                    'revenue_model' => $idea['revenue_model'] ?? 'Subscription',
                    'difficulty_level' => $idea['difficulty_level'] ?? 'Medium',
                    'estimated_market_size' => $idea['estimated_market_size'] ?? 'Medium',
                    'demand_score' => rand(60, 95), // Random score between 60-95
                ];
            }, $ideas);

        } catch (\Exception $e) {
            Log::error('Idea generation failed: ' . $e->getMessage());
            
            // Return fallback ideas if DeepSeek fails
            return $this->getFallbackIdeas($count);
        }
    }

    /**
     * Get fallback ideas when AI generation fails
     */
    private function getFallbackIdeas($count = 6)
    {
        $fallbackIdeas = [
            [
                'title' => 'AI-Powered Code Review Assistant',
                'description' => 'Automated code review tool that provides intelligent suggestions and catches bugs before deployment.',
                'category' => 'Developer Tools',
                'target_market' => 'Software development teams',
                'revenue_model' => 'Subscription ($29/month per developer)',
                'difficulty_level' => 'High',
                'estimated_market_size' => 'Large',
                'demand_score' => 85,
            ],
            [
                'title' => 'Smart Meeting Scheduler',
                'description' => 'AI-driven scheduling tool that finds optimal meeting times across time zones and calendars.',
                'category' => 'Productivity',
                'target_market' => 'Remote teams and businesses',
                'revenue_model' => 'Freemium ($15/month pro)',
                'difficulty_level' => 'Medium',
                'estimated_market_size' => 'Large',
                'demand_score' => 78,
            ],
            [
                'title' => 'Customer Feedback Analytics',
                'description' => 'Analyze customer feedback from multiple channels using sentiment analysis and trend detection.',
                'category' => 'Analytics',
                'target_market' => 'SaaS companies and e-commerce',
                'revenue_model' => 'Subscription ($99/month)',
                'difficulty_level' => 'Medium',
                'estimated_market_size' => 'Medium',
                'demand_score' => 72,
            ],
            [
                'title' => 'No-Code API Builder',
                'description' => 'Visual tool for creating and managing APIs without writing code, with automatic documentation.',
                'category' => 'Developer Tools',
                'target_market' => 'Non-technical entrepreneurs',
                'revenue_model' => 'Usage-based pricing',
                'difficulty_level' => 'High',
                'estimated_market_size' => 'Medium',
                'demand_score' => 81,
            ],
            [
                'title' => 'Social Media Content Planner',
                'description' => 'AI-assisted content planning and scheduling across multiple social media platforms.',
                'category' => 'Marketing',
                'target_market' => 'Small businesses and creators',
                'revenue_model' => 'Subscription ($19/month)',
                'difficulty_level' => 'Medium',
                'estimated_market_size' => 'Large',
                'demand_score' => 76,
            ],
            [
                'title' => 'Team Performance Dashboard',
                'description' => 'Real-time analytics dashboard for tracking team productivity and project progress.',
                'category' => 'Analytics',
                'target_market' => 'Project managers and team leads',
                'revenue_model' => 'Per-seat pricing ($12/user/month)',
                'difficulty_level' => 'Medium',
                'estimated_market_size' => 'Medium',
                'demand_score' => 69,
            ],
        ];

        return array_slice($fallbackIdeas, 0, $count);
    }

    /**
     * Process and filter generated ideas
     *
     * @param array $ideas
     * @return array
     */
    public function processIdeas(array $ideas): array
    {
        // TODO: Implement idea processing logic
        return $ideas;
    }
} 