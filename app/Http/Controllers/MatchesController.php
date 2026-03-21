<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MatchesController extends Controller
{
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

    private function transformGame(Game $game): array
    {
        return [
            'id' => $game->id,
            'groupName' => $game->group_name ? "Group {$game->group_name}" : null,
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
            'homeFlagUrl' => $this->flagUrl($game->homeTeam?->country?->flag_path),
            'awayFlagUrl' => $this->flagUrl($game->awayTeam?->country?->flag_path),
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
}
