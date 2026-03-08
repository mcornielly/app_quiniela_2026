<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tournament;

class TournamentController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $tournaments = Tournament::query()
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%");

                });

            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Tournaments/Index', [
            'filters' => request()->only('search'),
            'tournaments' => $tournaments
        ]);
    }

    /**
     * Store new Tournament
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        Tournament::create($validated);

        return redirect()->back()->with('success','Tournament created successfully');
    }

    /**
     * Update Tournament
     */
    public function update(Request $request, Tournament $tournament)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        $tournament->update($validated);

        return redirect()->back()->with('success','Tournament updated successfully');
    }

    /**
     * Delete single Tournament
     */
    public function destroy(Tournament $tournament)
    {
        $tournament->delete();

        return redirect()->back()->with('success','Tournament deleted successfully');
    }

    /**
     * Delete multiple Tournaments
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['exists:tournaments,id']
        ]);

        $deleted = Tournament::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted tournaments deleted successfully"
        ]);
    }
}
