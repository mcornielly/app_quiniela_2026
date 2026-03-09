<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Prediction;
use App\Models\Concerns\FormatsDates;
use Carbon\Carbon;

class PoolEntry extends Model
{
    use FormatsDates;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'name',
        'status',
        'completion_percent',
        'total_points',
        'entry_fee',
        'paid_at',
        'payment_ref'
    ];

    protected $casts = [
        'paid_at' => 'date',
        'completion_percent' => 'integer',
        'total_points' => 'integer',
        'entry_fee' => 'decimal:2'
    ];

    public function tournament() { return $this->belongsTo(Tournament::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function predictions() { return $this->hasMany(Prediction::class); }

}
