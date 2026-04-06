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

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%")
              ->orWhere('country', 'like', "%{$search}%");
        });
    }
}
