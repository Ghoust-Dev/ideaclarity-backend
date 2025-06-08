<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedPrompt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'idea_id',
        'type',
        'content',
        'used_tool',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the generated prompt.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Note: Idea relationship commented out until Idea model is created
    // public function idea()
    // {
    //     return $this->belongsTo(Idea::class);
    // }
} 