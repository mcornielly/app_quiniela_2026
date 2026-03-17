<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuinielaWorldCupController extends Controller
{
    public function __invoke(): Response
    {
        $tournament = Tournament::query()
            ->with([
                'groups.teams.country',
                'games.homeTeam.country',
                'games.homeTeam.group',
                'games.awayTeam.country',
                'games.awayTeam.group',
            ])
            ->whereHas('games')
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->firstOrFail();

        $groups = $tournament->groups
            ->sortBy('name')
            ->values()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'teams' => $group->teams
                        ->sortBy([
                            ['group_position', 'asc'],
                            ['name', 'asc'],
                        ])
                        ->values()
                        ->map(fn (Team $team) => $this->transformTeam($team))
                        ->all(),
                ];
            })
            ->all();

        $games = $tournament->games
            ->sortBy([
                ['match_date', 'asc'],
                ['match_time', 'asc'],
                ['match_number', 'asc'],
            ])
            ->values()
            ->map(function ($game) {
                return [
                    'id' => $game->id,
                    'match_number' => $game->match_number,
                    'stage' => $game->stage,
                    'stage_label' => $this->stageLabel($game->stage),
                    'group_name' => $game->homeTeam?->group?->name,
                    'match_date' => $game->match_date_input,
                    'match_time' => $game->match_time_input,
                    'venue' => $game->venue,
                    'status' => $game->status,
                    'home_slot' => $game->home_slot,
                    'away_slot' => $game->away_slot,
                    'home_team' => $this->transformTeam($game->homeTeam),
                    'away_team' => $this->transformTeam($game->awayTeam),
                ];
            })
            ->all();

        return Inertia::render('Quiniela/TournamentPredictionWorldCup', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'year' => $tournament->year,
                'type' => $tournament->type,
            ],
            'groups' => $groups,
            'games' => $games,
            'bracketRules' => config('tournament_brackets.' . $this->bracketKey($tournament), []),
        ]);
    }

    private function bracketKey(Tournament $tournament): string
    {
        return sprintf('%s_%s', $tournament->type, $tournament->year);
    }

    private function transformTeam(?Team $team): ?array
    {
        if (!$team) {
            return null;
        }

        $rawCountryCode = Str::lower($team->country?->code ?? '');
        $countryCode = Str::upper($rawCountryCode);
        $fallbackCode = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $team->name), 0, 3));
        $isSpecialFifaSlot = $rawCountryCode === 'fifa';

        $displayCode = $isSpecialFifaSlot
            ? $team->name
            : ($countryCode ?: $fallbackCode ?: Str::upper(Str::substr($team->name, 0, 3)));

        return [
            'id' => $team->id,
            'name' => $team->name,
            'code' => $displayCode,
            'group_name' => $team->group?->name,
            'group_position' => $team->group_position,
            'flag_path' => $team->country?->flag_path,
            'flag_url' => $this->resolveFlagUrl($team),
            'is_special_slot' => $isSpecialFifaSlot,
        ];
    }

    private function resolveFlagUrl(Team $team): ?string
    {
        $flagPath = $team->country?->flag_path;

        if ($flagPath) {
            if (Str::startsWith($flagPath, ['http://', 'https://', '/storage/'])) {
                return $flagPath;
            }

            return Storage::url($flagPath);
        }

        $countryCode = Str::lower($team->country?->code ?? $this->resolveCountryCodeFromTeamName($team->name));

        if ($countryCode) {
            return Storage::url("flags/{$countryCode}.png");
        }

        return null;
    }

    private function resolveCountryCodeFromTeamName(?string $teamName): ?string
    {
        if (!$teamName) {
            return null;
        }

        $map = [
            'algeria' => 'dz',
            'argentina' => 'ar',
            'australia' => 'au',
            'austria' => 'at',
            'belgium' => 'be',
            'brazil' => 'br',
            'canada' => 'ca',
            'cape verde' => 'cv',
            'colombia' => 'co',
            'croatia' => 'hr',
            'curacao' => 'cw',
            'curaçao' => 'cw',
            'ecuador' => 'ec',
            'egypt' => 'eg',
            'england' => 'gb-eng',
            'france' => 'fr',
            'germany' => 'de',
            'ghana' => 'gh',
            'haiti' => 'ht',
            'ir iran' => 'ir',
            'iran' => 'ir',
            'ivory coast' => 'ci',
            'japan' => 'jp',
            'jordan' => 'jo',
            'mexico' => 'mx',
            'morocco' => 'ma',
            'netherlands' => 'nl',
            'new zealand' => 'nz',
            'norway' => 'no',
            'panama' => 'pa',
            'paraguay' => 'py',
            'portugal' => 'pt',
            'qatar' => 'qa',
            'rep. of korea' => 'kr',
            'republic of korea' => 'kr',
            'south korea' => 'kr',
            'saudi arabia' => 'sa',
            'scotland' => 'gb-sct',
            'senegal' => 'sn',
            'south africa' => 'za',
            'spain' => 'es',
            'switzerland' => 'ch',
            'tunisia' => 'tn',
            'uruguay' => 'uy',
            'usa' => 'us',
            'united states' => 'us',
            'uzbekistan' => 'uz',
        ];

        return $map[Str::lower(trim($teamName))] ?? null;
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
