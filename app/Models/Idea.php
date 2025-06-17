<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Idea extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'public_ideas';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'problem',
        'problem_summary',
        'description',
        'audience_tag',
        'demand_score',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'string',
        'demand_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the problem summary attribute (alias for problem field)
     */
    public function getProblemSummaryAttribute()
    {
        return $this->problem;
    }

    /**
     * Get the description attribute (alias for problem field)
     */
    public function getDescriptionAttribute()
    {
        return $this->problem;
    }
} 