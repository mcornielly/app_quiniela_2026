<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'flag_path'
    ];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
