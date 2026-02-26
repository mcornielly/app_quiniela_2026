<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PoolEntry;
use App\Models\MatchGame;
use App\Models\Team;

class Prediction extends Model
{
    protected $fillable = ['pool_entry_id','match_game_id','home_score','away_score','winner_team_id','points_awarded'];

    public function entry() { return $this->belongsTo(PoolEntry::class, 'pool_entry_id'); }
    public function match() { return $this->belongsTo(MatchGame::class, 'match_game_id'); }
    public function winner() { return $this->belongsTo(Team::class, 'winner_team_id'); }
}
