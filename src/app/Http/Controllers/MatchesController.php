<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Tournament;
use App\Services\FootballApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MatchesController extends Controller
{
    public function __construct(
        private readonly FootballApiService $footballApi
    ) {
    }

    public function index(): Response
    {
        return $this->calendar();
    }

    public function results(): Response
    {
        $tournament = $this->resolveTournament();

        if (! $tournament) {
            return Inertia::render('Matches/Results', [
                'tournament' => null,
                'results' => [],
                'totalFinished' => 0,
            ]);
        }

        $games = $this->baseGamesQuery($tournament->id)->get();

        $results = $games
            ->where('status', 'finished')
            ->sortByDesc(fn (Game $game) => ($game->match_date?->format('Y-m-d') ?? '0000-00-00') . ' ' . ($game->match_time ?? '00:00:00'))
            ->map(fn (Game $game) => $this->transformGame($game))
            ->values()
            ->all();

        return Inertia::render('Matches/Results', [
            'tournament' => $this->transformTournament($tournament),
            'results' => $results,
            'totalFinished' => count($results),
        ]);
    }

    public function calendar(): Response
    {
        $tournament = $this->resolveTournament();

        if (! $tournament) {
            return Inertia::render('Matches/Calendar', [
                'tournament' => null,
                'calendarMatches' => [],
                'groupOptions' => [],
                'stageOptions' => [],
            ]);
        }

        $calendarMatches = $this->baseGamesQuery($tournament->id)
            ->get()
            ->map(fn (Game $game) => $this->transformGame($game))
            ->values()
            ->all();

        $groupOptions = collect($calendarMatches)
            ->pluck('groupName')
            ->filter(fn (?string $value) => ! blank($value))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $stageOptions = collect($calendarMatches)
            ->pluck('stageLabel')
            ->filter(fn (?string $value) => ! blank($value))
            ->unique()
            ->values()
            ->all();

        return Inertia::render('Matches/Calendar', [
            'tournament' => $this->transformTournament($tournament),
            'calendarMatches' => $calendarMatches,
            'groupOptions' => $groupOptions,
            'stageOptions' => $stageOptions,
        ]);
    }

    public function live(): Response
    {
        $tournament = $this->resolveTournament();

        if (! $tournament) {
            return Inertia::render('Matches/Live', [
                'tournament' => null,
                'liveMatches' => [],
            ]);
        }

        $liveMatches = $this->baseGamesQuery($tournament->id)
            ->where('status', 'in_progress')
            ->get()
            ->map(fn (Game $game) => $this->transformGame($game))
            ->values()
            ->all();

        return Inertia::render('Matches/Live', [
            'tournament' => $this->transformTournament($tournament),
            'liveMatches' => $liveMatches,
        ]);
    }

    public function liveShow(Game $game): Response
    {
        $tournament = $this->resolveTournament();

        if (! $tournament || (int) $game->tournament_id !== (int) $tournament->id) {
            abort(404);
        }

        $game->load(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group']);

        return Inertia::render('Matches/LiveShow', [
            'tournament' => $this->transformTournament($tournament),
            'match' => $this->transformGame($game),
        ]);
    }

    public function liveFeed(Game $game): JsonResponse
    {
        $tournament = $this->resolveTournament();

        if (! $tournament || (int) $game->tournament_id !== (int) $tournament->id) {
            abort(404);
        }

        $fixtureId = (int) ($game->api_fixture_id ?? 0);
        $ttlSeconds = $game->status === 'in_progress' ? 15 : 90;
        $cacheKey = "matches.live_feed.{$game->id}.fixture.{$fixtureId}.status.{$game->status}";

        $payload = Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function () use ($game) {
            return $this->buildLiveFeed($game);
        });

        return response()->json($payload);
    }

    private function buildLiveFeed(Game $game): array
    {
        $game->load(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group']);

        $match = $this->transformGame($game);
        $fixtureId = (int) ($game->api_fixture_id ?? 0);
        $homeApiTeamId = (int) ($game->homeTeam?->api_team_id ?? 0);
        $awayApiTeamId = (int) ($game->awayTeam?->api_team_id ?? 0);

        $feed = [
            'ok' => true,
            'match' => $match,
            'fixture' => null,
            'headToHead' => [],
            'events' => [],
            'lineups' => [],
            'statistics' => [],
            'players' => [],
            'errors' => [],
        ];

        if ($fixtureId <= 0) {
            $feed['errors'][] = 'missing_fixture_id';

            return $feed;
        }

        try {
            $fixture = $this->footballApi->getFixtureById($fixtureId);
            $feed['fixture'] = data_get($fixture, 'response.0', null);
            $homeApiTeamId = $homeApiTeamId > 0 ? $homeApiTeamId : (int) data_get($feed['fixture'], 'teams.home.id', 0);
            $awayApiTeamId = $awayApiTeamId > 0 ? $awayApiTeamId : (int) data_get($feed['fixture'], 'teams.away.id', 0);
        } catch (Throwable $exception) {
            report($exception);
            $feed['errors'][] = 'fixture_unavailable';
        }

        try {
            $events = $this->footballApi->getFixtureEvents($fixtureId);
            $feed['events'] = data_get($events, 'response', []);
        } catch (Throwable $exception) {
            report($exception);
            $feed['errors'][] = 'events_unavailable';
        }

        try {
            $lineups = $this->footballApi->getFixtureLineups($fixtureId);
            $feed['lineups'] = data_get($lineups, 'response', []);
        } catch (Throwable $exception) {
            report($exception);
            $feed['errors'][] = 'lineups_unavailable';
        }

        try {
            $statistics = $this->footballApi->getFixtureStatistics($fixtureId);
            $feed['statistics'] = data_get($statistics, 'response', []);
        } catch (Throwable $exception) {
            report($exception);
            $feed['errors'][] = 'statistics_unavailable';
        }

        $feed['players'] = $this->localPlayersFeed($game);

        if (empty($feed['players'])) {
            try {
                $players = $this->footballApi->getFixturePlayers($fixtureId);
                $feed['players'] = data_get($players, 'response', []);
            } catch (Throwable $exception) {
                report($exception);
                $feed['errors'][] = 'players_unavailable';
            }
        }

        if ($homeApiTeamId > 0 && $awayApiTeamId > 0) {
            try {
                $h2h = $this->footballApi->getHeadToHead($homeApiTeamId, $awayApiTeamId, 5);
                $feed['headToHead'] = data_get($h2h, 'response', []);
            } catch (Throwable $exception) {
                report($exception);
                $feed['errors'][] = 'h2h_unavailable';
            }
        } else {
            $feed['errors'][] = 'missing_team_api_ids';
        }

        return $feed;
    }

    private function transformGame(Game $game): array
    {
        return [
            'id' => $game->id,
            'apiFixtureId' => $game->api_fixture_id ? (int) $game->api_fixture_id : null,
            'groupName' => $game->group_name ? "Grupo {$game->group_name}" : null,
            'stageLabel' => $this->stageLabel($game->stage),
            'stage' => $game->stage,
            'status' => $game->status,
            'statusLabel' => $game->status === 'finished' ? 'Final' : ($game->status === 'in_progress' ? 'Live' : 'Upcoming'),
            'matchDateIso' => $game->match_date?->format('Y-m-d'),
            'matchDate' => $game->match_date?->format('d/m/Y'),
            'calendarDateLabel' => $this->calendarDateLabel($game->match_date),
            'matchTime' => $game->match_time ? Str::substr($game->match_time, 0, 5) : '--:--',
            'venue' => $game->venue,
            'homeTeam' => $this->teamName($game->homeTeam?->name, $game->home_slot),
            'awayTeam' => $this->teamName($game->awayTeam?->name, $game->away_slot),
            'homeCode' => $this->teamCode($game->homeTeam?->country?->code, $game->home_slot),
            'awayCode' => $this->teamCode($game->awayTeam?->country?->code, $game->away_slot),
            'homeApiTeamId' => $game->homeTeam?->api_team_id ? (int) $game->homeTeam->api_team_id : null,
            'awayApiTeamId' => $game->awayTeam?->api_team_id ? (int) $game->awayTeam->api_team_id : null,
            'homeFlagUrl' => $this->flagUrl($game->homeTeam?->country?->flag_path),
            'awayFlagUrl' => $this->flagUrl($game->awayTeam?->country?->flag_path),
            'homeShieldUrl' => $this->shieldUrl($game->homeTeam?->shield_path, $game->homeTeam?->api_team_logo_url),
            'awayShieldUrl' => $this->shieldUrl($game->awayTeam?->shield_path, $game->awayTeam?->api_team_logo_url),
            'homeScore' => is_numeric($game->home_score) ? (int) $game->home_score : null,
            'awayScore' => is_numeric($game->away_score) ? (int) $game->away_score : null,
        ];
    }

    private function resolveTournament(): ?Tournament
    {
        return Tournament::query()
            ->where('type', 'world_cup')
            ->orderByDesc('year')
            ->first();
    }

    private function transformTournament(Tournament $tournament): array
    {
        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'year' => $tournament->year,
        ];
    }

    private function baseGamesQuery(int $tournamentId)
    {
        return Game::query()
            ->with(['homeTeam.country', 'awayTeam.country', 'homeTeam.group', 'awayTeam.group'])
            ->where('tournament_id', $tournamentId)
            ->orderBy('match_date')
            ->orderBy('match_time');
    }

    private function calendarDateLabel(?Carbon $date): string
    {
        if (! $date) {
            return 'Fecha por definir';
        }

        return Str::ucfirst($date->copy()->locale('es')->translatedFormat('l, j \d\e F \d\e Y'));
    }

    private function stageLabel(?string $stage): string
    {
        return match ($stage) {
            'group' => 'Group stage',
            'round_32' => 'Round of 32',
            'round_16' => 'Round of 16',
            'quarter' => 'Quarter final',
            'semi' => 'Semi-final',
            'third_place' => 'Third place',
            'final' => 'Final',
            default => 'Stage',
        };
    }

    private function teamName(?string $name, ?string $slot): string
    {
        return $name ?: ($slot ?: 'TBD');
    }

    private function teamCode(?string $countryCode, ?string $slot): string
    {
        if ($countryCode) {
            return Str::upper($countryCode);
        }

        $fallback = preg_replace('/[^A-Za-z]/', '', $slot ?: '') ?: 'TBD';

        return Str::upper(Str::substr($fallback, 0, 3));
    }

    private function flagUrl(?string $flagPath): ?string
    {
        if (! $flagPath) {
            return null;
        }

        if (Str::startsWith($flagPath, ['http://', 'https://', '/storage/'])) {
            return $flagPath;
        }

        return Storage::url($flagPath);
    }

    private function shieldUrl(?string $shieldPath, ?string $apiShieldUrl = null): ?string
    {
        if ($shieldPath) {
            if (Str::startsWith($shieldPath, ['http://', 'https://', '/storage/'])) {
                return $shieldPath;
            }

            return Storage::url($shieldPath);
        }

        if ($apiShieldUrl && Str::startsWith($apiShieldUrl, ['http://', 'https://'])) {
            return $apiShieldUrl;
        }

        return null;
    }

    private function localPlayersFeed(Game $game): array
    {
        return Cache::remember(
            "matches.live_feed.local_players.{$game->id}",
            now()->addMinutes(45),
            fn () => array_values(array_filter([
                $this->localPlayersTeamPack($game->homeTeam),
                $this->localPlayersTeamPack($game->awayTeam),
            ]))
        );
    }

    private function localPlayersTeamPack($team): ?array
    {
        if (! $team) {
            return null;
        }

        $players = $team->players()
            ->select(['id', 'api_player_id', 'name', 'firstname', 'lastname', 'position', 'number'])
            ->inRandomOrder()
            ->limit(14)
            ->get();

        if ($players->isEmpty()) {
            return null;
        }

        return [
            'team' => [
                'id' => (int) ($team->api_team_id ?: $team->id),
                'name' => $team->name,
            ],
            'players' => $players->map(function ($player) {
                $name = trim((string) ($player->name ?: trim("{$player->firstname} {$player->lastname}")));

                return [
                    'player' => [
                        'id' => (int) ($player->api_player_id ?: $player->id),
                        'name' => $name !== '' ? $name : 'Jugador',
                    ],
                    'statistics' => [[
                        'games' => [
                            'number' => $player->number ?: random_int(1, 30),
                            'position' => $this->normalizePlayerPosition($player->position),
                            'rating' => number_format(random_int(60, 79) / 10, 1, '.', ''),
                        ],
                    ]],
                ];
            })->values()->all(),
        ];
    }

    private function normalizePlayerPosition(?string $position): string
    {
        $value = Str::upper((string) $position);

        return match (true) {
            str_contains($value, 'GOAL') || $value === 'G' || $value === 'GK' => 'G',
            str_contains($value, 'DEF') || $value === 'D' => 'D',
            str_contains($value, 'MID') || $value === 'M' || $value === 'CM' => 'M',
            str_contains($value, 'ATT') || str_contains($value, 'FOR') || $value === 'F' || $value === 'FW' => 'F',
            default => 'M',
        };
    }
}
