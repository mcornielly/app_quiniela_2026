<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'year',
        'host_countries',
        'logo',
        'deadline_at',
        'status',
        'type'
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'host_countries' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function poolEntries()
    {
        return $this->hasMany(PoolEntry::class);
    }
}
