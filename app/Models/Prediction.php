<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    protected $fillable = [

        'pool_entry_id',
        'game_id',
        'home_score',
        'away_score',
        'points'

    ];

    public function entry()
    {
        return $this->belongsTo(PoolEntry::class, 'pool_entry_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
