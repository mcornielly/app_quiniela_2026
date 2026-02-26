<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Prediction;

class PoolEntry extends Model
{
    protected $fillable = ['tournament_id','user_id','name','status','completion_percent','total_points','paid_at','payment_ref'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function tournament() { return $this->belongsTo(Tournament::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function predictions() { return $this->hasMany(Prediction::class); }
}
