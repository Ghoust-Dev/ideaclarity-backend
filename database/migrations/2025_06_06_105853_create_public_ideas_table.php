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
        Schema::create('public_ideas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('problem');
            $table->string('audience_tag');
            $table->integer('demand_score');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_ideas');
    }
};
