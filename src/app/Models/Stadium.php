<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stadium extends Model
{
    protected $fillable = [
        'api_venue_id',
        'name',
        'city',
        'country',
        'address',
        'capacity',
        'surface',
        'image_url',
        'image_gallery',
    ];

    protected $casts = [
        'image_gallery' => 'array',
    ];

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
