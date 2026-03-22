<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [

        'pool_entry_id',
        'game_id',
        'predicted_home_team_id',
        'predicted_away_team_id',
        'predicted_winner_team_id',
        'home_score',
        'away_score',
        'points'

    ];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
        'points' => 'integer',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function poolEntry(): BelongsTo
    {
        return $this->belongsTo(PoolEntry::class);
    }

    public function predictedHomeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'predicted_home_team_id');
    }

    public function predictedAwayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'predicted_away_team_id');
    }

    public function predictedWinnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'predicted_winner_team_id');
    }
}
