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

        $poolEntries = PoolEntry::query()
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
            'poolEntries' => $poolEntries,
            'tournaments' => $tournaments,
            'users' => $users,
        ]);
    }

    /**
     * Store new PoolEntry
     */
    public function store(Request $request)
    {
        $this->normalizePoolEntryPayload($request);

        $validated = $this->validatePoolEntry($request);

        $payload = $this->applyPoolEntryDefaults($validated);

        PoolEntry::create($payload);

        return redirect()->back()->with('success','PoolEntry created successfully');
    }

    /**
     * Update PoolEntry
     */
    public function update(Request $request, PoolEntry $pool_entry)
    {
        $this->normalizePoolEntryPayload($request);

        $validated = $this->validatePoolEntry($request);

        $payload = $this->applyPoolEntryDefaults($validated, $pool_entry);

        $pool_entry->update($payload);

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
            'success' => "$deleted pool entries deleted successfully"
        ]);
    }

    private function validatePoolEntry(Request $request): array
    {
        return $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id'],
            'user_id' => [
                'required',
                'exists:users,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'completion_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'total_points' => ['nullable', 'integer', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'payment_ref' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function normalizePoolEntryPayload(Request $request): void
    {
        $nullableFields = [
            'status',
            'completion_percent',
            'total_points',
            'paid_at',
            'payment_ref',
        ];

        foreach ($nullableFields as $field) {
            if ($request->has($field) && $request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }
    }

    private function applyPoolEntryDefaults(array $data, ?PoolEntry $poolEntry = null): array
    {
        $data['status'] = $data['status'] ?? ($poolEntry?->status ?? 'draft');
        $data['completion_percent'] = $data['completion_percent'] ?? 0;
        $data['total_points'] = $data['total_points'] ?? 0;

        return $data;
    }
}
