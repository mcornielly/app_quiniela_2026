<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PoolEntry;
use App\Models\Tournament;
use App\Models\User;



class PoolEntryController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $pool_entries = PoolEntry::query()
            ->with(['tournament','user'])
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%");

                });

            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $tournaments = Tournament::select('id','name')->get();
        $users = User::select('id','name')->get();


        return Inertia::render('Admin/PoolEntries/Index', [
            'filters' => request()->only('search'),
            'pool_entries' => $pool_entries,
            'tournaments' => $tournaments,
            'users' => $users,
        ]);
    }

    /**
     * Store new PoolEntry
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        PoolEntry::create($validated);

        return redirect()->back()->with('success','PoolEntry created successfully');
    }

    /**
     * Update PoolEntry
     */
    public function update(Request $request, PoolEntry $pool_entry)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255']
        ]);

        $pool_entry->update($validated);

        return redirect()->back()->with('success','PoolEntry updated successfully');
    }

    /**
     * Delete single PoolEntry
     */
    public function destroy(PoolEntry $pool_entry)
    {
        $pool_entry->delete();

        return redirect()->back()->with('success','PoolEntry deleted successfully');
    }

    /**
     * Delete multiple PoolEntries
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required','array'],
            'ids.*' => ['exists:pool_entries,id']
        ]);

        $deleted = PoolEntry::whereIn('id', $validated['ids'])->delete();

        return back()->with([
            'success' => "$deleted pool_entries deleted successfully"
        ]);
    }
}
