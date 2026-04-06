<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Team;
use Inertia\Response;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TeamController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $teams = Team::with('group')
            ->search($search)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Teams/Index', [
            'filters' => request()->only('search'),
            'teams' => $teams,
            'groups' => Group::orderBy('name')->get(),
            'types' => Team::types()
        ]);
    }
    
    /**
     * Store new team
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'group_id' => ['nullable','exists:groups,id'],
            'type' => ['required','in:international,national,club'],
            'group_position' => ['required','integer','min:1','max:4']
        ]);

        Team::create($validated);

        return redirect()->back()->with('success','Team created successfully');
    }

    /**
     * Update team
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'group_id' => ['nullable','exists:groups,id'],
            'type' => ['required','in:international,national,club'],
            'group_position' => ['required','integer','min:1','max:4']
        ]);

        $team->update($validated);

        return redirect()->back()->with('success','Team updated successfully');
    }

    /**
     * Delete single team
     */
    public function destroy(Team $team)
    {
        $team->delete();

        return redirect()->back()->with('success','Team deleted successfully');
    }

    /**
     * Delete multiple teams
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['exists:teams,id']
        ]);

        $deleted = Team::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted teams deleted successfully"
        ]);
    }
}
