<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    protected $fillable = [
        'pool_entry_id',
        'game_id',
        'home_score',
        'away_score'
    ];

    public function entry()
    {
        return $this->belongsTo(PoolEntry::class, 'pool_entry_id');
    }

    public function games()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
