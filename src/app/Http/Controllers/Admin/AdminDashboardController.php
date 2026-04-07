<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupStanding;
use App\Models\PoolEntry;
use App\Models\Tournament;
use App\Services\Tournament\PoolRankingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(PoolRankingService $rankingService): Response
    {
        $today = Carbon::today();
        $tournament = Tournament::query()->first();

        $resultMatches = Game::query()
            ->with(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group'])
            ->where('status', 'finished')
            ->orderByDesc('match_date')
            ->orderByDesc('match_time')
            ->limit(24)
            ->get()
            ->map(fn (Game $game) => $this->transformMatchCardGame($game))
            ->values();

        $featuredResults = $resultMatches
            ->where('matchDateIso', $today->toDateString())
            ->take(2)
            ->values();

        if ($featuredResults->isEmpty()) {
            $featuredResults = $resultMatches->take(2)->values();
        }

        $upcomingGames = Game::query()
            ->with(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group'])
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->limit(6)
            ->get()
            ->map(fn (Game $game) => $this->transformTableGame($game))
            ->values();

        $groupStageMatches = Game::query()
            ->with(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group'])
            ->where('stage', 'group')
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->limit(48)
            ->get()
            ->map(fn (Game $game) => $this->transformMatchCardGame($game))
            ->values();

        $groups = Group::query()
            ->with('teams.country')
            ->orderBy('name')
            ->get();

        $storedStandingsByGroup = GroupStanding::query()
            ->with('team.country')
            ->orderBy('position')
            ->get()
            ->groupBy('group_id')
            ->map(function ($standings) {
                return $standings
                    ->map(function (GroupStanding $standing) {
                        return [
                            'teamId' => $standing->team_id,
                            'team' => $this->transformTeam($standing->team),
                            'played' => $standing->played,
                            'won' => $standing->wins,
                            'drawn' => $standing->draws,
                            'lost' => $standing->losses,
                            'gf' => $standing->gf,
                            'ga' => $standing->ga,
                            'gd' => $standing->gd,
                            'points' => $standing->points,
                        ];
                    })
                    ->values()
                    ->all();
            });

        $standingsByGroup = $groups
            ->mapWithKeys(function (Group $group) use ($storedStandingsByGroup) {
                $groupStandings = $storedStandingsByGroup->get($group->id);

                if ($groupStandings && count($groupStandings) > 0) {
                    return [(string) $group->id => $groupStandings];
                }

                return [
                    (string) $group->id => $group->teams
                        ->sortBy([
                            ['group_position', 'asc'],
                            ['name', 'asc'],
                        ])
                        ->values()
                        ->map(function ($team) {
                            return [
                                'teamId' => $team->id,
                                'team' => $this->transformTeam($team),
                                'played' => 0,
                                'won' => 0,
                                'drawn' => 0,
                                'lost' => 0,
                                'gf' => 0,
                                'ga' => 0,
                                'gd' => 0,
                                'points' => 0,
                            ];
                        })
                        ->all(),
                ];
            });

        $groups = $groups
            ->map(function (Group $group) {
                return [
                    'id' => $group->id,
                    'value' => (string) $group->id,
                    'name' => $group->name,
                    'label' => "Grupo {$group->name}",
                    'teams' => $group->teams
                        ->sortBy([
                            ['group_position', 'asc'],
                            ['name', 'asc'],
                        ])
                        ->values()
                        ->map(fn ($team) => $this->transformTeam($team))
                        ->all(),
                ];
            })
            ->values();

        $ranking = $tournament
            ? $rankingService->getRanking($tournament->id, 15)
                ->map(function (PoolEntry $poolEntry, $index) {
                    return [
                        'id' => $poolEntry->id,
                        'name' => $poolEntry->name,
                        'exactHits' => $poolEntry->exact_hits,
                        'correctResults' => $poolEntry->correct_results,
                        'totalPoints' => $poolEntry->total_points,
                        'updatedAt' => $poolEntry->updated_at?->format('d/m/Y H:i'),
                        'position' => $index + 1,
                    ];
                })
                ->values()
            : collect();

        // Calculate Pool Metrics
        $poolStats = [
            'total' => PoolEntry::count(),
            'paid' => PoolEntry::whereNotNull('paid_at')->count(),
            'pending' => PoolEntry::whereNull('paid_at')->count(),
            'revenue' => PoolEntry::whereNotNull('paid_at')->count() * 10, // Assuming $10 per entry
        ];

        return Inertia::render('Admin/Dashboard', [
            'todayLabel' => $today->format('d/m/Y'),
            'todayIso' => $today->toDateString(),
            'resultMatches' => $resultMatches,
            'featuredResults' => $featuredResults,
            'upcomingGames' => $upcomingGames,
            'groupStageMatches' => $groupStageMatches,
            'groups' => $groups,
            'standingsByGroup' => $standingsByGroup,
            'ranking' => $ranking,
            'poolStats' => $poolStats,
            'tournament' => $tournament
                ? [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'year' => $tournament->year,
                ]
                : null,
        ]);
    }

    private function transformTableGame(Game $game): array
    {
        $status = match ($game->status) {
            'finished' => 'FT',
            'live' => 'LIVE',
            default => 'UPCOMING',
        };

        return [
            'id' => $game->id,
            'status' => $status,
            'groupId' => $game->homeTeam?->group?->id,
            'groupName' => $game->homeTeam?->group?->name,
            'venue' => $game->venue,
            'date' => $game->match_date?->format('d/m/Y'),
            'time' => $game->match_time,
            'homeTeam' => $this->transformTeam($game->homeTeam),
            'awayTeam' => $this->transformTeam($game->awayTeam),
            'homeScore' => $game->home_score,
            'awayScore' => $game->away_score,
            'actionLabel' => $status === 'LIVE' ? 'Ver en vivo' : 'Resultados',
        ];
    }

    private function transformMatchCardGame(Game $game): array
    {
        $status = match ($game->status) {
            'finished' => 'FT',
            'live' => 'LIVE',
            default => 'UPCOMING',
        };

        return [
            'id' => $game->id,
            'stage' => $game->stage,
            'stage_label' => Str::headline((string) $game->stage),
            'groupId' => $game->homeTeam?->group?->id,
            'group_name' => $game->homeTeam?->group?->name,
            'matchDateIso' => $game->match_date?->format('Y-m-d'),
            'display_date' => $game->match_date?->format('d/m/Y'),
            'display_time' => $game->match_time,
            'venue' => $game->venue,
            'status' => $status,
            'homeScore' => $game->home_score,
            'awayScore' => $game->away_score,
            'home_team' => $this->transformTeam($game->homeTeam),
            'away_team' => $this->transformTeam($game->awayTeam),
            'home_slot' => $game->home_slot,
            'away_slot' => $game->away_slot,
            'win_probability' => null,
            'probability_text' => null,
            'actionLabel' => $status === 'LIVE' ? 'Ver en vivo' : 'Resultados',
        ];
    }

    private function transformTeam($team): ?array
    {
        if (!$team) {
            return null;
        }

        $countryCode = Str::upper($team->country?->code ?? '');
        $fallbackCode = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $team->name), 0, 3));
        $isSpecialSlot = Str::lower($team->country?->code ?? '') === 'fifa';

        return [
            'id' => $team->id,
            'name' => $team->name,
            'code' => $isSpecialSlot ? $team->name : ($countryCode ?: $fallbackCode ?: Str::upper(Str::substr($team->name, 0, 3))),
            'flag_path' => $team->country?->flag_path,
            'flag_url' => $this->resolveFlagUrl($team->country?->flag_path),
            'is_special_slot' => $isSpecialSlot,
        ];
    }

    private function resolveFlagUrl(?string $flagPath): ?string
    {
        if (!$flagPath) {
            return null;
        }

        if (Str::startsWith($flagPath, ['http://', 'https://', '/storage/'])) {
            return $flagPath;
        }

        return Storage::url($flagPath);
    }
}
