<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentTeam extends Model
{
    protected $table = 'tournament_team';

    public $timestamps = false;

    protected $fillable = [
        'tournament_id',
        'team_id',
        'fifa_ranking',
        'fair_play_points',
    ];

    protected $casts = [
        'fifa_ranking' => 'integer',
        'fair_play_points' => 'integer',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
