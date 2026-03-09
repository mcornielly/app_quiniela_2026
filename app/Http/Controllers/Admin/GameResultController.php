<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Game;

use App\Services\Tournament\GroupStandingsService;
use App\Services\Tournament\BracketProgressionService;
use App\Services\Tournament\PredictionScoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameResultController extends Controller
{
    protected $standingsService;
    protected $bracketService;
    protected $scoringService;

    public function __construct(
        GroupStandingsService $standingsService,
        BracketProgressionService $bracketService,
        PredictionScoringService $scoringService
    ) {
        $this->standingsService = $standingsService;
        $this->bracketService = $bracketService;
        $this->scoringService = $scoringService;
    }

    /*
    |--------------------------------------------------------------------------
    | List games
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $games = Game::with(['homeTeam', 'awayTeam'])
            ->orderBy('match_number')
            ->get();

        return response()->json($games);
    }

    /*
    |--------------------------------------------------------------------------
    | Show game
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $game = Game::with(['homeTeam', 'awayTeam'])->findOrFail($id);

        return response()->json($game);
    }

    /*
    |--------------------------------------------------------------------------
    | Update score
    |--------------------------------------------------------------------------
    */

    public function updateScore(Request $request, Game $game)
    {
        $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($game, $request) {

            /*
            |--------------------------------------------------------------------------
            | 1. Update match score
            |--------------------------------------------------------------------------
            | Guardamos los goles del partido y marcamos el juego como finalizado.
            */

            $game->home_score = $request->home_score;
            $game->away_score = $request->away_score;
            $game->status = 'finished';

            /*
            |--------------------------------------------------------------------------
            | 2. Determine match winner
            |--------------------------------------------------------------------------
            | - Si gana el local → winner_team_id = home_team_id
            | - Si gana el visitante → winner_team_id = away_team_id
            | - Si empatan → winner_team_id = null
            */
            if ($request->home_score > $request->away_score) {

                $game->winner_team_id = $game->home_team_id;
                $game->result_type = 'home';

            } elseif ($request->away_score > $request->home_score) {

                $game->winner_team_id = $game->away_team_id;
                $game->result_type = 'away';

            } else {

                $game->winner_team_id = null;
                $game->result_type = 'draw';

            }


            $game->save();

            /*
            |--------------------------------------------------------------------------
            | 3. Update group standings (Group Stage)
            |--------------------------------------------------------------------------
            | Si el partido pertenece a fase de grupos se recalcula la tabla del grupo:
            |
            | Ejemplo:
            | Group A
            | Team | P | W | D | L | GF | GA | PTS
            |
            | Este servicio recalcula:
            | - puntos
            | - goles
            | - diferencia
            | - ranking
            */

            if ($game->isGroupStage()) {

                $groupId = $game->homeTeam->group_id;

                $this->standingsService->calculate($groupId);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Advance knockout bracket
            |--------------------------------------------------------------------------
            | Si el partido pertenece a eliminación directa:
            |
            | - Round of 16
            | - Quarterfinals
            | - Semifinals
            | - Final
            |
            | El ganador avanza automáticamente al siguiente partido.
            */

            if ($game->isKnockout()) {

                $this->bracketService->advance($game);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Score predictions
            |--------------------------------------------------------------------------
            | Se recalculan las predicciones de los usuarios:
            |
            | Ejemplo de puntos:
            | Exact score → 5 pts
            | Correct winner → 3 pts
            | Correct draw → 3 pts
            |
            | Este servicio actualiza:
            | - predictions
            | - pool_entry totals
            */
            $this->scoringService->scoreGame($game);

        });

        /*
        |--------------------------------------------------------------------------
        | 6. Response
        |--------------------------------------------------------------------------
        */

        // return response()->json([
        //     'message' => 'Game updated successfully',
        //     'game' => $game
        // ]);

        return redirect()->back()->with('success', 'Game updated successfully');
    }
}
