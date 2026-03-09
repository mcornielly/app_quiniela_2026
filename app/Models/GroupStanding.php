<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupStanding extends Model
{
    protected $fillable = [

        'tournament_id',
        'group_id',
        'team_id',

        'played',
        'wins',
        'draws',
        'losses',

        'gf',
        'ga',
        'gd',

        'points',
        'position'

    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
