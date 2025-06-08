<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competitor_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('idea_id');
            $table->uuid('user_id')->nullable();
            $table->json('competitors_data'); // Store the competitor analysis as JSON
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('idea_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_results');
    }
};
