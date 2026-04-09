<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FootballApiService
{
    private string $baseUrl;

    private string $apiKey;

    private int $timeoutSeconds;

    private int $retryTimes;

    private int $retrySleepMs;

    private const MIN_QUOTA_REMAINING = 5;
    private const CACHE_TTL_STATIC = 604800; // 7 days
    private const CACHE_TTL_VOLATILE = 300;   // 5 minutes

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.football_api.url', 'https://v3.football.api-sports.io'), '/');
        $this->apiKey = (string) config('services.football_api.key', '');
        $this->timeoutSeconds = (int) config('services.football_api.timeout', 15);
        $this->retryTimes = (int) config('services.football_api.retry_times', 2);
        $this->retrySleepMs = (int) config('services.football_api.retry_sleep_ms', 250);
    }

    /**
     * Make an API-FOOTBALL GET request.
     */
    private function get(string $endpoint, array $params = []): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('API_FOOTBALL_KEY is not configured.');
        }

        // Rate limit check from cache (prevent even firing if we are empty)
        if (Cache::get('football_api_quota_exhausted', false)) {
            throw new RuntimeException('API Football daily quota practically exhausted. Stopping to preserve last calls.');
        }

        $response = $this->httpClient()->get($endpoint, $params);

        // Update quota status based on headers
        $remaining = $response->header('x-ratelimit-requests-remaining');
        $limit = $response->header('x-ratelimit-requests-limit');

        if ($response->successful() && $remaining !== null) {
            $remainingInt = (int) $remaining;
            
            // Only block if we are actually near a limit (e.g. Free plan is 100)
            // If they have 75,000 requests, 5 is negligible, but it's a safe floor.
            if ($remainingInt <= self::MIN_QUOTA_REMAINING) {
                Cache::put('football_api_quota_exhausted', true, now()->endOfDay());
            }

            // Store current quota for monitoring
            Cache::put('football_api_last_quota', [
                'remaining' => $remainingInt,
                'limit' => (int) $limit,
                'updated_at' => now()->toDateTimeString(),
            ], 86400);
        }

        if (! $response->successful()) {
            throw new RuntimeException("API Football HTTP error {$response->status()} on [{$endpoint}]");
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException("API Football invalid JSON payload on [{$endpoint}]");
        }

        $errors = $payload['errors'] ?? [];
        if (is_array($errors) && ! empty($errors)) {
            $errorMessage = implode(' | ', array_map(fn ($key, $value) => is_string($key) ? "{$key}: {$value}" : (string) $value, array_keys($errors), $errors));
            throw new RuntimeException("API Football business error on [{$endpoint}] {$errorMessage}");
        }

        return $payload;
    }

    /**
     * Fetch all pages for a given endpoint.
     */
    public function getAllPages(string $endpoint, array $params = []): array
    {
        $allData = [];
        $currentPage = 1;
        $totalPages = 1;

        do {
            $response = $this->get($endpoint, array_merge($params, ['page' => $currentPage]));
            $data = $response['response'] ?? [];
            $allData = array_merge($allData, $data);

            $totalPages = (int) ($response['paging']['total'] ?? 1);
            $currentPage++;
        } while ($currentPage <= $totalPages);

        return $allData;
    }

    public function getQuotaStatus(): array
    {
        return [
            'exhausted' => Cache::get('football_api_quota_exhausted', false),
            'expires_at' => Cache::get('football_api_quota_exhausted') ? now()->endOfDay()->toDateTimeString() : null,
            'last_check' => Cache::get('football_api_last_quota'),
        ];
    }

    private function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-apisports-key' => $this->apiKey,
            ])
            ->timeout($this->timeoutSeconds)
            ->retry($this->retryTimes, $this->retrySleepMs);
    }

    private function normalizedCacheKey(string $prefix, array $params = []): string
    {
        ksort($params);

        return $prefix.'.'.md5(json_encode($params));
    }

    private function cached(string $key, int $ttlSeconds, callable $callback): array
    {
        if ($ttlSeconds <= 0) {
            return $callback();
        }

        return Cache::remember($key, now()->addSeconds($ttlSeconds), $callback);
    }

    public function getCountries(): array
    {
        return $this->cached(
            'football.countries',
            (int) config('services.football_api.cache.countries', self::CACHE_TTL_STATIC),
            fn () => $this->get('countries')
        );
    }

    public function getVenues(array $params = []): array
    {
        $cacheKey = $this->normalizedCacheKey('football.venues', $params);

        return $this->cached(
            $cacheKey,
            (int) config('services.football_api.cache.venues', self::CACHE_TTL_STATIC),
            fn () => $this->get('venues', $params)
        );
    }

    /**
     * Uncached venues request for sync jobs.
     */
    public function getVenuesFresh(array $params = []): array
    {
        return $this->get('venues', $params);
    }

    public function getVenueById(int $id): array
    {
        return $this->getVenues(['id' => $id]);
    }

    public function getTeams(array $params = []): array
    {
        $cacheKey = $this->normalizedCacheKey('football.teams', $params);

        return $this->cached(
            $cacheKey,
            (int) config('services.football_api.cache.teams', self::CACHE_TTL_STATIC),
            fn () => $this->get('teams', $params)
        );
    }

    public function getTeamById(int $id): array
    {
        return $this->getTeams(['id' => $id]);
    }

    public function getTeamsByLeague(int $leagueId, int $season): array
    {
        return $this->getTeams([
            'league' => $leagueId,
            'season' => $season,
        ]);
    }

    public function getFixtures(array $params = []): array
    {
        $cacheKey = $this->normalizedCacheKey('football.fixtures', $params);

        return $this->cached(
            $cacheKey,
            (int) config('services.football_api.cache.fixtures', self::CACHE_TTL_VOLATILE),
            fn () => $this->get('fixtures', $params)
        );
    }

    /**
     * Uncached fixtures request for sync jobs.
     */
    public function getFixturesFresh(array $params = []): array
    {
        return $this->get('fixtures', $params);
    }

    public function getFixtureById(int $fixtureId): array
    {
        return $this->getFixtures(['id' => $fixtureId]);
    }

    public function getLiveFixtures(?int $leagueId = null): array
    {
        $params = ['live' => $leagueId ?: 'all'];
        $cacheKey = $this->normalizedCacheKey('football.fixtures.live', $params);

        return $this->cached(
            $cacheKey,
            (int) config('services.football_api.cache.live', 15),
            fn () => $this->get('fixtures', $params)
        );
    }

    public function getFixtureEvents(int $fixtureId): array
    {
        return $this->cached(
            "football.fixture.events.{$fixtureId}",
            (int) config('services.football_api.cache.events', 60),
            fn () => $this->get('fixtures/events', ['fixture' => $fixtureId])
        );
    }

    public function getFixtureLineups(int $fixtureId): array
    {
        return $this->cached(
            "football.fixture.lineups.{$fixtureId}",
            (int) config('services.football_api.cache.lineups', 600),
            fn () => $this->get('fixtures/lineups', ['fixture' => $fixtureId])
        );
    }

    public function getFixtureStatistics(int $fixtureId): array
    {
        return $this->cached(
            "football.fixture.statistics.{$fixtureId}",
            (int) config('services.football_api.cache.statistics', 300),
            fn () => $this->get('fixtures/statistics', ['fixture' => $fixtureId])
        );
    }

    public function getLeagues(array $params = []): array
    {
        $cacheKey = $this->normalizedCacheKey('football.leagues', $params);

        return $this->cached(
            $cacheKey,
            (int) config('services.football_api.cache.leagues', self::CACHE_TTL_STATIC),
            fn () => $this->get('leagues', $params)
        );
    }

    public function getStandings(int $leagueId, int $season): array
    {
        return $this->cached(
            "football.standings.{$leagueId}.{$season}",
            (int) config('services.football_api.cache.standings', 3600),
            fn () => $this->get('standings', ['league' => $leagueId, 'season' => $season])
        );
    }

    public function getCoach(int $teamId): array
    {
        return $this->cached(
            "football.coach.{$teamId}",
            (int) config('services.football_api.cache.teams', self::CACHE_TTL_STATIC),
            fn () => $this->get('coachs', ['team' => $teamId])
        );
    }

    public function getSquad(int $teamId): array
    {
        return $this->cached(
            "football.squad.{$teamId}",
            (int) config('services.football_api.cache.teams', self::CACHE_TTL_STATIC),
            fn () => $this->get('players/squads', ['team' => $teamId])
        );
    }
}
