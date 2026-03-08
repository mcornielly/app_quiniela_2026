<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;



class GameController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $games = Game::query()
            ->with(['tournament', 'homeTeam', 'awayTeam']) // Eager load relationships
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%");

                });

            })
            ->orderBy('match_number')
            ->paginate(10)
            ->withQueryString();

        $tournaments = Tournament::select('id','name')->get();


        return Inertia::render('Admin/Games/Index', [
            'filters' => request()->only('search'),
            'games' => $games,
            'tournaments' => $tournaments,
        ]);
    }

    /**
     * Store new Game
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        Game::create($validated);

        return redirect()->back()->with('success','Game created successfully');
    }

    /**
     * Update Game
     */
    public function update(Request $request, Game $game)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

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
}
