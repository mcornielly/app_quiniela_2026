<?php

namespace App\Models;

use App\Models\Concerns\FormatsDates;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use FormatsDates;

    protected $fillable = [

        'tournament_id',
        'match_number',
        'api_fixture_id',

        // teams
        'home_team_id',
        'away_team_id',

        // slot references (1A, W74, etc)
        'home_slot',
        'away_slot',

        // match info
        'stage',
        'venue',
        'stadium_id',
        'match_date',
        'match_time',

        // results
        'home_score',
        'away_score',
        'winner_team_id',
        'result_type',

        'status'
    ];

    protected $casts = [
        'match_date' => 'date',
        'match_time' => 'string',
        'home_score' => 'integer',
        'away_score' => 'integer',
    ];

    protected $appends = [
        'match_date_input',
        'match_time_input',
        'group_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function winnerTeam()
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function stadium()
    {
        return $this->belongsTo(Stadium::class);
    }

    public function predictions()
    {
        return $this->hasMany(Prediction::class);
    }

    public function histories()
    {
        return $this->hasMany(GameHistory::class);
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            // If it's a single character, assume it's a group-specific filter (e.g., 'B')
            if (strlen($search) === 1) {
                $search = strtoupper($search);
                $q->whereHas('homeTeam.group', function ($g) use ($search) {
                    $g->where('name', $search);
                })->orWhereHas('awayTeam.group', function ($g) use ($search) {
                    $g->where('name', $search);
                });
            } else {
                // Broad search
                $q->where('match_number', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%")
                  ->orWhere('stage', 'like', "%{$search}%")
                  ->orWhereHas('homeTeam', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('awayTeam', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tournament', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%");
                  });
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isFinished()
    {
        return $this->status === 'finished';
    }

    public function isGroupStage()
    {
        return $this->stage === 'group';
    }

    public function isKnockout()
    {
        return $this->stage !== 'group';
    }

    /*
    |--------------------------------------------------------------------------
    | Determine winner
    |--------------------------------------------------------------------------
    */

    public function winner()
    {
        if (!$this->hasResult()) {
            return null;
        }

        if ($this->home_score > $this->away_score) {
            return $this->homeTeam;
        }

        if ($this->away_score > $this->home_score) {
            return $this->awayTeam;
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Determine if match is a draw
    |--------------------------------------------------------------------------
    */
    public function isDraw()
    {
        return $this->hasResult() && $this->home_score === $this->away_score;
    }
    /*
    |--------------------------------------------------------------------------
    | Determine loser (used for Third Place match)
    |--------------------------------------------------------------------------
    */

    public function loser()
    {
        if (!$this->isFinished()) {
            return null;
        }

        if ($this->home_score < $this->away_score) {
            return $this->homeTeam;
        }

        if ($this->away_score < $this->home_score) {
            return $this->awayTeam;
        }

        return null;
    }

    public function loserTeamId()
    {
        $loser = $this->loser();

        return $loser?->id;
    }

    /*
    |--------------------------------------------------------------------------
    | Check if match has result (used to determine if predictions can be scored)
    |--------------------------------------------------------------------------*/
    public function hasResult()
    {
        return !is_null($this->home_score) && !is_null($this->away_score);
    }


    /*
    |--------------------------------------------------------------------------
    | Check if winner can advance in bracket (used to determine if next match can be resolved)
    |--------------------------------------------------------------------------*/
    public function canAdvanceBracket()
    {
        return $this->isFinished() && !$this->isDraw();
    }

    public function score()
    {
        if (!$this->hasResult()) {
            return null;
        }

        return "{$this->home_score} - {$this->away_score}";
    }

    public function hasStarted()
    {
        $kickoff = $this->match_date->copy()
            ->setTimeFromTimeString($this->match_time);

        return now()->greaterThanOrEqualTo($kickoff);
    }

    public function getMatchDateInputAttribute()
    {
        return $this->match_date?->format('Y-m-d');
    }

    public function getMatchTimeInputAttribute()
    {
        return $this->match_time;
    }

    public function getGroupNameAttribute()
    {
        if ($this->stage !== 'group') {
            return '-';
        }

        return $this->homeTeam?->group?->name;
    }
}
