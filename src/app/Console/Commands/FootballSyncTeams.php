<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FootballSyncTeams extends Command
{
    protected $signature = 'football:sync-teams';

    protected $description = 'Sync local teams with API-Football data using a single league call (IDs and Logos)';

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
        $this->info('Running team sync using country name lookups (cached)...');
        $this->info('(WC 2026 season not available on free plan - using country-based search)');
        return $this->fallbackSyncByCountry();
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

    /**
     * Fallback si el Mundial 2026 aún no está disponible como liga en la API:
     * Busca por país usando los datos de la tabla countries (ya populados con sync-countries).
     * Todas estas llamadas también van cachadas 7 días.
     */
    private function fallbackSyncByCountry(): int
    {
        $this->info('Running fallback: searching by country name (cached)...');

        $teams = Team::where('type', 'international')
            ->whereNull('api_team_id')
            ->with('country')
            ->get();

        $updatedCount = 0;

        foreach ($teams as $team) {
            if (Str::contains($team->name, '/') || !$team->country) continue;

            try {
                $response = $this->api->getTeams(['country' => $team->country->name]);
                $apiTeams = $response['response'] ?? [];

                $nationalTeam = collect($apiTeams)->first(fn($t) => $t['team']['national'] ?? false)
                    ?? ($apiTeams[0] ?? null);

                if ($nationalTeam && isset($nationalTeam['team']['id'])) {
                    $team->update([
                        'api_team_id'       => $nationalTeam['team']['id'],
                        'api_team_logo_url'  => $nationalTeam['team']['logo'] ?? null,
                    ]);
                    $this->line("  ✓ <info>{$team->name}</info> → API ID: {$nationalTeam['team']['id']}");
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                $this->line("  ✗ <comment>{$team->name}</comment> — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Fallback complete! Updated: {$updatedCount}.");
        return 0;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $value)));
    }
}
