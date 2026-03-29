<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;



class GameController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $games = Game::query()
                ->with([
                    'tournament',
                    'homeTeam.group',
                    'awayTeam.group'
                ])
                ->when($search, function ($query) use ($search) {
                    $query->where('venue', 'like', "%{$search}%");
                })
                ->orderBy('match_number')
                ->paginate(10)
                ->through(function ($game) {

                    $game->match_date = $game->match_date?->format('Y-m-d');
                    $game->match_time = $game->match_time;

                    return $game;
            });

        $tournaments = Tournament::select('id','name')->get();
        $teams = Team::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Games/Index', [
            'filters' => request()->only('search'),
            'games' => $games,
            'tournaments' => $tournaments,
            'teams' => $teams,
        ]);
    }

    public function calendar(): Response
    {
        $search = request('search');

        $games = Game::query()
                ->with([
                    'tournament',
                    'homeTeam.group',
                    'awayTeam.group'
                ])
                ->when($search, function ($query) use ($search) {
                    $query->where('venue', 'like', "%{$search}%");
                })
                ->orderBy('match_number')
                ->paginate(10)
                ->through(function ($game) {

                    $game->match_date = $game->match_date?->format('Y-m-d');
                    $game->match_time = $game->match_time;

                    return $game;
            });

        $tournaments = Tournament::select('id','name')->get();
        $teams = Team::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Games/Calendar', [
            'filters' => request()->only('search'),
            'games' => $games,
            'tournaments' => $tournaments,
            'teams' => $teams,
        ]);
    }

    /**
     * Store new Game
     */
    public function store(Request $request)
    {
        $this->normalizeGamePayload($request);

        $validated = $this->validateGame($request);

        if (! isset($validated['stage'])) {
            $validated['stage'] = 'group';
        }

        Game::create($validated);

        return redirect()->back()->with('success','Game created successfully');
    }

    /**
     * Update Game
     */
    public function update(Request $request, Game $game)
    {
        $this->normalizeGamePayload($request);

        $validated = $this->validateGame($request, $game);

        if (! isset($validated['stage'])) {
            $validated['stage'] = $game->stage ?? 'group';
        }

        $game->update($validated);

        return redirect()->back()->with('success','Game updated successfully');
    }

    /**
     * Delete single Game
     */
    public function destroy(Game $game)
    {
        $game->delete();

        return redirect()->back()->with('success','Game deleted successfully');
    }

    /**
     * Delete multiple Games
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['exists:games,id']
        ]);

        $deleted = Game::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted games deleted successfully"
        ]);
    }

    private function validateGame(Request $request, ?Game $game = null): array
    {
        $stageOptions = [
            'group',
            'round_32',
            'round_16',
            'quarter',
            'semi',
            'third_place',
            'final'
        ];

        return $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id'],
            'match_number' => [
                'required',
                'integer',
                Rule::unique('games', 'match_number')->ignore($game?->id)
            ],
            'home_team_id' => ['nullable', 'exists:teams,id'],
            'away_team_id' => ['nullable', 'exists:teams,id', 'different:home_team_id'],
            'home_slot' => ['nullable', 'string', 'max:20'],
            'away_slot' => ['nullable', 'string', 'max:20'],
            'home_score' => ['nullable', 'integer', 'min:0'],
            'away_score' => ['nullable', 'integer', 'min:0'],
            'winner_team_id' => ['nullable', 'exists:teams,id'],
            'stage' => ['nullable', Rule::in($stageOptions)],
            'venue' => ['nullable', 'string', 'max:255'],
            'match_date' => ['required', 'date'],
            'match_time' => ['nullable', 'date_format:H:i'],
        ]);
    }

    private function normalizeGamePayload(Request $request): void
    {
        $nullableFields = [
            'home_team_id',
            'away_team_id',
            'winner_team_id',
            'home_slot',
            'away_slot',
            'home_score',
            'away_score',
            'match_time',
            'venue',
            'stage',
        ];

        foreach ($nullableFields as $field) {
            if ($request->has($field) && $request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }
    }
}
