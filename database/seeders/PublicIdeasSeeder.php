<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicIdeasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ideas = [
            [
                'id' => Str::uuid(),
                'title' => 'AI-Powered Task Manager',
                'problem' => 'Helps developers prioritize tasks using GPT and auto-scheduling.',
                'audience_tag' => 'Perfect for solo devs',
                'demand_score' => 92,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Social Media Analytics Dashboard',
                'problem' => 'Unifies all social metrics into one view for makers.',
                'audience_tag' => 'Great for indie hackers',
                'demand_score' => 71,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Local Business Directory SaaS',
                'problem' => 'Allows small businesses to manage bookings and payments.',
                'audience_tag' => 'Useful for freelancers & agencies',
                'demand_score' => 74,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Micro-SaaS Profit Tracker',
                'problem' => 'Tracks income, churn, and growth for solo founders.',
                'audience_tag' => 'Loved by early-stage builders',
                'demand_score' => 86,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'title' => 'Cold Email Sequence Generator',
                'problem' => 'Helps freelancers auto-generate email sequences for leads.',
                'audience_tag' => 'Optimized for freelance devs',
                'demand_score' => 78,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('public_ideas')->insert($ideas);
    }
}
