<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;
use App\Models\WorldCupGroup;

class Team extends Model
{
    protected $fillable = ['tournament_id','world_cup_group_id','name','short_code','flag_url','is_placeholder'];

    public function tournament() { return $this->belongsTo(Tournament::class); }
    public function group() { return $this->belongsTo(WorldCupGroup::class, 'world_cup_group_id'); }
}
