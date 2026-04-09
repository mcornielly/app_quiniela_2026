<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = [
        'api_player_id',
        'team_id',
        'name',
        'firstname',
        'lastname',
        'age',
        'birth_date',
        'nationality',
        'height',
        'weight',
        'position',
        'number',
        'photo_url',
        'local_photo_path',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
