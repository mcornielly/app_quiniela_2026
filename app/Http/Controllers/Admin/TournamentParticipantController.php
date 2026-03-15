<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Services\Tournament\BracketProgressionService;
use App\Services\Tournament\GroupStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TournamentParticipantController extends Controller
{
    public function __construct(
        private readonly GroupStandingsService $groupStandingsService,
        private readonly BracketProgressionService $bracketProgressionService,
    ) {
    }

    public function index(Tournament $tournament): Response
    {
        $participants = $tournament->tournamentTeams()
            ->with(['team.group', 'team.country'])
            ->get()
            ->sortBy([
                fn (TournamentTeam $entry) => $entry->team?->group?->name ?? 'ZZ',
                fn (TournamentTeam $entry) => $entry->team?->group_position ?? 99,
                fn (TournamentTeam $entry) => $entry->team?->name ?? 'ZZZ',
            ])
            ->values()
            ->map(function (TournamentTeam $entry) {
                return [
                    'id' => $entry->id,
                    'team_id' => $entry->team_id,
                    'team_name' => $entry->team?->name,
                    'group_name' => $entry->team?->group?->name,
                    'group_position' => $entry->team?->group_position,
                    'country_name' => $entry->team?->country?->name,
                    'fifa_ranking' => $entry->fifa_ranking,
                    'fair_play_points' => $entry->fair_play_points,
                ];
            });

        return Inertia::render('Admin/Tournaments/Participants', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'year' => $tournament->year,
                'type' => $tournament->type,
            ],
            'participants' => $participants,
        ]);
    }

    public function update(Request $request, Tournament $tournament, TournamentTeam $participant): RedirectResponse
    {
        abort_unless($participant->tournament_id === $tournament->id, 404);

        $validated = $request->validate([
            'fifa_ranking' => ['nullable', 'integer', 'min:1', 'max:999'],
            'fair_play_points' => ['required', 'integer', 'max:0'],
        ]);

        $participant->update($validated);

        foreach ($tournament->groups()->pluck('id') as $groupId) {
            $this->groupStandingsService->calculate($groupId);
        }

        $this->bracketProgressionService->syncTournament($tournament->fresh());

        return redirect()
            ->back()
            ->with('success', 'Tournament participant metrics updated successfully.');
    }
}
