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

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            // If it's a single letter, assume it's a specific group search (e.g., 'A')
            if (strlen($search) === 1) {
                $search = strtoupper($search);
                $q->whereHas('group', function ($g) use ($search) {
                    $g->where('name', $search);
                });
            } else {
                // Wide search for team name or group name
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('group', function ($g) use ($search) {
                      $g->where('name', 'like', "%{$search}%");
                  });
            }
        });
    }
}
