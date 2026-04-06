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

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhereHas('tournament', function ($t) use ($search) {
                  $t->where('name', 'like', "%{$search}%");
              });
        });
    }
}

