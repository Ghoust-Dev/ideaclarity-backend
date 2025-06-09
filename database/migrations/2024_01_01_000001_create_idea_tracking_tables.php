<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Table: saved_ideas - When a user saves an idea from Discover
        Schema::create('saved_ideas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('user_id');
            $table->uuid('idea_id');
            $table->timestamp('saved_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->unique(['user_id', 'idea_id']);
            
            $table->index(['user_id']);
            $table->index(['idea_id']);
        });

        // Table: validation_progress - Tracks per-user, per-idea tool completion
        Schema::create('validation_progress', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('user_id');
            $table->uuid('idea_id');
            $table->string('step')->check("step IN ('landing', 'tweet', 'competitor', 'discussion', 'survey')");
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idea_id', 'step']);
            
            $table->index(['user_id', 'idea_id']);
        });

        // Table: validation_challenges - For the 48h challenge tracking
        Schema::create('validation_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('user_id');
            $table->uuid('idea_id');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'idea_id']);
        });

        // Table: roadmap_progress - Tracks roadmap steps per user per idea
        Schema::create('roadmap_progress', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('user_id');
            $table->uuid('idea_id');
            $table->string('step_name');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idea_id', 'step_name']);
            
            $table->index(['user_id', 'idea_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('roadmap_progress');
        Schema::dropIfExists('validation_challenges');
        Schema::dropIfExists('validation_progress');
        Schema::dropIfExists('saved_ideas');
    }
}; 