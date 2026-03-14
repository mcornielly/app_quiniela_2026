<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [

        'pool_entry_id',
        'game_id',
        'home_score',
        'away_score',
        'points'

    ];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
        'points' => 'integer',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function poolEntry(): BelongsTo
    {
        return $this->belongsTo(PoolEntry::class);
    }
}
