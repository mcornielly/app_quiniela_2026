<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Rule;
use App\Models\Tournament;



class RuleController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $rules = Rule::query()
            ->with(['tournament'])
            ->search($search)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $tournaments = Tournament::select('id','name')->get();


        return Inertia::render('Admin/Rules/Index', [
            'filters' => request()->only('search'),
            'rules' => $rules,
            'tournaments' => $tournaments,


        ]);
    }

    /**
     * Store new Rule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        Rule::create($validated);

        return redirect()->back()->with('success','Rule created successfully');
    }

    /**
     * Update Rule
     */
    public function update(Request $request, Rule $rule)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        $rule->update($validated);

        return redirect()->back()->with('success','Rule updated successfully');
    }

    /**
     * Delete single Rule
     */
    public function destroy(Rule $rule)
    {
        $rule->delete();

        return redirect()->back()->with('success','Rule deleted successfully');
    }

    /**
     * Delete multiple Rules
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['exists:rules,id']
        ]);

        $deleted = Rule::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted rules deleted successfully"
        ]);
    }
}
