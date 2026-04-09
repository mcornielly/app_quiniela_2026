<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Models\Team;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FootballSyncTeams extends Command
{
    protected $signature = 'football:sync-teams
        {--tournament-year=2026 : Tournament year}
        {--tournament-type=world_cup : Tournament type}
        {--dry-run : Show matches without persisting}';

    protected $description = 'Assign API team IDs to tournament participants using API-FOOTBALL teams endpoint only';

    // World Cup 2026 league ID in API-Football
    private const WC_LEAGUE_ID = 1;
    private const WC_SEASON    = 2026;

    private FootballApiService $api;

    public function __construct(FootballApiService $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    public function handle(): int
    {
        $year = (int) $this->option('tournament-year');
        $type = (string) $this->option('tournament-type');
        $dryRun = (bool) $this->option('dry-run');

        $tournament = Tournament::query()
            ->where('year', $year)
            ->where('type', $type)
            ->first();

        if (! $tournament) {
            $this->error("Tournament {$type} {$year} not found.");
            return self::FAILURE;
        }

        $teams = Team::query()
            ->where('type', 'international')
            ->whereHas('tournamentEntries', fn ($q) => $q->where('tournament_id', $tournament->id))
            ->with('country')
            ->get();

        if ($teams->isEmpty()) {
            $this->warn('No international participant teams found for tournament.');
            return self::SUCCESS;
        }

        $this->info("Syncing API team IDs for {$teams->count()} participant teams (endpoint: teams)...");

        $updated = 0;
        $skipped = 0;
        $unmatched = [];
        $usedApiIds = Team::query()
            ->whereNotNull('api_team_id')
            ->pluck('api_team_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($teams as $team) {
            if (Str::contains($team->name, '/')) {
                $skipped++;
                $this->line("  - <comment>{$team->name}</comment> (playoff placeholder, skipped)");
                continue;
            }

            $candidate = $this->findBestApiCandidate($team);

            if (! $candidate) {
                $unmatched[] = $team->name;
                $this->line("  ✗ <comment>{$team->name}</comment> (no API match)");
                continue;
            }

            $apiId = (int) data_get($candidate, 'team.id', 0);
            $apiLogo = data_get($candidate, 'team.logo');

            if ($apiId <= 0) {
                $unmatched[] = $team->name;
                $this->line("  ✗ <comment>{$team->name}</comment> (API candidate without id)");
                continue;
            }

            $alreadyUsedByOther = in_array($apiId, $usedApiIds, true) && (int) ($team->api_team_id ?? 0) !== $apiId;
            if ($alreadyUsedByOther) {
                $unmatched[] = $team->name;
                $this->line("  ✗ <comment>{$team->name}</comment> (API ID {$apiId} already assigned)");
                continue;
            }

            $next = [
                'api_team_id' => $apiId,
                'api_team_logo_url' => is_string($apiLogo) && $apiLogo !== '' ? $apiLogo : $team->api_team_logo_url,
            ];

            if (! $dryRun) {
                $team->update($next);
            }

            if (! in_array($apiId, $usedApiIds, true)) {
                $usedApiIds[] = $apiId;
            }

            $updated++;
            $this->line("  ✓ <info>{$team->name}</info> → API ID: {$apiId}");
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry-run complete.' : 'Sync complete.');
        $this->line("- Updated: {$updated}");
        $this->line("- Skipped: {$skipped}");
        $this->line("- Unmatched: ".count($unmatched));

        if (! empty($unmatched)) {
            $this->line('Unmatched teams:');
            foreach ($unmatched as $name) {
                $this->line("  - {$name}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Intento principal: busca el mejor match entre el equipo local y el mapa de la API.
     * Prueba 3 estrategias en orden:
     *   1. Nombre del equipo normalizado
     *   2. Nombre del país (de la relación country)
     *   3. Código ISO del país
     */
    private function findBestMatch(Team $team, array $apiMap): ?array
    {
        // Estrategia 1: nombre directo del equipo
        $key = $this->normalize($team->name);
        if (isset($apiMap[$key])) return $apiMap[$key];

        // Estrategia 2: nombre del país relacionado
        if ($team->country) {
            $key = $this->normalize($team->country->name);
            if (isset($apiMap[$key])) return $apiMap[$key];

            // Estrategia 3: búsqueda parcial dentro del mapa por código ISO
            $code = strtolower($team->country->code);
            foreach ($apiMap as $normalized => $data) {
                if (Str::contains($normalized, $code)) {
                    return $data;
                }
            }
        }

        // Estrategia 4: búsqueda parcial de las primeras 4 letras del nombre
        $shortName = substr($this->normalize($team->name), 0, 4);
        foreach ($apiMap as $normalized => $data) {
            if (str_starts_with($normalized, $shortName)) {
                return $data;
            }
        }

        return null;
    }

    private function findBestApiCandidate(Team $team): ?array
    {
        $pool = collect();

        $queries = $this->candidateQueries($team);
        foreach ($queries as $params) {
            try {
                $response = $this->api->getTeams($params);
                $rows = collect($response['response'] ?? [])->map(fn ($row) => (array) $row);
                $pool = $pool->concat($rows);
            } catch (\Throwable $e) {
                // Keep going with other candidate queries.
            }
        }

        if ($pool->isEmpty()) {
            return null;
        }

        $scored = $pool
            ->unique(function ($row) {
                $id = (int) data_get($row, 'team.id', 0);
                return $id > 0 ? "id:{$id}" : md5(json_encode($row));
            })
            ->map(function (array $row) use ($team) {
                return [
                    'score' => $this->scoreApiTeamCandidate($team, $row),
                    'row' => $row,
                ];
            })
            ->sortByDesc('score')
            ->values();

        $best = $scored->first();
        if (! $best || (int) ($best['score'] ?? 0) < 80) {
            return null;
        }

        return $best['row'];
    }

    private function candidateQueries(Team $team): array
    {
        $queries = [];

        $queries[] = ['search' => $team->name];

        $countryName = $this->countryNameForApi($team);
        if ($countryName !== null) {
            $queries[] = ['country' => $countryName];
            $queries[] = ['country' => $countryName, 'search' => $team->name];
        }

        return collect($queries)
            ->filter(fn ($q) => ! empty(array_filter($q, fn ($v) => trim((string) $v) !== '')))
            ->unique(fn ($q) => md5(json_encode($q)))
            ->values()
            ->all();
    }

    private function countryNameForApi(Team $team): ?string
    {
        $raw = trim((string) ($team->country?->name ?? ''));
        if ($raw === '') {
            return null;
        }

        $normalized = $this->normalize($raw);

        return match ($normalized) {
            'unitedstates', 'unitedstatesofamerica', 'usa' => 'USA',
            'southkorea', 'republicofkorea', 'repofkorea' => 'South Korea',
            'iran', 'iriran' => 'Iran',
            default => $raw,
        };
    }

    private function scoreApiTeamCandidate(Team $localTeam, array $candidate): int
    {
        $score = 0;

        $localName = $this->normalize($localTeam->name);
        $countryName = $this->normalize((string) ($localTeam->country?->name ?? ''));
        $apiName = $this->normalize((string) data_get($candidate, 'team.name', ''));
        $apiCountry = $this->normalize((string) data_get($candidate, 'team.country', ''));
        $national = (bool) data_get($candidate, 'team.national', false);

        if ($national) {
            $score += 45;
        }

        if ($localName !== '' && $apiName === $localName) {
            $score += 60;
        } elseif ($localName !== '' && (str_contains($apiName, $localName) || str_contains($localName, $apiName))) {
            $score += 30;
        }

        if ($countryName !== '' && $apiCountry === $countryName) {
            $score += 30;
        } elseif ($countryName !== '' && (str_contains($apiCountry, $countryName) || str_contains($countryName, $apiCountry))) {
            $score += 15;
        }

        return $score;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $value)));
    }
}
