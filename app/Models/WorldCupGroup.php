<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\MatchGame;

class WorldCupGroup extends Model
{
    protected $fillable = ['tournament_id','code','name'];

    public function tournament() { return $this->belongsTo(Tournament::class); }
    public function teams() { return $this->hasMany(Team::class); }
    public function matches() { return $this->hasMany(MatchGame::class); }
}
