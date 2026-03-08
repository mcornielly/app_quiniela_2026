<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Tournament;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

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
            'name' => ['required','string','max:255'],
            'year' => ['nullable','integer'],
            'host_countries' => ['nullable','string'],
            'logo' => ['nullable','image','mimes:png,jpg,jpeg,svg,webp','max:2048'],
            'deadline_at' => ['nullable','date'],
            'status' => ['nullable','string'],
            'type' => ['nullable','string'],
        ]);

        // Upload logo
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('tournaments','public');
        }

        Tournament::create($validated);

        return redirect()->back()->with('success','Tournament created successfully');
    }

    /**
     * Update Tournament
     */
    public function update(Request $request, Tournament $tournament)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'year' => ['nullable','integer'],
            'host_countries' => ['nullable'],
            'logo' => ['nullable','image','mimes:png,jpg,jpeg,svg,webp','max:2048'],
            'deadline_at' => ['nullable','date'],
            'status' => ['nullable','string'],
            'type' => ['nullable','string'],
        ]);

        // subir nueva imagen
        if ($request->hasFile('logo')) {

            // borrar logo anterior
            if ($tournament->logo) {
                Storage::disk('public')->delete($tournament->logo);
            }

            $validated['logo'] = $request->file('logo')->store('tournaments', 'public');
        }

        $tournament->update($validated);

        return back()->with('success','Tournament updated successfully');
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
