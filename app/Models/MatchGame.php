<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;
use App\Models\WorldCupGroup;
use App\Models\Team;

class MatchGame extends Model
{
    protected $fillable = [
        'tournament_id',
        'world_cup_group_id',
        'group_code',
        'city',
        'home_team_id',
        'away_team_id',
        'home_ref',
        'away_ref',
        'starts_at',
        'timezone',
        'stage',
        'home_score',
        'away_score',
        'status',
        'finished_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function tournament() { return $this->belongsTo(Tournament::class); }
    public function group() { return $this->belongsTo(WorldCupGroup::class, 'world_cup_group_id'); }

    public function homeTeam() { return $this->belongsTo(Team::class, 'home_team_id'); }
    public function awayTeam() { return $this->belongsTo(Team::class, 'away_team_id'); }
}
