<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Team extends Model
{
    protected $fillable = [
        'country_id',
        'group_id',
        'group_position',
        'name',
        'type'
    ];

    public static function types(): array
    {
        $column = DB::selectOne("
            SHOW COLUMNS
            FROM teams
            WHERE Field = 'type'
        ");

        preg_match('/^enum\((.*)\)$/', $column->Type, $matches);

        return array_map(
            fn($value) => trim($value, "'"),
            explode(',', $matches[1])
        );
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
