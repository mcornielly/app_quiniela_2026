<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    protected $fillable = [
        'tournament_id',
        'name'
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        if (strlen($search) === 1) {
            return $query->where('name', strtoupper($search));
        }

        return $query->where('name', 'like', "%" . strtoupper($search) . "%");
    }
}
