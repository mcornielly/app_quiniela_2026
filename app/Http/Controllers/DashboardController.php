<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\PoolEntry;
use App\Models\Game;
use App\Models\Prediction;
use App\Models\GroupStanding;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $tournament = Tournament::query()
            ->where('type', 'world_cup')
            ->orderByDesc('year')
            ->first();

        return Inertia::render('Dashboard', [
            'tournament' => $tournament ? [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'year' => $tournament->year,
                'logo' => $tournament->logo,
            ] : null,
            'todayLabel' => Carbon::today()->format('d/m/Y'),
            'todayIso' => Carbon::today()->toDateString(),
            'favoriteTeams' => $this->favoriteTeams(),
            'favoriteTeamCard' => $this->favoriteTeamCard($tournament?->id),
            'favoriteTeamTheme' => $this->resolveFavoriteTeamTheme(),
            'dashboardMetrics' => $this->dashboardMetrics($tournament?->id),
            'upcomingMatches' => $this->upcomingMatches($tournament?->id),
            'resultMatches' => $this->resultMatches($tournament?->id),
            'featuredResults' => $this->featuredResults($tournament?->id),
            'upcomingGames' => $this->upcomingGames($tournament?->id),
            'topPredictionsRanking' => $this->topPredictionsRanking($tournament?->id),
            'tournamentCoverage' => $this->tournamentCoverage($tournament?->id),
        ]);
    }

    public function updateFavoriteTeam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'favorite_team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $teamId = $validated['favorite_team_id'] ?? null;

        if ($teamId !== null) {
            $allowedIds = collect($this->favoriteTeams())->pluck('id');

            abort_unless($allowedIds->contains($teamId), 422, 'El equipo seleccionado no esta disponible para personalizacion.');
        }

        $request->user()->update([
            'favorite_team_id' => $teamId,
        ]);

        return back()->with('success', $teamId ? 'Tu equipo favorito se actualizo correctamente.' : 'Tu banner volvio al estilo neutro.');
    }

    private function favoriteTeams(): array
    {
        $themes = config('world-cup-themes.themes', []);
        $themeByCountry = collect($themes)
            ->reject(fn (array $theme, string $key) => $key === config('world-cup-themes.default'))
            ->flatMap(function (array $theme) {
                return collect($theme['country_codes'] ?? [])
                    ->mapWithKeys(fn (string $countryCode) => [$countryCode => [
                        'key' => $theme['key'],
                        'label' => $theme['label'],
                    ]]);
            });

        if ($themeByCountry->isEmpty()) {
            return [];
        }

        return Team::query()
            ->with('country')
            ->whereHas('country', fn ($query) => $query->whereIn('code', $themeByCountry->keys()))
            ->orderBy('name')
            ->get()
            ->unique(fn (Team $team) => $team->country?->code ?? "team-{$team->id}")
            ->values()
            ->map(function (Team $team) use ($themeByCountry) {
                $countryCode = $team->country?->code;
                $theme = $countryCode ? $themeByCountry->get($countryCode) : null;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'country_code' => $countryCode,
                    'flag_path' => $team->country?->flag_path,
                    'shield_path' => $team->shield_path,
                    'theme_key' => $theme['key'] ?? null,
                    'theme_label' => $theme['label'] ?? null,
                ];
            })
            ->all();
    }

    private function favoriteTeamCard(?int $tournamentId): ?array
    {
        $user = Auth::user();
        $favoriteTeamId = $user?->favorite_team_id;

        if (!$favoriteTeamId) {
            return null;
        }

        $team = Team::query()
            ->with(['group', 'country'])
            ->find($favoriteTeamId);

        if (!$team) {
            return null;
        }

        $standing = null;
        if ($tournamentId) {
            $standing = GroupStanding::query()
                ->where('tournament_id', $tournamentId)
                ->where('team_id', $team->id)
                ->first();
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'group_name' => $team->group?->name,
            'position' => $standing?->position ?? $team->group_position,
            'stats' => [
                'points' => (int) ($standing?->points ?? 0),
                'played' => (int) ($standing?->played ?? 0),
                'won' => (int) ($standing?->wins ?? 0),
                'drawn' => (int) ($standing?->draws ?? 0),
                'lost' => (int) ($standing?->losses ?? 0),
                'gf' => (int) ($standing?->gf ?? 0),
                'ga' => (int) ($standing?->ga ?? 0),
            ],
        ];
    }

    private function resolveFavoriteTeamTheme(): ?array
    {
        $user = Auth::user();
        $user?->loadMissing('favoriteTeam.country');

        $countryCode = strtolower(trim((string) ($user?->favoriteTeam?->country?->code ?? '')));
        $themes = config('world-cup-themes.themes', []);
        $defaultKey = config('world-cup-themes.default');
        $defaultTheme = $defaultKey ? ($themes[$defaultKey] ?? null) : null;
        $baseTheme = $defaultTheme ?? ($themes['neutral'] ?? null);

        if (!$baseTheme) {
            return null;
        }

        if ($countryCode === '') {
            return $baseTheme;
        }

        foreach ($themes as $theme) {
            $countryCodes = array_map(
                static fn (string $code): string => strtolower(trim($code)),
                $theme['country_codes'] ?? []
            );

            if (in_array($countryCode, $countryCodes, true)) {
                return array_replace($baseTheme, $theme);
            }
        }

        return $baseTheme;
    }

    private function dashboardMetrics(?int $tournamentId): array
    {
        $userId = Auth::id();

        if (!$userId || !$tournamentId) {
            return [
                'liveGamesCount' => 0,
                'totalGamesCount' => 0,
                'userPoolEntriesCount' => 0,
                'userTotalPoints' => 0,
            ];
        }

        return [
            'liveGamesCount' => Game::query()
                ->where('tournament_id', $tournamentId)
                ->where('status', 'in_progress')
                ->count(),
            'totalGamesCount' => Game::query()
                ->where('tournament_id', $tournamentId)
                ->count(),
            'userPoolEntriesCount' => PoolEntry::query()
                ->where('user_id', $userId)
                ->where('tournament_id', $tournamentId)
                ->count(),
            'userTotalPoints' => (int) Prediction::query()
                ->join('pool_entries', 'pool_entries.id', '=', 'predictions.pool_entry_id')
                ->join('games', 'games.id', '=', 'predictions.game_id')
                ->where('pool_entries.user_id', $userId)
                ->where('pool_entries.tournament_id', $tournamentId)
                ->where('games.tournament_id', $tournamentId)
                ->sum('predictions.points'),
        ];
    }

    private function upcomingMatches(?int $tournamentId): array
    {
        if (!$tournamentId) {
            return [];
        }

        return Game::query()
            ->with(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group'])
            ->where('tournament_id', $tournamentId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->limit(6)
            ->get()
            ->map(fn (Game $game) => $this->transformMatchCardGame($game))
            ->values()
            ->all();
    }

    private function topPredictionsRanking(?int $tournamentId): array
    {
        if (!$tournamentId) {
            return [];
        }

        return PoolEntry::query()
            ->selectRaw('
                pool_entries.id as pool_entry_id,
                pool_entries.user_id as user_id,
                pool_entries.name as pool_entry_name,
                users.name as user_name,
                COALESCE(pool_entries.exact_hits, 0) as exact_hits,
                COALESCE(pool_entries.correct_results, 0) as correct_results,
                COALESCE(pool_entries.total_points, 0) as total_points,
                pool_entries.updated_at as updated_at
            ')
            ->join('users', 'users.id', '=', 'pool_entries.user_id')
            ->where('pool_entries.tournament_id', $tournamentId)
            ->orderByDesc('total_points')
            ->orderByDesc('exact_hits')
            ->orderByDesc('correct_results')
            ->limit(15)
            ->get()
            ->values()
            ->map(function ($row, int $index) {
                return [
                    'rank' => $index + 1,
                    'poolEntryId' => (int) $row->pool_entry_id,
                    'userId' => (int) $row->user_id,
                    'poolEntryName' => $row->pool_entry_name,
                    'userName' => $row->user_name,
                    'exactHits' => (int) $row->exact_hits,
                    'correctResults' => (int) $row->correct_results,
                    'totalPoints' => (int) $row->total_points,
                    'updatedAt' => $row->updated_at ? Carbon::parse($row->updated_at)->format('d/m/Y H:i') : null,
                ];
            })
            ->all();
    }

    private function tournamentCoverage(?int $tournamentId): array
    {
        $resolvedTournamentId = $tournamentId
            ?? Game::query()
                ->whereNotNull('tournament_id')
                ->orderByDesc('match_date')
                ->orderByDesc('id')
                ->value('tournament_id');

        if (!$resolvedTournamentId) {
            return [
                'overallProgress' => 0,
                'rows' => [],
                'hasGames' => false,
            ];
        }

        $games = Game::query()
            ->where('tournament_id', $resolvedTournamentId)
            ->get(['stage', 'status']);

        if ($games->isEmpty()) {
            return [
                'overallProgress' => 0,
                'rows' => [],
                'hasGames' => false,
            ];
        }

        $stageOrder = [
            'group' => 10,
            'round_32' => 20,
            'round_16' => 30,
            'quarter' => 40,
            'semi' => 50,
            'third_place' => 60,
            'final' => 70,
        ];

        $rows = $games
            ->groupBy('stage')
            ->map(function ($items, $stage) {
                $total = $items->count();
                $finished = $items->where('status', 'finished')->count();
                $progress = $total > 0 ? (int) round(($finished / $total) * 100) : 0;

                return [
                    'key' => (string) $stage,
                    'label' => $this->stageLabel((string) $stage),
                    'total' => $total,
                    'finished' => $finished,
                    'progress' => $progress,
                    'completed' => $total > 0 && $finished >= $total,
                ];
            })
            ->sortBy(fn (array $row) => $stageOrder[$row['key']] ?? 999)
            ->values()
            ->all();

        $totalGames = $games->count();
        $finishedGames = $games->where('status', 'finished')->count();
        $overallProgress = $totalGames > 0 ? (int) round(($finishedGames / $totalGames) * 100) : 0;

        return [
            'overallProgress' => $overallProgress,
            'rows' => $rows,
            'hasGames' => true,
        ];
    }

    private function resultMatches(?int $tournamentId): array
    {
        if (!$tournamentId) {
            return [];
        }

        return Game::query()
            ->with(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group'])
            ->where('tournament_id', $tournamentId)
            ->where('status', 'finished')
            ->orderByDesc('match_date')
            ->orderByDesc('match_time')
            ->limit(24)
            ->get()
            ->map(fn (Game $game) => $this->transformMatchCardGame($game))
            ->values()
            ->all();
    }

    private function featuredResults(?int $tournamentId): array
    {
        $todayIso = Carbon::today()->toDateString();
        $matches = collect($this->resultMatches($tournamentId));

        $todayMatches = $matches->where('matchDateIso', $todayIso)->take(2)->values();

        if ($todayMatches->isNotEmpty()) {
            return $todayMatches->all();
        }

        return $matches->take(2)->values()->all();
    }

    private function upcomingGames(?int $tournamentId): array
    {
        if (!$tournamentId) {
            return [];
        }

        return Game::query()
            ->with(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group'])
            ->where('tournament_id', $tournamentId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->limit(8)
            ->get()
            ->map(fn (Game $game) => $this->transformTableGame($game))
            ->values()
            ->all();
    }

    private function transformMatchCardGame(Game $game): array
    {
        $status = match ($game->status) {
            'finished' => 'FT',
            'in_progress' => 'LIVE',
            default => 'UPCOMING',
        };

        return [
            'id' => $game->id,
            'stage' => $game->stage,
            'stage_label' => $this->stageLabel($game->stage),
            'matchDateIso' => $game->match_date?->format('Y-m-d'),
            'group_name' => $game->homeTeam?->group?->name,
            'display_date' => $game->match_date?->format('d/m/Y'),
            'display_time' => $game->match_time ? Str::substr($game->match_time, 0, 5) : null,
            'venue' => $game->venue,
            'status' => $status,
            'homeScore' => $game->home_score,
            'awayScore' => $game->away_score,
            'home_team' => $this->transformTeamForCard($game->homeTeam),
            'away_team' => $this->transformTeamForCard($game->awayTeam),
            'home_slot' => $game->home_slot,
            'away_slot' => $game->away_slot,
            'actionLabel' => $status === 'LIVE' ? 'En vivo' : 'Programado',
        ];
    }

    private function transformTableGame(Game $game): array
    {
        $status = match ($game->status) {
            'finished' => 'FT',
            'in_progress' => 'LIVE',
            default => 'UPCOMING',
        };

        return [
            'id' => $game->id,
            'status' => $status,
            'groupName' => $game->homeTeam?->group?->name,
            'venue' => $game->venue,
            'date' => $game->match_date?->format('d/m/Y'),
            'time' => $game->match_time ? Str::substr($game->match_time, 0, 5) : null,
            'homeTeam' => $this->transformTeamForCard($game->homeTeam),
            'awayTeam' => $this->transformTeamForCard($game->awayTeam),
            'homeSlot' => $game->home_slot,
            'awaySlot' => $game->away_slot,
            'homeScore' => $game->home_score,
            'awayScore' => $game->away_score,
            'actionLabel' => $status === 'LIVE' ? 'Ver en vivo' : 'Resultados',
        ];
    }

    private function transformTeamForCard($team): ?array
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

    private function stageLabel(?string $stage): string
    {
        return match ($stage) {
            'group' => 'Fase de grupos',
            'round_32' => 'Round of 32',
            'round_16' => 'Octavos',
            'quarter' => 'Cuartos',
            'semi' => 'Semifinal',
            'third_place' => 'Tercer lugar',
            'final' => 'Final',
            default => Str::headline((string) $stage),
        };
    }
}
