<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Country;

class CountryController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $countries = Country::query()
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%");

                });

            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Countries/Index', [
            'filters' => request()->only('search'),
            'countries' => $countries
        ]);
    }

    /**
     * Store new Country
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        Country::create($validated);

        return redirect()->back()->with('success','Country created successfully');
    }

    /**
     * Update Country
     */
    public function update(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        $country->update($validated);

        return redirect()->back()->with('success','Country updated successfully');
    }

    /**
     * Delete single Country
     */
    public function destroy(Country $country)
    {
        $country->delete();

        return redirect()->back()->with('success','Country deleted successfully');
    }

    /**
     * Delete multiple Countries
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['exists:countries,id']
        ]);

        $deleted = Country::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted countries deleted successfully"
        ]);
    }
}
