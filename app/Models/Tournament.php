<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\WorldCupGroup;
use App\Models\Team;
use App\Models\MatchGame;
use App\Models\PoolEntry;

class Tournament extends Model
{
    protected $fillable = ['name','year','deadline_at','status'];

    protected $casts = [
        'deadline_at' => 'datetime',
    ];

    public function groups() { return $this->hasMany(WorldCupGroup::class); }
    public function teams() { return $this->hasMany(Team::class); }
    public function matches() { return $this->hasMany(MatchGame::class); }
    public function poolEntries() { return $this->hasMany(PoolEntry::class); }
}
