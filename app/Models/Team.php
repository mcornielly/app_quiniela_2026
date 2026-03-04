<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'country_id',
        'group_id',
        'name',
        'short_code',
        'type'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
