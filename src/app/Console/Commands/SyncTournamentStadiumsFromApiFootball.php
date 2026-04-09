<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Stadium;
use App\Models\Tournament;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncTournamentStadiumsFromApiFootball extends Command
{
    protected $signature = 'football:sync:tournament-stadiums
        {--tournament-year=2026 : Local tournament year}
        {--refresh-cache : Forget country venue cache and fetch again}
        {--dry-run : Print actions without persisting changes}';

    protected $description = 'Sync stadiums used by tournament games using host-country cached API-FOOTBALL venues';

    public function handle(FootballApiService $footballApi): int
    {
        $tournamentYear = (int) $this->option('tournament-year');
        $refreshCache = (bool) $this->option('refresh-cache');
        $dryRun = (bool) $this->option('dry-run');

        $tournament = Tournament::query()
            ->where('type', 'world_cup')
            ->where('year', $tournamentYear)
            ->first();

        if (! $tournament) {
            $this->error("Tournament world_cup {$tournamentYear} not found.");
            return self::FAILURE;
        }

        if (! Schema::hasTable('stadiums')) {
            $this->error('Missing table "stadiums". Run migrations first: php artisan migrate');
            return self::FAILURE;
        }

        $hostCountries = $this->resolveHostCountries($tournament);
        if (empty($hostCountries)) {
            $this->error('Tournament host_countries is empty. Update the tournament hosts first.');
            return self::FAILURE;
        }

        [$validCountries, $invalidCountries] = $this->validateCountriesAgainstApi($footballApi, $hostCountries);

        if (! empty($invalidCountries)) {
            $this->warn('Unrecognized host countries (will be ignored): '.implode(', ', $invalidCountries));
        }

        if (empty($validCountries)) {
            $this->error('No valid host countries after validation. Nothing to sync.');
            return self::FAILURE;
        }

        $hostVenues = $this->loadHostCountryVenuesFromCache(
            api: $footballApi,
            tournament: $tournament,
            countries: $validCountries,
            refreshCache: $refreshCache
        );

        if ($hostVenues->isEmpty()) {
            $this->warn('No venues returned for validated host countries.');
            return self::SUCCESS;
        }

        $venueLabels = Game::query()
            ->where('tournament_id', $tournament->id)
            ->whereNotNull('venue')
            ->pluck('venue')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($venueLabels->isEmpty()) {
            $this->warn('No venue labels found in local games.');
            return self::SUCCESS;
        }

        $stats = [
            'countries_in_tournament' => count($hostCountries),
            'countries_valid' => count($validCountries),
            'countries_invalid' => count($invalidCountries),
            'cached_country_venues' => $hostVenues->count(),
            'labels_total' => $venueLabels->count(),
            'labels_matched' => 0,
            'labels_unmatched' => 0,
            'stadiums_created' => 0,
            'stadiums_updated' => 0,
            'games_linked' => 0,
        ];

        foreach ($venueLabels as $label) {
            $candidate = $this->resolveVenueCandidateFromCachedVenues($label, $hostVenues);

            if (! $candidate) {
                $stats['labels_unmatched']++;
                $this->warn("No venue match for: {$label}");
                continue;
            }

            $stats['labels_matched']++;
            $stadium = $this->upsertStadiumFromVenue($candidate, $dryRun, $stats);

            if (! $stadium || $dryRun) {
                continue;
            }

            $linked = Game::query()
                ->where('tournament_id', $tournament->id)
                ->whereRaw('LOWER(TRIM(venue)) = ?', [Str::lower($label)])
                ->update(['stadium_id' => $stadium->id]);

            $stats['games_linked'] += $linked;
        }

        $this->line('');
        $this->info($dryRun ? 'Dry-run stadium sync summary:' : 'Stadium sync summary:');
        $this->line("- Host countries in tournament: {$stats['countries_in_tournament']}");
        $this->line("- Host countries validated: {$stats['countries_valid']}");
        $this->line("- Host countries invalid: {$stats['countries_invalid']}");
        $this->line("- Cached venues from host countries: {$stats['cached_country_venues']}");
        $this->line("- Venue labels processed: {$stats['labels_total']}");
        $this->line("- Labels matched: {$stats['labels_matched']}");
        $this->line("- Labels unmatched: {$stats['labels_unmatched']}");
        $this->line("- Stadiums created: {$stats['stadiums_created']}");
        $this->line("- Stadiums updated: {$stats['stadiums_updated']}");
        $this->line("- Games linked to stadium_id: {$stats['games_linked']}");

        return self::SUCCESS;
    }

    private function resolveHostCountries(Tournament $tournament): array
    {
        $countries = $tournament->host_countries;

        if (is_string($countries)) {
            $countries = json_decode($countries, true);
        }

        if (! is_array($countries)) {
            return [];
        }

        return collect($countries)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function validateCountriesAgainstApi(FootballApiService $footballApi, array $countries): array
    {
        $payload = $footballApi->getCountries();
        $apiCountries = collect($payload['response'] ?? []);

        $known = [];
        foreach ($apiCountries as $country) {
            $name = $this->normalize((string) ($country['name'] ?? ''));
            $code = $this->normalize((string) ($country['code'] ?? ''));

            if ($name !== '') {
                $known[$name] = true;
            }
            if ($code !== '') {
                $known[$code] = true;
            }
        }

        $valid = [];
        $invalid = [];

        foreach ($countries as $country) {
            $apiCountry = $this->mapTournamentCountryForApi($country);
            $normalized = $this->normalize($apiCountry);

            if ($normalized !== '' && isset($known[$normalized])) {
                $valid[] = $country;
                continue;
            }

            $invalid[] = $country;
        }

        return [array_values(array_unique($valid)), array_values(array_unique($invalid))];
    }

    private function loadHostCountryVenuesFromCache(
        FootballApiService $api,
        Tournament $tournament,
        array $countries,
        bool $refreshCache
    ): Collection {
        $ttl = max(60, (int) config('services.football_api.cache.venues', 86400));

        $rows = collect();

        foreach ($countries as $country) {
            $apiCountry = $this->mapTournamentCountryForApi($country);
            $cacheKey = $this->countryVenueCacheKey((int) $tournament->id, (string) $tournament->year, $apiCountry);

            if ($refreshCache) {
                Cache::forget($cacheKey);
            }

            $countryRows = Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($api, $apiCountry) {
                $payload = $api->getVenues(['country' => $apiCountry]);

                return $payload['response'] ?? [];
            });

            $rows = $rows->concat(collect($countryRows)->map(fn ($row) => (array) $row));
        }

        return $rows
            ->filter(fn ($row) => is_array($row))
            ->unique(function (array $row) {
                $id = data_get($row, 'id', data_get($row, 'venue.id'));
                if (is_numeric($id)) {
                    return 'id:'.(string) $id;
                }

                $name = $this->normalize((string) data_get($row, 'name', data_get($row, 'venue.name', '')));
                $city = $this->normalize((string) data_get($row, 'city', data_get($row, 'venue.city', '')));

                return 'name_city:'.$name.'|'.$city;
            })
            ->values();
    }

    private function countryVenueCacheKey(int $tournamentId, string $year, string $country): string
    {
        return 'football.tournament.venues.'
            .$tournamentId.'.'
            .$year.'.'
            .Str::slug($country ?: 'unknown-country');
    }

    private function mapTournamentCountryForApi(string $country): string
    {
        $normalized = $this->normalize($country);

        return match ($normalized) {
            'united states', 'united states of america', 'us' => 'USA',
            default => $country,
        };
    }

    private function resolveVenueCandidateFromCachedVenues(string $label, Collection $venues): ?array
    {
        $best = null;
        $bestScore = -1;

        foreach ($venues as $venue) {
            $row = (array) $venue;
            $score = $this->scoreVenueCandidate($label, $row);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        return $bestScore >= 40 ? $best : null;
    }

    private function scoreVenueCandidate(string $label, array $venue): int
    {
        $labelNorm = $this->normalize($label);
        $labelAliases = $this->venueLabelAliases($labelNorm);
        $hints = $this->worldCup2026VenueHints()[$labelNorm] ?? null;
        $name = (string) data_get($venue, 'name', data_get($venue, 'venue.name', ''));
        $city = (string) data_get($venue, 'city', data_get($venue, 'venue.city', ''));
        $country = (string) data_get($venue, 'country', data_get($venue, 'venue.country', ''));

        $nameNorm = $this->normalize($name);
        $cityNorm = $this->normalize($city);
        $countryNorm = $this->normalize($country);

        $score = 0;
        foreach ($labelAliases as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if ($candidate === $nameNorm) {
                $score = max($score, 100);
            }
            if ($candidate === $cityNorm) {
                $score = max($score, 80);
            }
            if (str_contains($nameNorm, $candidate) || str_contains($candidate, $nameNorm)) {
                $score = max($score, 35);
            }
            if (str_contains($cityNorm, $candidate) || str_contains($candidate, $cityNorm)) {
                $score = max($score, 30);
            }
        }

        if (is_array($hints)) {
            $hintCity = $this->normalize((string) ($hints['city'] ?? ''));
            $hintCountry = $this->normalize((string) ($hints['country'] ?? ''));
            $hintNameTokens = collect($hints['name_tokens'] ?? [])->map(fn ($token) => $this->normalize((string) $token))->filter()->all();

            if ($hintCity !== '' && ($hintCity === $cityNorm || str_contains($cityNorm, $hintCity) || str_contains($hintCity, $cityNorm))) {
                $score = max($score, 90);
            }

            if ($hintCountry !== '' && ($hintCountry === $countryNorm || str_contains($countryNorm, $hintCountry) || str_contains($hintCountry, $countryNorm))) {
                $score += 10;
            }

            $matchedTokens = 0;
            foreach ($hintNameTokens as $token) {
                if ($token !== '' && (str_contains($nameNorm, $token) || str_contains($token, $nameNorm))) {
                    $matchedTokens++;
                }
            }
            if ($matchedTokens > 0) {
                $score += min(20, $matchedTokens * 10);
            }
        }

        if ($country !== '') {
            $score += 5;
        }

        return $score;
    }

    private function venueLabelAliases(string $label): array
    {
        $aliases = [$label];

        if ($label === 'mexico city') {
            $aliases[] = 'df';
            $aliases[] = 'd f';
            $aliases[] = 'ciudad de mexico';
            $aliases[] = 'mexico df';
        }

        return array_values(array_unique(array_filter($aliases)));
    }

    private function worldCup2026VenueHints(): array
    {
        return [
            'toronto' => ['city' => 'toronto', 'country' => 'canada', 'name_tokens' => ['toronto']],
            'vancouver' => ['city' => 'vancouver', 'country' => 'canada', 'name_tokens' => ['bc place']],
            'mexico city' => ['city' => 'd f', 'country' => 'mexico', 'name_tokens' => ['azteca', 'ciudad de mexico']],
            'guadalajara' => ['city' => 'zapopan', 'country' => 'mexico', 'name_tokens' => ['akron', 'guadalajara']],
            'monterrey' => ['city' => 'guadalupe', 'country' => 'mexico', 'name_tokens' => ['bbva', 'monterrey']],
            'atlanta' => ['city' => 'atlanta', 'country' => 'usa', 'name_tokens' => ['mercedes', 'benz']],
            'boston' => ['city' => 'foxborough', 'country' => 'usa', 'name_tokens' => ['gillette']],
            'dallas' => ['city' => 'arlington', 'country' => 'usa', 'name_tokens' => ['at t', 'cowboys']],
            'houston' => ['city' => 'houston', 'country' => 'usa', 'name_tokens' => ['nrg']],
            'kansas city' => ['city' => 'kansas city', 'country' => 'usa', 'name_tokens' => ['arrowhead', 'geha']],
            'los angeles' => ['city' => 'inglewood', 'country' => 'usa', 'name_tokens' => ['sofi']],
            'miami' => ['city' => 'miami gardens', 'country' => 'usa', 'name_tokens' => ['hard rock']],
            'new york new jersey' => ['city' => 'east rutherford', 'country' => 'usa', 'name_tokens' => ['metlife']],
            'philadelphia' => ['city' => 'philadelphia', 'country' => 'usa', 'name_tokens' => ['lincoln financial']],
            'san francisco bay area' => ['city' => 'santa clara', 'country' => 'usa', 'name_tokens' => ['levi s', 'levis']],
            'seattle' => ['city' => 'seattle', 'country' => 'usa', 'name_tokens' => ['lumen']],
        ];
    }

    private function upsertStadiumFromVenue(array $venue, bool $dryRun, array &$stats): ?Stadium
    {
        $apiVenueId = data_get($venue, 'id', data_get($venue, 'venue.id'));
        $name = (string) data_get($venue, 'name', data_get($venue, 'venue.name', ''));

        if (! is_numeric($apiVenueId) && trim($name) === '') {
            return null;
        }

        $payload = [
            'api_venue_id' => is_numeric($apiVenueId) ? (int) $apiVenueId : null,
            'name' => trim($name) !== '' ? trim($name) : 'Unknown Stadium',
            'city' => $this->nullableString(data_get($venue, 'city', data_get($venue, 'venue.city'))),
            'country' => $this->nullableString(data_get($venue, 'country', data_get($venue, 'venue.country'))),
            'address' => $this->nullableString(data_get($venue, 'address', data_get($venue, 'venue.address'))),
            'capacity' => is_numeric(data_get($venue, 'capacity', data_get($venue, 'venue.capacity')))
                ? (int) data_get($venue, 'capacity', data_get($venue, 'venue.capacity'))
                : null,
            'surface' => $this->nullableString(data_get($venue, 'surface', data_get($venue, 'venue.surface'))),
            'image_url' => $this->nullableString(data_get($venue, 'image', data_get($venue, 'venue.image'))),
        ];

        $stadium = null;
        if (is_numeric($payload['api_venue_id'])) {
            $stadium = Stadium::query()->where('api_venue_id', $payload['api_venue_id'])->first();
        }
        if (! $stadium && $payload['name']) {
            $stadium = Stadium::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($payload['name'])])
                ->when($payload['city'], fn ($q) => $q->whereRaw('LOWER(city) = ?', [Str::lower($payload['city'])]))
                ->first();
        }

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

        if ($changes) {
            if (! $dryRun) {
                $stadium->update($changes);
            }
            $stats['stadiums_updated']++;
        }

        return $stadium->fresh() ?: $stadium;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }
}
