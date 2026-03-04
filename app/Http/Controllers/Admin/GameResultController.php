<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Game;

use App\Services\Tournament\GroupStandingsService;
use App\Services\Tournament\BracketProgressionService;
use App\Services\Tournament\PredictionScoringService;

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

    public function updateScore(Request $request, $id)
    {
        $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
        ]);

        $game = Game::findOrFail($id);

        $game->home_score = $request->home_score;
        $game->away_score = $request->away_score;

        $game->status = 'finished';

        /*
        |--------------------------------------------------------------------------
        | Determine winner
        |--------------------------------------------------------------------------
        */

        if ($request->home_score > $request->away_score) {
            $game->winner_team_id = $game->home_team_id;
        }

        if ($request->away_score > $request->home_score) {
            $game->winner_team_id = $game->away_team_id;
        }

        $game->save();

        /*
        |--------------------------------------------------------------------------
        | Update group standings
        |--------------------------------------------------------------------------
        */

        if ($game->isGroupStage()) {

            $groupId = $game->homeTeam->group_id;

            $this->standingsService->calculate($groupId);
        }

        /*
        |--------------------------------------------------------------------------
        | Advance bracket
        |--------------------------------------------------------------------------
        */

        if ($game->isKnockout()) {
            $this->bracketService->advance($game);
        }

        /*
        |--------------------------------------------------------------------------
        | Score predictions
        |--------------------------------------------------------------------------
        */

        $this->scoringService->scoreGame($game);

        return response()->json([
            'message' => 'Game updated successfully',
            'game' => $game
        ]);
    }
}
