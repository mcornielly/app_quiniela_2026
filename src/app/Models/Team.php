<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'country_id',
        'group_id',
        'group_position',
        'name',
        'type',
        'shield_path',
        'api_team_id',
        'api_team_logo_url',
    ];

    public static function types(): array
    {
        return ['international', 'national', 'club'];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class)
            ->withPivot(['id', 'fifa_ranking', 'fair_play_points']);
    }

    public function tournamentEntries()
    {
        return $this->hasMany(TournamentTeam::class);
    }
}
