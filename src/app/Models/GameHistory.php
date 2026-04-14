<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameHistory extends Model
{
    protected $fillable = [
        'tournament_id',
        'game_id',
        'home_team_id',
        'away_team_id',
        'api_fixture_id',
        'match_number',
        'stage',
        'group_name',
        'status',
        'status_short',
        'status_label',
        'venue',
        'match_date',
        'match_time',
        'home_score',
        'away_score',
        'home_possession',
        'away_possession',
        'goals_feed',
        'events',
        'statistics',
        'lineups',
        'players',
        'payload',
        'collected_at',
    ];

    protected $casts = [
        'match_date' => 'date',
        'match_time' => 'string',
        'goals_feed' => 'array',
        'events' => 'array',
        'statistics' => 'array',
        'lineups' => 'array',
        'players' => 'array',
        'payload' => 'array',
        'collected_at' => 'datetime',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
