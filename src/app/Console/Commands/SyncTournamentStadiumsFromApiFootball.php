<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Stadium;
use App\Models\Tournament;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class SyncTournamentStadiumsFromApiFootball extends Command
{
    protected $signature = 'football:sync:tournament-stadiums
        {--tournament-year=2026 : Local tournament year}
        {--dry-run : Print actions without persisting changes}';

    protected $description = 'Sync only stadiums used by tournament games (cross local venues with API-FOOTBALL venues)';

    public function handle(FootballApiService $footballApi): int
    {
        $tournamentYear = (int) $this->option('tournament-year');
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
            'labels_total' => $venueLabels->count(),
            'labels_matched' => 0,
            'labels_unmatched' => 0,
            'stadiums_created' => 0,
            'stadiums_updated' => 0,
            'games_linked' => 0,
        ];

        foreach ($venueLabels as $label) {
            $candidate = $this->resolveVenueCandidate($footballApi, $label);

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
        $this->line("- Venue labels processed: {$stats['labels_total']}");
        $this->line("- Labels matched: {$stats['labels_matched']}");
        $this->line("- Labels unmatched: {$stats['labels_unmatched']}");
        $this->line("- Stadiums created: {$stats['stadiums_created']}");
        $this->line("- Stadiums updated: {$stats['stadiums_updated']}");
        $this->line("- Games linked to stadium_id: {$stats['games_linked']}");

        return self::SUCCESS;
    }

    private function resolveVenueCandidate(FootballApiService $footballApi, string $label): ?array
    {
        $attempts = $this->buildVenueAttempts($label);

        $best = null;
        $bestScore = -1;

        foreach ($attempts as $params) {
            $params = array_filter($params, fn ($value) => trim((string) $value) !== '');
            if (empty($params)) {
                continue;
            }

            try {
                $payload = $footballApi->getVenuesFresh($params);
            } catch (\Throwable $e) {
                if (str_contains(Str::lower($e->getMessage()), 'request limit')) {
                    throw new RuntimeException('API request limit reached while syncing stadiums. Try again tomorrow or upgrade the plan.', 0, $e);
                }
                // Ignore invalid query combos and continue with next strategy.
                continue;
            }
            $rows = collect($payload['response'] ?? []);
            if (isset($params['id']) && $rows->isNotEmpty()) {
                return (array) $rows->first();
            }

            foreach ($rows as $row) {
                $score = $this->scoreVenueCandidate($label, (array) $row);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = (array) $row;
                }
            }
        }

        // avoid weak/accidental match
        if ($bestScore < 40) {
            return null;
        }

        return $best;
    }

    private function scoreVenueCandidate(string $label, array $venue): int
    {
        $labelNorm = $this->normalize($label);
        $name = (string) data_get($venue, 'name', data_get($venue, 'venue.name', ''));
        $city = (string) data_get($venue, 'city', data_get($venue, 'venue.city', ''));
        $country = (string) data_get($venue, 'country', data_get($venue, 'venue.country', ''));

        $nameNorm = $this->normalize($name);
        $cityNorm = $this->normalize($city);

        $score = 0;
        if ($labelNorm !== '' && $labelNorm === $nameNorm) {
            $score += 100;
        }
        if ($labelNorm !== '' && $labelNorm === $cityNorm) {
            $score += 80;
        }
        if ($labelNorm !== '' && str_contains($nameNorm, $labelNorm)) {
            $score += 35;
        }
        if ($labelNorm !== '' && str_contains($cityNorm, $labelNorm)) {
            $score += 30;
        }
        if ($country !== '') {
            $score += 5;
        }

        return $score;
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

    private function normalizeForApiSearch(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9 ]+/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    private function extractSearchTerms(string $label): array
    {
        $terms = [];
        $normalizedFull = $this->normalizeForApiSearch($label);
        if ($normalizedFull !== '') {
            $terms[] = $normalizedFull;
        }

        $parts = preg_split('/[\/|,-]+/', $label) ?: [];
        foreach ($parts as $part) {
            $term = $this->normalizeForApiSearch((string) $part);
            if ($term !== '') {
                $terms[] = $term;
            }
        }

        return array_values(array_unique($terms));
    }

    private function buildVenueAttempts(string $label): array
    {
        $map = $this->worldCup2026VenueMap();
        $labelNorm = $this->normalize($label);

        $attempts = [];
        $isCurated = isset($map[$labelNorm]);

        if ($isCurated) {
            $entry = $map[$labelNorm];
            if (! empty($entry['id'])) {
                $attempts[] = ['id' => (string) $entry['id']];
            }
            if (! empty($entry['name']) && ! empty($entry['country'])) {
                $attempts[] = ['name' => $entry['name'], 'country' => $entry['country']];
                $attempts[] = ['search' => $entry['name']];
            }
            if (! empty($entry['city'])) {
                $attempts[] = ['city' => $entry['city'], 'country' => $entry['country'] ?? null];
            }
        }

        // For curated hosts, do not fallback to broad generic city search to avoid false matches.
        if (! $isCurated) {
            $terms = $this->extractSearchTerms($label);
            foreach ($terms as $term) {
                $attempts[] = ['search' => $term];
                $attempts[] = ['city' => $term];
                $attempts[] = ['name' => $term];
            }
        }

        // normalize + remove empty/null values
        $normalized = [];
        foreach ($attempts as $params) {
            $clean = [];
            foreach ($params as $key => $value) {
                $safe = $this->normalizeForApiSearch((string) $value);
                if ($safe !== '') {
                    $clean[$key] = $safe;
                }
            }
            if (! empty($clean)) {
                $normalized[] = $clean;
            }
        }

        // deduplicate attempts
        $unique = [];
        $seen = [];
        foreach ($normalized as $item) {
            ksort($item);
            $hash = md5(json_encode($item));
            if (! isset($seen[$hash])) {
                $seen[$hash] = true;
                $unique[] = $item;
            }
        }

        return $unique;
    }

    private function worldCup2026VenueMap(): array
    {
        return [
            'mexico city' => ['id' => 1069, 'name' => 'Estadio Azteca', 'country' => 'Mexico', 'city' => 'D F'],
            'guadalajara' => ['id' => 1076, 'name' => 'Estadio AKRON', 'country' => 'Mexico', 'city' => 'Zapopan'],
            'monterrey' => ['id' => 11905, 'name' => 'Estadio BBVA', 'country' => 'Mexico', 'city' => 'Guadalupe'],
            'toronto' => ['id' => 312, 'name' => 'BMO Field', 'country' => 'Canada', 'city' => 'Toronto'],
            'vancouver' => ['id' => 19445, 'name' => 'BC Place', 'country' => 'Canada', 'city' => 'Vancouver'],
            'seattle' => ['id' => 11534, 'name' => 'Lumen Field', 'country' => 'USA', 'city' => 'Seattle'],
            'san francisco bay area' => ['name' => 'Levi S Stadium', 'country' => 'USA', 'city' => 'Santa Clara'],
            'los angeles' => ['name' => 'SoFi Stadium', 'country' => 'USA', 'city' => 'Inglewood'],
            'kansas city' => ['name' => 'Arrowhead Stadium', 'country' => 'USA', 'city' => 'Kansas City'],
            'dallas' => ['name' => 'AT T Stadium', 'country' => 'USA', 'city' => 'Arlington'],
            'houston' => ['name' => 'NRG Stadium', 'country' => 'USA', 'city' => 'Houston'],
            'atlanta' => ['id' => 1898, 'name' => 'Mercedes-Benz Stadium', 'country' => 'USA', 'city' => 'Atlanta'],
            'miami' => ['name' => 'Hard Rock Stadium', 'country' => 'USA', 'city' => 'Miami Gardens'],
            'boston' => ['id' => 1618, 'name' => 'Gillette Stadium', 'country' => 'USA', 'city' => 'Foxborough'],
            'philadelphia' => ['name' => 'Lincoln Financial Field', 'country' => 'USA', 'city' => 'Philadelphia'],
            'new york new jersey' => ['name' => 'MetLife Stadium', 'country' => 'USA', 'city' => 'East Rutherford'],
        ];
    }
}
