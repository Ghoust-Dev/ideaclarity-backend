<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneratedPromptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('generated_prompts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('idea_id');
            $table->string('type'); // landing_page, tweet, etc
            $table->longText('content');
            $table->string('used_tool')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            
            // Add foreign key constraints if needed
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('idea_id')->references('id')->on('ideas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('generated_prompts');
    }
} 