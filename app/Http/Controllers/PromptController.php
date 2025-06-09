<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeneratedPrompt;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromptController extends Controller
{
    public function generateTweet($idea_id, Request $request)
    {
        try {
            // Get the idea from public_ideas table
            $idea = DB::table('public_ideas')->where('id', $idea_id)->first();
            
            if (!$idea) {
                return response()->json(['error' => 'Idea not found'], 404);
            }

            // Check if we have a recent tweet for this idea
            $existingTweet = GeneratedPrompt::where('idea_id', $idea_id)
                ->where('type', 'tweet')
                ->where('generated_at', '>', now()->subHours(1)) // Cache for 1 hour
                ->first();

            if ($existingTweet) {
                return response()->json([
                    'tweet' => $existingTweet->content,
                    'cached' => true
                ]);
            }

            // Generate new tweet using OpenAI
            $prompt = "Generate an engaging Twitter post for this startup idea:

Title: {$idea->title}
Problem: {$idea->problem_summary}
Description: {$idea->description}

Requirements:
- Keep it under 280 characters
- Make it engaging and ask for feedback
- Include relevant hashtags like #startup #buildinpublic
- Sound authentic and personal
- Ask the community what they think

Generate only the tweet text, nothing else.";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 100,
                'temperature' => 0.8,
            ]);

            $tweetContent = trim($response->choices[0]->message->content);

            // Save to database using Supabase user ID
            GeneratedPrompt::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $request->attributes->get('user_id'), // From Supabase middleware
                'idea_id' => $idea_id,
                'type' => 'tweet',
                'content' => $tweetContent,
                'used_tool' => 'gpt-4o-mini',
                'generated_at' => now(),
            ]);

            return response()->json([
                'tweet' => $tweetContent,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            // If OpenAI fails (quota/billing), provide mock tweet for testing
            if (strpos($e->getMessage(), 'quota') !== false || strpos($e->getMessage(), 'billing') !== false) {
                Log::info('💡 PROVIDING MOCK TWEET DATA (OpenAI quota exceeded)');
                
                $mockTweet = "I'm working on {$idea->title} - an innovative solution for modern productivity challenges. What do you think? Would this solve a problem for you? #startup #buildinpublic #AI";

                // Save mock tweet to database
                GeneratedPrompt::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $request->attributes->get('user_id'),
                    'idea_id' => $idea_id,
                    'type' => 'tweet',
                    'content' => $mockTweet,
                    'used_tool' => 'mock-fallback',
                    'generated_at' => now(),
                ]);

                return response()->json([
                    'tweet' => $mockTweet,
                    'cached' => false,
                    'mock' => true,
                    'message' => 'OpenAI quota exceeded. Showing sample tweet for testing.'
                ]);
            }

            return response()->json(['error' => 'Failed to generate tweet: ' . $e->getMessage()], 500);
        }
    }

    public function generateCompetitors($idea_id, Request $request)
    {
        try {
            // DEBUG: Log that we've entered the method
            Log::info('🏁 COMPETITORS START:', [
                'idea_id' => $idea_id,
                'idea_id_type' => gettype($idea_id),
                'user_id' => $request->attributes->get('user_id'),
                'user_email' => $request->attributes->get('user_email')
            ]);

            // Try to cast idea_id to integer first, then fallback to string
            $numericId = is_numeric($idea_id) ? (int)$idea_id : null;
            
            Log::info('🔢 ID CONVERSION:', [
                'original_id' => $idea_id,
                'numeric_id' => $numericId,
                'is_numeric' => is_numeric($idea_id)
            ]);

            // Get the idea from public_ideas table with proper type handling
            $idea = null;
            if ($numericId) {
                $idea = DB::table('public_ideas')->where('id', $numericId)->first();
                Log::info('📝 TRIED NUMERIC QUERY');
            }
            
            // If numeric failed, try as string
            if (!$idea) {
                $idea = DB::table('public_ideas')->where('id', (string)$idea_id)->first();
                Log::info('📝 TRIED STRING QUERY');
            }
            
            if (!$idea) {
                Log::warning('❌ IDEA NOT FOUND:', ['idea_id' => $idea_id, 'numeric_id' => $numericId]);
                return response()->json(['error' => 'Idea not found'], 404);
            }

            Log::info('✅ IDEA FOUND:', [
                'idea_title' => $idea->title ?? 'no_title',
                'idea_problem' => $idea->problem_summary ?? 'no_problem',
                'found_id' => $idea->id ?? 'no_id',
                'found_id_type' => gettype($idea->id ?? null)
            ]);

            // Check for existing competitors (cache for 24 hours)
            $existingCompetitors = DB::table('competitor_results')
                ->where('idea_id', $idea->id) // Use the actual ID from the found idea
                ->where('created_at', '>', now()->subHours(24))
                ->first();

            if ($existingCompetitors) {
                Log::info('📦 RETURNING CACHED COMPETITORS');
                return response()->json([
                    'competitors' => json_decode($existingCompetitors->competitors_data),
                    'cached' => true
                ]);
            }

            Log::info('🤖 CALLING OPENAI for competitors');

            // Generate competitors using OpenAI
            $prompt = "List 2-3 SaaS competitors similar to the following idea:

Idea Name: {$idea->title}
Problem Solved: " . ($idea->problem_summary ?? $idea->description ?? "Innovative solution for " . $idea->title) . "
Target Audience: developers and tech professionals

For each competitor, return:
- Name
- Monthly price or pricing model
- Main strengths (bullet points)
- Weaknesses or limitations (bullet points)
- Website link (if known)

Respond in clean JSON format like this:
[
  {
    \"name\": \"CompetitorName\",
    \"price\": \"$19/mo\",
    \"strengths\": [\"Feature 1\", \"Feature 2\"],
    \"weaknesses\": [\"Limitation 1\", \"Limitation 2\"],
    \"link\": \"https://example.com\"
  }
]";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

            Log::info('✅ OPENAI RESPONSE RECEIVED');

            $competitorsJson = trim($response->choices[0]->message->content);
            $competitors = json_decode($competitorsJson, true);

            Log::info('📊 PARSING RESULTS:', [
                'json_length' => strlen($competitorsJson),
                'parsed_successfully' => !is_null($competitors),
                'competitors_count' => is_array($competitors) ? count($competitors) : 0
            ]);

            if (!$competitors) {
                Log::error('❌ JSON PARSING FAILED:', [
                    'raw_response' => substr($competitorsJson, 0, 500) . '...'
                ]);
                throw new \Exception('Invalid JSON response from AI');
            }

            Log::info('💾 SAVING TO DATABASE');

            // Save to database using the actual idea ID and Supabase user ID
            DB::table('competitor_results')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'idea_id' => $idea->id, // Use the actual ID from the found idea
                'user_id' => $request->attributes->get('user_id'), // From Supabase middleware
                'competitors_data' => $competitorsJson,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('✅ COMPETITORS GENERATION COMPLETE');

            return response()->json([
                'competitors' => $competitors,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            Log::error('❌ COMPETITORS ERROR:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // If OpenAI fails (quota/billing), provide mock data for testing
            if (strpos($e->getMessage(), 'quota') !== false || strpos($e->getMessage(), 'billing') !== false) {
                Log::info('💡 PROVIDING MOCK COMPETITORS DATA (OpenAI quota exceeded)');
                
                $mockCompetitors = [
                    [
                        'name' => 'Asana',
                        'price' => '$10.99/mo',
                        'strengths' => ['Great team collaboration', 'Visual project boards', 'Extensive integrations'],
                        'weaknesses' => ['Can be overwhelming for simple tasks', 'Limited AI features'],
                        'link' => 'https://asana.com'
                    ],
                    [
                        'name' => 'Todoist',
                        'price' => '$4/mo',
                        'strengths' => ['Clean interface', 'Natural language processing', 'Cross-platform sync'],
                        'weaknesses' => ['Limited team features', 'No built-in time tracking'],
                        'link' => 'https://todoist.com'
                    ],
                    [
                        'name' => 'ClickUp',
                        'price' => '$7/mo',
                        'strengths' => ['All-in-one workspace', 'Customizable views', 'Time tracking'],
                        'weaknesses' => ['Steep learning curve', 'Can be slow with large datasets'],
                        'link' => 'https://clickup.com'
                    ]
                ];

                // Save mock data to database
                DB::table('competitor_results')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'idea_id' => $idea->id,
                    'user_id' => $request->attributes->get('user_id'),
                    'competitors_data' => json_encode($mockCompetitors),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'competitors' => $mockCompetitors,
                    'cached' => false,
                    'mock' => true,
                    'message' => 'OpenAI quota exceeded. Showing sample competitors for testing.'
                ]);
            }

            return response()->json(['error' => 'Failed to generate competitors: ' . $e->getMessage()], 500);
        }
    }

    public function regenerateLandingPrompt($idea_id, Request $request)
    {
        try {
            // Get the idea from public_ideas table
            $idea = DB::table('public_ideas')->where('id', $idea_id)->first();
            
            if (!$idea) {
                return response()->json(['error' => 'Idea not found'], 404);
            }

            $heroTitle = $request->input('hero_title', $idea->title . ' - Coming Soon');
            $subtitle = $request->input('subtitle', $idea->problem_summary ?? $idea->description ?? 'Innovative solution');
            $ctaText = $request->input('cta_text', 'Join Waitlist');

            // Generate landing page prompt using OpenAI
            $prompt = "Create a detailed landing page prompt for AI tools like v0.dev, Lovable, or Bolt for this SaaS idea:

Title: {$idea->title}
Problem: " . ($idea->problem_summary ?? $idea->description ?? "Innovative solution for " . $idea->title) . "
Hero Title: {$heroTitle}
Subtitle: {$subtitle}
CTA: {$ctaText}

Generate a comprehensive prompt that includes:
- Hero section details
- Problem/solution explanation  
- 3 key features with descriptions
- Social proof suggestions
- Design requirements (modern, clean, developer-friendly)
- Color scheme and styling preferences

Make it detailed and actionable for AI code generation tools.";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.8,
            ]);

            $generatedPrompt = trim($response->choices[0]->message->content);

            // Save to database using Supabase user ID
            GeneratedPrompt::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $request->attributes->get('user_id'), // From Supabase middleware
                'idea_id' => $idea_id,
                'type' => 'landing_page',
                'content' => $generatedPrompt,
                'used_tool' => 'gpt-4o-mini',
                'generated_at' => now(),
            ]);

            return response()->json([
                'prompt' => $generatedPrompt,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            // If OpenAI fails (quota/billing), provide mock landing page prompt for testing
            if (strpos($e->getMessage(), 'quota') !== false || strpos($e->getMessage(), 'billing') !== false) {
                Log::info('💡 PROVIDING MOCK LANDING PAGE PROMPT (OpenAI quota exceeded)');
                
                $mockPrompt = "Design a modern SaaS landing page for a product called \"{$idea->title}\".

Hero Section:
- Main headline: \"{$heroTitle}\"
- Subheadline: \"{$subtitle}\"
- Primary CTA button: \"{$ctaText}\"

This tool solves important problems for developers and tech professionals.

Page Structure:
- Strong hero section with the above headline and CTA
- Problem/solution section explaining the pain solved
- 3 key feature blocks with icons:
  * Smart automation features
  * Intuitive user interface
  * Seamless integrations
- Social proof section (testimonials or user count)
- Footer with Terms, Privacy, and Contact links

Design Requirements:
- Developer-friendly design with dark theme option
- Minimal and clean UI with modern gradients
- Subtle animations and micro-interactions
- Responsive layout for mobile and desktop
- Professional typography and readable layout
- Use blue/teal accent colors for CTAs and highlights
- Clean code structure optimized for performance";

                // Save mock prompt to database
                GeneratedPrompt::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $request->attributes->get('user_id'),
                    'idea_id' => $idea_id,
                    'type' => 'landing_page',
                    'content' => $mockPrompt,
                    'used_tool' => 'mock-fallback',
                    'generated_at' => now(),
                ]);

                return response()->json([
                    'prompt' => $mockPrompt,
                    'cached' => false,
                    'mock' => true,
                    'message' => 'OpenAI quota exceeded. Showing sample landing page prompt for testing.'
                ]);
            }

            return response()->json(['error' => 'Failed to generate landing prompt: ' . $e->getMessage()], 500);
        }
    }
} 