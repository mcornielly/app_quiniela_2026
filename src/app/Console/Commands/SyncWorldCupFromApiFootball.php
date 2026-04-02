<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Game;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\FootballApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncWorldCupFromApiFootball extends Command
{
    protected $signature = 'football:sync:world-cup
        {--league= : API-FOOTBALL league id (default: API_FOOTBALL_WORLD_CUP_LEAGUE or 1)}
        {--season= : Season year (default: API_FOOTBALL_WORLD_CUP_SEASON or 2026)}
        {--timezone=UTC : API timezone parameter}
        {--tournament-year=2026 : Local tournament year to update}
        {--all-pages : Fetch all pages (default is first page only for cost efficiency)}
        {--dry-run : Print actions without persisting changes}';

    protected $description = 'Sync countries, teams and game venues/results for World Cup from API-FOOTBALL fixtures';

    private const FIFA_FALLBACK_CODE = 'fifa';

    public function handle(FootballApiService $footballApi): int
    {
        $league = (int) ($this->option('league') ?: env('API_FOOTBALL_WORLD_CUP_LEAGUE', 1));
        $season = (int) ($this->option('season') ?: env('API_FOOTBALL_WORLD_CUP_SEASON', 2026));
        $timezone = (string) $this->option('timezone');
        $tournamentYear = (int) $this->option('tournament-year');
        $allPages = (bool) $this->option('all-pages');
        $dryRun = (bool) $this->option('dry-run');

        $tournament = Tournament::query()
            ->where('type', 'world_cup')
            ->where('year', $tournamentYear)
            ->first();

        if (! $tournament) {
            $this->error("Tournament world_cup {$tournamentYear} not found.");
            return self::FAILURE;
        }

        $this->info("Syncing World Cup from API-FOOTBALL (league={$league}, season={$season}, timezone={$timezone})...");

        $params = [
            'league' => $league,
            'season' => $season,
            'timezone' => $timezone,
        ];

        $firstPayload = $footballApi->getFixturesFresh($params);
        $fixtures = collect($firstPayload['response'] ?? []);
        $totalPages = (int) data_get($firstPayload, 'paging.total', 1);

        if ($totalPages > 1 && ! $allPages) {
            $this->warn("API returned {$totalPages} pages. Processing only first page for cost efficiency. Use --all-pages to fetch all.");
        }

        if ($allPages && $totalPages > 1) {
            for ($page = 2; $page <= $totalPages; $page++) {
                $pagePayload = $footballApi->getFixturesFresh($params + ['page' => $page]);
                $fixtures = $fixtures->concat($pagePayload['response'] ?? []);
            }
        }

        if ($fixtures->isEmpty()) {
            $this->warn('No fixtures returned by API.');
            return self::SUCCESS;
        }

        $stats = [
            'countries_created' => 0,
            'countries_updated' => 0,
            'teams_created' => 0,
            'teams_updated' => 0,
            'stadiums_created' => 0,
            'stadiums_updated' => 0,
            'games_matched' => 0,
            'games_updated' => 0,
            'games_skipped' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($fixtures as $fixtureRow) {
                $fixture = data_get($fixtureRow, 'fixture', []);
                $home = data_get($fixtureRow, 'teams.home', []);
                $away = data_get($fixtureRow, 'teams.away', []);

                $homeCountryId = $this->resolveCountry($home, $stats, $dryRun)?->id;
                $awayCountryId = $this->resolveCountry($away, $stats, $dryRun)?->id;

                $homeTeam = $this->resolveTeam($home, $homeCountryId, $tournament->id, $stats, $dryRun);
                $awayTeam = $this->resolveTeam($away, $awayCountryId, $tournament->id, $stats, $dryRun);

                $matchedGame = $this->resolveGameMatch(
                    tournamentId: (int) $tournament->id,
                    fixtureId: (int) data_get($fixture, 'id', 0),
                    homeTeamId: $homeTeam?->id,
                    awayTeamId: $awayTeam?->id,
                    fixtureDateIso: (string) data_get($fixture, 'date', '')
                );

                if (! $matchedGame) {
                    $stats['games_skipped']++;
                    continue;
                }

                $stats['games_matched']++;
                $updated = $this->updateGameFromFixture($matchedGame, $fixtureRow, $dryRun, $stats);
                if ($updated) {
                    $stats['games_updated']++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->line('');
        $this->info($dryRun ? 'Dry-run summary:' : 'Sync summary:');
        $this->line("- Countries created: {$stats['countries_created']}");
        $this->line("- Countries updated: {$stats['countries_updated']}");
        $this->line("- Teams created: {$stats['teams_created']}");
        $this->line("- Teams updated: {$stats['teams_updated']}");
        $this->line("- Stadiums created: {$stats['stadiums_created']}");
        $this->line("- Stadiums updated: {$stats['stadiums_updated']}");
        $this->line("- Games matched: {$stats['games_matched']}");
        $this->line("- Games updated: {$stats['games_updated']}");
        $this->line("- Games skipped (no safe match): {$stats['games_skipped']}");

        return self::SUCCESS;
    }

    private function resolveCountry(array $teamPayload, array &$stats, bool $dryRun): ?Country
    {
        $teamCountryName = trim((string) data_get($teamPayload, 'country', ''));
        $teamCode = strtolower(trim((string) data_get($teamPayload, 'code', '')));

        if ($teamCode === '' && $teamCountryName !== '') {
            $existingByName = Country::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($teamCountryName)])
                ->first();

            if ($existingByName) {
                $teamCode = strtolower((string) $existingByName->code);
            }
        }

        if ($teamCode === '') {
            return null;
        }

        $country = Country::query()->where('code', $teamCode)->first();
        $fallbackFlagPath = $this->fallbackFlagPath();
        $resolvedFlagPath = $this->resolveFlagPath($teamCode, $fallbackFlagPath);
        $resolvedName = $teamCountryName !== '' ? $teamCountryName : Str::upper($teamCode);

        if (! $country) {
            if ($dryRun) {
                $stats['countries_created']++;
                return new Country([
                    'name' => $resolvedName,
                    'code' => $teamCode,
                    'flag_path' => $resolvedFlagPath,
                ]);
            }

            $country = Country::query()->create([
                'name' => $resolvedName,
                'code' => $teamCode,
                'flag_path' => $resolvedFlagPath,
            ]);

            $stats['countries_created']++;

            return $country;
        }

        $shouldUpdate = false;
        $changes = [];

        if ($teamCountryName !== '' && $country->name !== $teamCountryName) {
            $changes['name'] = $teamCountryName;
            $shouldUpdate = true;
        }

        if (empty($country->flag_path)) {
            $changes['flag_path'] = $resolvedFlagPath;
            $shouldUpdate = true;
        }

        if ($shouldUpdate && ! $dryRun) {
            $country->update($changes);
        }

        if ($shouldUpdate) {
            $stats['countries_updated']++;
        }

        return $country->fresh() ?: $country;
    }

    private function resolveTeam(array $teamPayload, ?int $countryId, int $tournamentId, array &$stats, bool $dryRun): ?Team
    {
        $apiTeamId = (int) data_get($teamPayload, 'id', 0);
        $name = trim((string) data_get($teamPayload, 'name', ''));
        $logo = trim((string) data_get($teamPayload, 'logo', ''));

        if ($apiTeamId <= 0 || $name === '') {
            return null;
        }

        $team = Team::query()
            ->where('api_team_id', $apiTeamId)
            ->first();

        if (! $team) {
            $team = Team::query()
                ->where('name', $name)
                ->where('type', 'international')
                ->first();
        }

        if (! $team) {
            if ($dryRun) {
                $stats['teams_created']++;
                return new Team([
                    'name' => $name,
                    'type' => 'international',
                    'country_id' => $countryId,
                    'api_team_id' => $apiTeamId,
                    'api_team_logo_url' => $logo !== '' ? $logo : null,
                ]);
            }

            $team = Team::query()->create([
                'name' => $name,
                'type' => 'international',
                'country_id' => $countryId,
                'api_team_id' => $apiTeamId,
                'api_team_logo_url' => $logo !== '' ? $logo : null,
            ]);

            $stats['teams_created']++;
        } else {
            $changes = [];
            if ($countryId && (int) $team->country_id !== $countryId) {
                $changes['country_id'] = $countryId;
            }
            if ((int) ($team->api_team_id ?? 0) !== $apiTeamId) {
                $changes['api_team_id'] = $apiTeamId;
            }
            if ($logo !== '' && $team->api_team_logo_url !== $logo) {
                $changes['api_team_logo_url'] = $logo;
            }

            if ($changes) {
                if (! $dryRun) {
                    $team->update($changes);
                }
                $stats['teams_updated']++;
            }
        }

        if (! $dryRun && $team->exists) {
            DB::table('tournament_team')->updateOrInsert([
                'tournament_id' => $tournamentId,
                'team_id' => $team->id,
            ]);
        }

        return $team->fresh() ?: $team;
    }

    private function resolveGameMatch(int $tournamentId, int $fixtureId, ?int $homeTeamId, ?int $awayTeamId, string $fixtureDateIso): ?Game
    {
        if ($fixtureId > 0) {
            $byFixture = Game::query()
                ->where('tournament_id', $tournamentId)
                ->where('api_fixture_id', $fixtureId)
                ->first();

            if ($byFixture) {
                return $byFixture;
            }
        }

        if (! $homeTeamId || ! $awayTeamId || $fixtureDateIso === '') {
            return null;
        }

        $fixtureDate = Carbon::parse($fixtureDateIso)->toDateString();
        $datesToTry = [
            $fixtureDate,
            Carbon::parse($fixtureDate)->subDay()->toDateString(),
            Carbon::parse($fixtureDate)->addDay()->toDateString(),
        ];

        foreach ($datesToTry as $date) {
            $candidate = Game::query()
                ->where('tournament_id', $tournamentId)
                ->where('home_team_id', $homeTeamId)
                ->where('away_team_id', $awayTeamId)
                ->whereDate('match_date', $date)
                ->orderBy('match_number')
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        return null;
    }

    private function updateGameFromFixture(Game $game, array $fixtureRow, bool $dryRun, array &$stats): bool
    {
        $fixture = data_get($fixtureRow, 'fixture', []);
        $score = data_get($fixtureRow, 'goals', []);
        $homeScore = data_get($score, 'home');
        $awayScore = data_get($score, 'away');

        $statusLong = (string) data_get($fixtureRow, 'fixture.status.long', '');
        $statusShort = (string) data_get($fixtureRow, 'fixture.status.short', '');
        $status = $this->mapFixtureStatus($statusShort, $statusLong);

        $venueName = trim((string) data_get($fixture, 'venue.name', ''));
        $stadium = $this->resolveStadium($fixture, $stats, $dryRun);

        $changes = [];
        $fixtureId = (int) data_get($fixture, 'id', 0);
        if ($fixtureId > 0 && (int) ($game->api_fixture_id ?? 0) !== $fixtureId) {
            $changes['api_fixture_id'] = $fixtureId;
        }
        if ($venueName !== '' && $game->venue !== $venueName) {
            $changes['venue'] = $venueName;
        }
        if ($stadium?->id && (int) ($game->stadium_id ?? 0) !== (int) $stadium->id) {
            $changes['stadium_id'] = (int) $stadium->id;
        }
        if ($game->status !== $status) {
            $changes['status'] = $status;
        }
        if (is_numeric($homeScore)) {
            $homeScoreInt = (int) $homeScore;
            if ((int) ($game->home_score ?? -1) !== $homeScoreInt) {
                $changes['home_score'] = $homeScoreInt;
            }
        }
        if (is_numeric($awayScore)) {
            $awayScoreInt = (int) $awayScore;
            if ((int) ($game->away_score ?? -1) !== $awayScoreInt) {
                $changes['away_score'] = $awayScoreInt;
            }
        }

        if (
            (($changes['status'] ?? $game->status) === 'finished')
            && (array_key_exists('home_score', $changes) || array_key_exists('away_score', $changes))
        ) {
            $finalHome = array_key_exists('home_score', $changes) ? (int) $changes['home_score'] : (int) $game->home_score;
            $finalAway = array_key_exists('away_score', $changes) ? (int) $changes['away_score'] : (int) $game->away_score;

            if ($finalHome > $finalAway) {
                $changes['winner_team_id'] = $game->home_team_id;
                $changes['result_type'] = 'normal';
            } elseif ($finalAway > $finalHome) {
                $changes['winner_team_id'] = $game->away_team_id;
                $changes['result_type'] = 'normal';
            } else {
                $changes['winner_team_id'] = null;
                $changes['result_type'] = null;
            }
        }

        if (empty($changes)) {
            return false;
        }

        if (! $dryRun) {
            $game->update($changes);
        }

        return true;
    }

    private function resolveStadium(array $fixturePayload, array &$stats, bool $dryRun): ?Stadium
    {
        $venue = data_get($fixturePayload, 'venue', []);
        $apiVenueId = data_get($venue, 'id');
        $name = trim((string) data_get($venue, 'name', ''));
        $city = trim((string) data_get($venue, 'city', ''));
        $address = trim((string) data_get($venue, 'address', ''));
        $capacity = data_get($venue, 'capacity');
        $surface = trim((string) data_get($venue, 'surface', ''));
        $image = trim((string) data_get($venue, 'image', ''));

        if ($name === '' && ! is_numeric($apiVenueId)) {
            return null;
        }

        $country = trim((string) data_get($fixturePayload, 'league.country', ''));

        $stadium = null;
        if (is_numeric($apiVenueId)) {
            $stadium = Stadium::query()->where('api_venue_id', (int) $apiVenueId)->first();
        }

        if (! $stadium && $name !== '') {
            $stadium = Stadium::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->when($city !== '', fn ($q) => $q->whereRaw('LOWER(city) = ?', [Str::lower($city)]))
                ->first();
        }

        $payload = [
            'api_venue_id' => is_numeric($apiVenueId) ? (int) $apiVenueId : null,
            'name' => $name !== '' ? $name : 'Unknown Stadium',
            'city' => $city !== '' ? $city : null,
            'country' => $country !== '' ? $country : null,
            'address' => $address !== '' ? $address : null,
            'capacity' => is_numeric($capacity) ? (int) $capacity : null,
            'surface' => $surface !== '' ? $surface : null,
            'image_url' => $image !== '' ? $image : null,
        ];

        if (! $stadium) {
            if ($dryRun) {
                $stats['stadiums_created']++;
                return new Stadium($payload);
            }

            $stats['stadiums_created']++;
            return Stadium::query()->create($payload);
        }

        $changes = [];
        foreach ($payload as $key => $value) {
            if ($value !== null && $stadium->{$key} !== $value) {
                $changes[$key] = $value;
            }
        }

        if (! empty($changes)) {
            if (! $dryRun) {
                $stadium->update($changes);
            }
            $stats['stadiums_updated']++;
        }

        return $stadium->fresh() ?: $stadium;
    }

    private function mapFixtureStatus(string $short, string $long): string
    {
        $short = strtoupper(trim($short));
        $long = Str::lower(trim($long));

        if (in_array($short, ['FT', 'AET', 'PEN'], true) || str_contains($long, 'match finished')) {
            return 'finished';
        }

        if (
            in_array($short, ['1H', '2H', 'HT', 'ET', 'BT', 'P', 'INT', 'LIVE'], true)
            || str_contains($long, 'live')
            || str_contains($long, 'in play')
        ) {
            return 'in_progress';
        }

        return 'scheduled';
    }

    private function resolveFlagPath(string $code, string $fallback): string
    {
        $code = strtolower(trim($code));

        $svg = storage_path("app/public/flags/{$code}.svg");
        if (is_file($svg)) {
            return "flags/{$code}.svg";
        }

        $png = storage_path("app/public/flags/{$code}.png");
        if (is_file($png)) {
            return "flags/{$code}.png";
        }

        return $fallback;
    }

    private function fallbackFlagPath(): string
    {
        $svg = storage_path('app/public/flags/'.self::FIFA_FALLBACK_CODE.'.svg');
        if (is_file($svg)) {
            return 'flags/'.self::FIFA_FALLBACK_CODE.'.svg';
        }

        return 'flags/'.self::FIFA_FALLBACK_CODE.'.png';
    }
}
