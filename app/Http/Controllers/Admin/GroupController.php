<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Group;
use App\Models\Tournament;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $groups = Group::with('tournament')
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%");

                });

            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Groups/Index', [
            'filters' => request()->only('search'),
            'groups' => $groups,
            'tournaments' => Tournament::orderBy('name')->get(),
        ]);
    }

    /**
     * Store new Group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => ['required','exists:tournaments,id'],
            'name' => ['required','string','max:255']
        ]);

        Group::create($validated);

        return redirect()->back()->with('success','Group created successfully');
    }

    /**
     * Update Group
     */
    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'tournament_id' => ['required','exists:tournaments,id'],
            'name' => ['required','string','max:255']
        ]);

        $group->update($validated);

        return redirect()->back()->with('success','Group updated successfully');
    }

    /**
     * Delete single Group
     */
    public function destroy(Group $group)
    {
        $group->delete();

        return redirect()->back()->with('success','Group deleted successfully');
    }

    /**
     * Delete multiple Groups
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['exists:groups,id']
        ]);

        $deleted = Group::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted groups deleted successfully"
        ]);
    }
}
