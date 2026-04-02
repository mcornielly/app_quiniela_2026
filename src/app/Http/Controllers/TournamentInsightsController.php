<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Tournament\StandingsTableService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TournamentInsightsController extends Controller
{
    public function __construct(
        private readonly StandingsTableService $standingsTableService,
    ) {
    }

    public function teamProfile(?Team $team = null): Response
    {
        $tournament = $this->resolveTournament();

        if (! $tournament) {
            return Inertia::render('Quiniela/TeamProfile', [
                'tournament' => null,
                'teams' => [],
                'selectedTeam' => null,
                'groupStandings' => [],
                'teamMatches' => [],
                'venueCards' => [],
            ]);
        }

        $teams = Team::query()
            ->with([
                'country:id,name,code,flag_path',
                'group:id,tournament_id,name',
                'tournamentEntries' => fn ($query) => $query
                    ->where('tournament_id', $tournament->id),
            ])
            ->whereHas('tournamentEntries', fn ($query) => $query->where('tournament_id', $tournament->id))
            ->orderByRaw('COALESCE(group_id, 9999)')
            ->orderByRaw('COALESCE(group_position, 9999)')
            ->orderBy('name')
            ->get();

        $selectedTeam = $this->resolveSelectedTeam($teams, $team);
        $tournamentGames = $this->resolveTournamentGames($tournament->id);

        if (! $selectedTeam) {
            return Inertia::render('Quiniela/TeamProfile', [
                'tournament' => $this->transformTournament($tournament),
                'teams' => $this->transformTeamList($teams),
                'selectedTeam' => null,
                'groupStandings' => [],
                'teamMatches' => [],
                'venueCards' => $this->transformVenueCards($tournamentGames),
            ]);
        }

        $groupStandings = $this->resolveGroupStandings($teams, $selectedTeam, $tournament->id);
        $teamMatchesCollection = $this->resolveTeamMatches($selectedTeam, $tournament->id);

        return Inertia::render('Quiniela/TeamProfile', [
            'tournament' => $this->transformTournament($tournament),
            'teams' => $this->transformTeamList($teams),
            'selectedTeam' => $this->transformSelectedTeam($selectedTeam, $groupStandings),
            'groupStandings' => $groupStandings,
            'teamMatches' => $this->transformTeamMatches($teamMatchesCollection, $selectedTeam),
            'venueCards' => $this->transformVenueCards($tournamentGames),
        ]);
    }

    public function stadiumProfile(string $venueSlug): Response
    {
        $tournament = $this->resolveTournament();

        if (! $tournament) {
            return Inertia::render('Quiniela/StadiumProfile', [
                'tournament' => null,
                'stadium' => null,
                'matches' => [],
            ]);
        }

        $games = $this->resolveTournamentGames($tournament->id);
        $selectedVenueMatches = $games
            ->filter(function (Game $game) use ($venueSlug) {
                $venue = trim((string) $game->venue);

                return $venue !== '' && Str::slug(Str::lower($venue)) === $venueSlug;
            })
            ->values();

        if ($selectedVenueMatches->isEmpty()) {
            return Inertia::render('Quiniela/StadiumProfile', [
                'tournament' => $this->transformTournament($tournament),
                'stadium' => null,
                'matches' => [],
            ]);
        }

        $venue = trim((string) $selectedVenueMatches->first()->venue);
        $stadiumModel = $selectedVenueMatches->first()?->stadium;

        return Inertia::render('Quiniela/StadiumProfile', [
            'tournament' => $this->transformTournament($tournament),
            'stadium' => [
                'name' => $stadiumModel?->name ?: $venue,
                'slug' => $venueSlug,
                'city' => $stadiumModel?->city,
                'country' => $stadiumModel?->country,
                'address' => $stadiumModel?->address,
                'capacity' => $stadiumModel?->capacity,
                'surface' => $stadiumModel?->surface,
                'api_venue_id' => $stadiumModel?->api_venue_id,
                'image_url' => $stadiumModel?->image_url ?: $this->resolveVenueImageUrl($venue),
                'matches_count' => $selectedVenueMatches->count(),
                'info' => $stadiumModel
                    ? 'Datos sincronizados desde API-FOOTBALL.'
                    : 'Datos base cargados desde el calendario. Sincroniza estadios para enriquecer esta ficha.',
            ],
            'matches' => $selectedVenueMatches->map(fn (Game $game) => [
                'id' => $game->id,
                'match_number' => $game->match_number,
                'stage' => $game->stage,
                'stage_label' => $this->stageLabel($game->stage),
                'group_name' => $game->group_name,
                'status' => $game->status,
                'status_label' => $this->statusLabel($game->status),
                'home_team' => $this->teamSnapshot($game->homeTeam, $game->home_slot),
                'away_team' => $this->teamSnapshot($game->awayTeam, $game->away_slot),
                'home_score' => is_numeric($game->home_score) ? (int) $game->home_score : null,
                'away_score' => is_numeric($game->away_score) ? (int) $game->away_score : null,
                'match_date_label' => $this->formatMatchDate($game->match_date),
                'match_time_label' => $game->match_time ? Str::substr($game->match_time, 0, 5) : '--:--',
            ])->all(),
        ]);
    }

    public function roadmap(): Response
    {
        $tournament = $this->resolveTournament();

        if (! $tournament) {
            return Inertia::render('Quiniela/TournamentRoadmap', [
                'tournament' => null,
                'roadmapStages' => [],
                'totals' => [
                    'games' => 0,
                    'finished' => 0,
                    'inProgress' => 0,
                    'upcoming' => 0,
                ],
            ]);
        }

        $games = Game::query()
            ->with([
                'homeTeam.country:id,name,code,flag_path',
                'awayTeam.country:id,name,code,flag_path',
                'homeTeam.group:id,tournament_id,name',
                'awayTeam.group:id,tournament_id,name',
            ])
            ->where('tournament_id', $tournament->id)
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->orderBy('match_number')
            ->get();

        $stageSortOrder = [
            'group' => 10,
            'round_32' => 20,
            'round_16' => 30,
            'quarter' => 40,
            'semi' => 50,
            'third_place' => 60,
            'final' => 70,
        ];

        $games = $games
            ->sortBy(function (Game $game) use ($stageSortOrder) {
                $stageWeight = $stageSortOrder[$game->stage] ?? 999;
                $matchNumber = (int) ($game->match_number ?? 9999);
                $dateValue = $game->match_date?->format('Ymd') ?? '99999999';
                $timeValue = preg_replace('/[^0-9]/', '', (string) ($game->match_time ?? '99:99:99')) ?: '999999';

                return sprintf('%03d-%04d-%s-%s', $stageWeight, $matchNumber, $dateValue, $timeValue);
            })
            ->values();

        $stageDefinitions = [
            ['key' => 'group', 'label' => 'Fase de grupos', 'short' => 'Grupos'],
            ['key' => 'round_32', 'label' => 'Ronda de 32', 'short' => 'R32'],
            ['key' => 'round_16', 'label' => 'Octavos', 'short' => 'R16'],
            ['key' => 'quarter', 'label' => 'Cuartos', 'short' => 'QF'],
            ['key' => 'semi', 'label' => 'Semifinales', 'short' => 'SF'],
            ['key' => 'third_place', 'label' => 'Tercer lugar', 'short' => '3RD'],
            ['key' => 'final', 'label' => 'Final', 'short' => 'FIN'],
        ];

        $roadmapStages = collect($stageDefinitions)
            ->map(function (array $stage) use ($games) {
                $stageGames = $games
                    ->where('stage', $stage['key'])
                    ->values();

                $finished = $stageGames->where('status', 'finished')->count();
                $inProgress = $stageGames->where('status', 'in_progress')->count();

                return [
                    'key' => $stage['key'],
                    'label' => $stage['label'],
                    'short' => $stage['short'],
                    'games' => $stageGames->map(fn (Game $game) => $this->transformRoadmapGame($game))->all(),
                    'stats' => [
                        'total' => $stageGames->count(),
                        'finished' => $finished,
                        'inProgress' => $inProgress,
                        'upcoming' => max(0, $stageGames->count() - $finished - $inProgress),
                    ],
                ];
            })
            ->filter(fn (array $stage) => $stage['stats']['total'] > 0)
            ->values()
            ->all();

        $finishedGames = $games->where('status', 'finished')->count();
        $inProgressGames = $games->where('status', 'in_progress')->count();

        return Inertia::render('Quiniela/TournamentRoadmap', [
            'tournament' => $this->transformTournament($tournament),
            'roadmapStages' => $roadmapStages,
            'totals' => [
                'games' => $games->count(),
                'finished' => $finishedGames,
                'inProgress' => $inProgressGames,
                'upcoming' => max(0, $games->count() - $finishedGames - $inProgressGames),
            ],
        ]);
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
            'type' => $tournament->type,
        ];
    }

    private function resolveSelectedTeam(Collection $teams, ?Team $candidateTeam): ?Team
    {
        if (! $candidateTeam) {
            return null;
        }

        return $teams->firstWhere('id', $candidateTeam->id);
    }

    private function resolveGroupStandings(Collection $teams, Team $selectedTeam, int $tournamentId): array
    {
        if (! $selectedTeam->group_id) {
            return [];
        }

        $groupTeams = $teams
            ->where('group_id', $selectedTeam->group_id)
            ->values();

        if ($groupTeams->isEmpty()) {
            return [];
        }

        $teamIds = $groupTeams->pluck('id')->all();

        $groupGames = Game::query()
            ->where('tournament_id', $tournamentId)
            ->where('stage', 'group')
            ->whereIn('home_team_id', $teamIds)
            ->whereIn('away_team_id', $teamIds)
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->get();

        $rows = $this->standingsTableService->calculate($groupTeams, $groupGames);

        return collect($rows)
            ->values()
            ->map(function (array $row, int $index) use ($selectedTeam) {
                $team = $row['team'];

                return [
                    'position' => $index + 1,
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'team_code' => Str::upper($team->country?->code ?? Str::substr($team->name, 0, 3)),
                    'flag_url' => $this->resolveFlagUrl($team->country?->flag_path),
                    'played' => (int) $row['played'],
                    'wins' => (int) $row['wins'],
                    'draws' => (int) $row['draws'],
                    'losses' => (int) $row['losses'],
                    'gf' => (int) $row['gf'],
                    'ga' => (int) $row['ga'],
                    'gd' => (int) $row['gd'],
                    'points' => (int) $row['points'],
                    'is_selected' => $team->id === $selectedTeam->id,
                ];
            })
            ->all();
    }

    private function resolveTeamMatches(Team $selectedTeam, int $tournamentId): Collection
    {
        return $this->resolveTournamentGames($tournamentId)
            ->filter(fn (Game $game) => (int) $game->home_team_id === (int) $selectedTeam->id || (int) $game->away_team_id === (int) $selectedTeam->id)
            ->values();
    }

    private function transformTeamList(Collection $teams): array
    {
        return $teams
            ->map(function (Team $team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'country_code' => Str::upper($team->country?->code ?? ''),
                    'group_name' => $team->group?->name,
                    'flag_url' => $this->resolveFlagUrl($team->country?->flag_path),
                    'shield_url' => $this->resolveShieldUrl($team),
                ];
            })
            ->values()
            ->all();
    }

    private function transformSelectedTeam(Team $team, array $groupStandings): array
    {
        $entry = $team->tournamentEntries->first();
        $standingRow = collect($groupStandings)->firstWhere('team_id', $team->id);

        return [
            'id' => $team->id,
            'name' => $team->name,
            'country_name' => $team->country?->name,
            'country_code' => Str::upper($team->country?->code ?? ''),
            'group_name' => $team->group?->name,
            'group_position' => $team->group_position,
            'flag_url' => $this->resolveFlagUrl($team->country?->flag_path),
            'shield_url' => $this->resolveShieldUrl($team),
            'fifa_ranking' => $entry?->fifa_ranking,
            'fair_play_points' => $entry?->fair_play_points,
            'group_stats' => $standingRow,
        ];
    }

    private function transformTeamMatches(Collection $teamMatches, Team $selectedTeam): array
    {
        return $teamMatches
            ->map(function (Game $game) use ($selectedTeam) {
                $isHome = (int) $game->home_team_id === (int) $selectedTeam->id;
                $opponent = $isHome ? $game->awayTeam : $game->homeTeam;
                $opponentSlot = $isHome ? $game->away_slot : $game->home_slot;

                return [
                    'id' => $game->id,
                    'match_number' => $game->match_number,
                    'stage' => $game->stage,
                    'stage_label' => $this->stageLabel($game->stage),
                    'group_name' => $game->group_name,
                    'status' => $game->status,
                    'status_label' => $this->statusLabel($game->status),
                    'is_home' => $isHome,
                    'home_team' => $this->teamSnapshot($game->homeTeam, $game->home_slot),
                    'away_team' => $this->teamSnapshot($game->awayTeam, $game->away_slot),
                    'opponent' => $this->teamSnapshot($opponent, $opponentSlot),
                    'venue' => $game->venue,
                    'venue_image_url' => $game->stadium?->image_url ?: $this->resolveVenueImageUrl($game->venue),
                    'match_date_label' => $this->formatMatchDate($game->match_date),
                    'match_time_label' => $game->match_time ? Str::substr($game->match_time, 0, 5) : '--:--',
                    'home_score' => is_numeric($game->home_score) ? (int) $game->home_score : null,
                    'away_score' => is_numeric($game->away_score) ? (int) $game->away_score : null,
                ];
            })
            ->values()
            ->all();
    }

    private function transformVenueCards(Collection $matches): array
    {
        return $matches
            ->filter(fn (Game $game) => ! blank($game->venue))
            ->groupBy(fn (Game $game) => trim((string) $game->venue))
            ->map(function (Collection $venueMatches, string $venue) {
                $sorted = $venueMatches
                    ->sortBy(fn (Game $game) => ($game->match_date?->format('Y-m-d') ?? '9999-99-99') . ' ' . ($game->match_time ?? '99:99:99'))
                    ->values();

                $first = $sorted->first();

                return [
                    'slug' => Str::slug(Str::lower($venue)),
                    'venue' => $venue,
                    'matches_count' => $sorted->count(),
                    'first_match_date_iso' => $first?->match_date?->format('Y-m-d'),
                    'first_match_date' => $this->formatMatchDate($first?->match_date),
                    'first_match_time' => $first?->match_time ? Str::substr($first->match_time, 0, 5) : '--:--',
                    'image_url' => $first?->stadium?->image_url ?: $this->resolveVenueImageUrl($venue),
                ];
            })
            ->sortBy('first_match_date_iso')
            ->map(function (array $row) {
                unset($row['first_match_date_iso']);
                return $row;
            })
            ->values()
            ->all();
    }

    private function resolveTournamentGames(int $tournamentId): Collection
    {
        return Game::query()
            ->with([
                'homeTeam.country:id,name,code,flag_path',
                'awayTeam.country:id,name,code,flag_path',
                'homeTeam.group:id,tournament_id,name',
                'awayTeam.group:id,tournament_id,name',
                'stadium:id,api_venue_id,name,city,country,address,capacity,surface,image_url',
            ])
            ->where('tournament_id', $tournamentId)
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->orderBy('match_number')
            ->get();
    }

    private function transformRoadmapGame(Game $game): array
    {
        return [
            'id' => $game->id,
            'match_number' => $game->match_number,
            'stage' => $game->stage,
            'group_name' => $game->group_name,
            'status' => $game->status,
            'status_label' => $this->statusLabel($game->status),
            'home_team' => $this->teamSnapshot($game->homeTeam, $game->home_slot),
            'away_team' => $this->teamSnapshot($game->awayTeam, $game->away_slot),
            'home_score' => is_numeric($game->home_score) ? (int) $game->home_score : null,
            'away_score' => is_numeric($game->away_score) ? (int) $game->away_score : null,
            'date_label' => $this->formatMatchDate($game->match_date),
            'time_label' => $game->match_time ? Str::substr($game->match_time, 0, 5) : '--:--',
            'venue' => $game->venue,
        ];
    }

    private function teamSnapshot(?Team $team, ?string $slot): array
    {
        $fallbackName = $slot ?: 'Por definir';
        $code = Str::upper($team?->country?->code ?? Str::substr(preg_replace('/[^A-Za-z]/', '', $fallbackName), 0, 3));

        return [
            'id' => $team?->id,
            'name' => $team?->name ?: $fallbackName,
            'code' => $code ?: 'TBD',
            'flag_url' => $this->resolveFlagUrl($team?->country?->flag_path),
            'shield_url' => $this->resolveShieldUrl($team),
        ];
    }

    private function resolveFlagUrl(?string $flagPath): ?string
    {
        if (! $flagPath) {
            return null;
        }

        if (Str::startsWith($flagPath, ['http://', 'https://', '/storage/'])) {
            return $flagPath;
        }

        return Storage::url($flagPath);
    }

    private function resolveShieldUrl(?Team $team): ?string
    {
        if (! $team) {
            return null;
        }

        $path = $team->shield_path;

        if (! $path) {
            $countryCode = strtolower(trim((string) $team->country?->code));

            if ($countryCode !== '') {
                $fallback = "shield/{$countryCode}.png";
                if (Storage::disk('public')->exists($fallback)) {
                    $path = $fallback;
                }
            }
        }

        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/storage/'])) {
            return $path;
        }

        return Storage::url($path);
    }

    private function resolveVenueImageUrl(?string $venue): ?string
    {
        if (! $venue) {
            return null;
        }

        $images = config('stadiums.images', []);
        $slug = Str::slug(Str::lower($venue));

        return $images[$slug] ?? null;
    }

    private function formatMatchDate(?Carbon $date): string
    {
        if (! $date) {
            return 'Fecha por definir';
        }

        return Str::ucfirst($date->copy()->locale('es')->translatedFormat('d M Y'));
    }

    private function stageLabel(?string $stage): string
    {
        return match ($stage) {
            'group' => 'Fase de grupos',
            'round_32' => 'Ronda de 32',
            'round_16' => 'Octavos',
            'quarter' => 'Cuartos',
            'semi' => 'Semifinal',
            'third_place' => 'Tercer lugar',
            'final' => 'Final',
            default => 'Etapa',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'finished' => 'Finalizado',
            'in_progress' => 'En juego',
            default => 'Programado',
        };
    }
}
