<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
        'tournament_starts_at',
        'participation_closes_at',
        'exact_score_points',
        'correct_result_points',
        'unpaid_after_window_action',
        'active',
    ];

    protected $casts = [
        'tournament_starts_at' => 'datetime',
        'participation_closes_at' => 'datetime',
        'exact_score_points' => 'integer',
        'correct_result_points' => 'integer',
        'active' => 'boolean',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
}

