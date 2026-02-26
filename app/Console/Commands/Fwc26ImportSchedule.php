<?php

namespace App\Console\Commands;

use App\Models\MatchGame;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\WorldCupGroup;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Fwc26ImportSchedule extends Command
{
    protected $signature = 'fwc26:import-schedule
        {path : CSV path, e.g. database/data/fwc26_schedule_ve.csv}
        {--tournament=2026 : Tournament year}
        {--tz=America/Caracas : Timezone of times in CSV (Venezuela by default)}
        {--dry-run : Validate and show summary without writing}
    ';

    protected $description = 'Import FIFA World Cup 2026 schedule from CSV into match_games';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));
        $year = (int) $this->option('tournament');
        $tz = (string) $this->option('tz');
        $dryRun = (bool) $this->option('dry-run');

        if (!file_exists($path)) {
            $this->error("CSV not found: {$path}");
            return self::FAILURE;
        }

        $tournament = Tournament::query()->where('year', $year)->first();
        if (!$tournament) {
            $this->error("Tournament year {$year} not found. Seed tournaments first.");
            return self::FAILURE;
        }

        $rows = $this->readCsv($path);
        if (count($rows) === 0) {
            $this->error("CSV has no rows.");
            return self::FAILURE;
        }

        $this->info("Rows: " . count($rows));
        $this->info("Tournament: {$tournament->name} ({$tournament->year})");
        $this->info("CSV TZ: {$tz}");

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $tx = fn() => DB::transaction(function () use (
            $rows, $tournament, $tz, $dryRun, &$created, &$updated, &$skipped, &$errors
        ) {
            foreach ($rows as $i => $row) {
                $line = $i + 2; // header is line 1

                // Required columns
                $stage = trim((string)($row['stage'] ?? ''));
                $date  = trim((string)($row['date'] ?? ''));
                $time  = trim((string)($row['time'] ?? ''));
                $home  = trim((string)($row['home'] ?? ''));
                $away  = trim((string)($row['away'] ?? ''));
                $group = trim((string)($row['group'] ?? ''));
                $city  = trim((string)($row['city'] ?? ''));

                if ($stage === '' || $date === '' || $time === '' || $home === '' || $away === '') {
                    $this->warn("Line {$line}: missing required fields (stage/date/time/home/away). Skipping.");
                    $skipped++;
                    continue;
                }

                $startsAt = $this->parseDateTime($date, $time, $tz);
                if (!$startsAt) {
                    $this->warn("Line {$line}: invalid date/time '{$date} {$time}'. Skipping.");
                    $skipped++;
                    continue;
                }

                $groupCode = $group !== '' ? Str::upper($group) : null;

                // Decide if this is ref-based (KO) or team-based (group stage)
                [$homeTeamId, $homeRef] = $this->resolveSide($tournament->id, $home, $groupCode);
                [$awayTeamId, $awayRef] = $this->resolveSide($tournament->id, $away, $groupCode);

                // group_id if applicable
                $groupId = null;
                if ($groupCode) {
                    $groupModel = WorldCupGroup::query()
                        ->where('tournament_id', $tournament->id)
                        ->where('code', $groupCode)
                        ->first();

                    $groupId = $groupModel?->id;
                }

                // Find existing match by unique key (tournament+stage+starts_at+home/away identifiers)
                // We use a "best-effort" key to make import idempotent.
                $query = MatchGame::query()
                    ->where('tournament_id', $tournament->id)
                    ->where('stage', $stage)
                    ->where('starts_at', $startsAt->toDateTimeString())
                    ->where(function ($q) use ($homeTeamId, $homeRef) {
                        if ($homeTeamId) $q->where('home_team_id', $homeTeamId);
                        else $q->whereNull('home_team_id')->where('home_ref', $homeRef);
                    })
                    ->where(function ($q) use ($awayTeamId, $awayRef) {
                        if ($awayTeamId) $q->where('away_team_id', $awayTeamId);
                        else $q->whereNull('away_team_id')->where('away_ref', $awayRef);
                    });

                $existing = $query->first();

                $payload = [
                    'tournament_id' => $tournament->id,
                    'world_cup_group_id' => $groupId,
                    'group_code' => $groupCode,
                    'city' => $city !== '' ? $city : null,
                    'starts_at' => $startsAt->toDateTimeString(),
                    'timezone' => $tz,
                    'stage' => $stage,
                    'status' => 'scheduled',
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'home_ref' => $homeRef,
                    'away_ref' => $awayRef,
                ];

                if ($dryRun) {
                    // just validate
                    continue;
                }

                if ($existing) {
                    $existing->fill($payload);
                    if ($existing->isDirty()) {
                        $existing->save();
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    MatchGame::query()->create($payload);
                    $created++;
                }
            }
        });

        if ($dryRun) {
            $this->info("Dry run: no writes performed.");
            // Still validate parsing; no transaction needed
            try {
                foreach ($rows as $i => $row) {
                    $date  = trim((string)($row['date'] ?? ''));
                    $time  = trim((string)($row['time'] ?? ''));
                    if ($date && $time) $this->parseDateTime($date, $time, (string)$this->option('tz'));
                }
            } catch (\Throwable $e) {
                $this->error("Dry run failed: " . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            try {
                $tx();
            } catch (\Throwable $e) {
                $this->error("Import failed: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");
        $this->info("Errors: {$errors}");

        return self::SUCCESS;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) return [];

        $header = null;
        $rows = [];

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(fn($h) => Str::lower(trim($h)), $data);
                continue;
            }

            if (count($data) === 1 && trim((string)$data[0]) === '') continue;

            $row = [];
            foreach ($header as $idx => $key) {
                $row[$key] = $data[$idx] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function parseDateTime(string $date, string $time, string $tz): ?Carbon
    {
        // Accept dd/mm/yy OR dd/mm/yyyy
        $date = trim($date);
        $time = trim($time);

        // Normalize time like "3:00 PM" to "15:00"
        $time = Str::upper($time);

        $formats = [
            'd/m/y g:i A',
            'd/m/Y g:i A',
            'd/m/y H:i',
            'd/m/Y H:i',
        ];

        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, "{$date} {$time}", $tz);
                if ($dt) return $dt;
            } catch (\Throwable) {
                // continue
            }
        }

        return null;
    }

    /**
     * Return [teamId|null, ref|null]
     */
    private function resolveSide(int $tournamentId, string $value, ?string $groupCode): array
    {
        $v = trim($value);

        // KO refs examples: "2A", "1E", "3ABCDF"
        if (preg_match('/^\d+[A-Z]+$/', Str::upper($v))) {
            return [null, Str::upper($v)];
        }

        // If value is "REPECHAJE" use group placeholder
        if (Str::upper($v) === 'REPECHAJE') {
            // try group-specific placeholder first: your seeder uses "Repechaje UEFA"/"Repechaje FIFA"
            // For groups A,B,D,F have UEFA; groups I,K have FIFA in your groups image.
            // If groupCode is unknown, fallback to any placeholder.
            $placeholderName = null;
            if ($groupCode) {
                $uefaGroups = ['A','B','D','F'];
                $fifaGroups = ['I','K'];

                if (in_array($groupCode, $uefaGroups, true)) $placeholderName = 'Repechaje UEFA';
                if (in_array($groupCode, $fifaGroups, true)) $placeholderName = 'Repechaje FIFA';
            }

            $team = Team::query()
                ->where('tournament_id', $tournamentId)
                ->when($placeholderName, fn($q) => $q->where('name', $placeholderName))
                ->where('is_placeholder', true)
                ->first();

            if ($team) return [$team->id, null];

            // fallback: create generic placeholder
            $team = Team::query()->firstOrCreate(
                ['tournament_id' => $tournamentId, 'name' => 'Repechaje'],
                ['short_code' => 'PLAYOFF', 'is_placeholder' => true]
            );

            return [$team->id, null];
        }

        // Normalize common names from your calendar (Venezuela format) to match Team names
        $normalizedName = $this->normalizeTeamName($v);

        $team = Team::query()
            ->where('tournament_id', $tournamentId)
            ->where('name', $normalizedName)
            ->first();

        if ($team) return [$team->id, null];

        // fallback: try partial match
        $team = Team::query()
            ->where('tournament_id', $tournamentId)
            ->where('name', 'like', $normalizedName . '%')
            ->first();

        if ($team) return [$team->id, null];

        // If still not found, create a placeholder team record so import doesn't stop
        $team = Team::query()->firstOrCreate(
            ['tournament_id' => $tournamentId, 'name' => $normalizedName],
            ['short_code' => Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $normalizedName), 0, 3)), 'is_placeholder' => true]
        );

        return [$team->id, null];
    }

    private function normalizeTeamName(string $name): string
    {
        $n = Str::upper(trim($name));
        $map = [
            'MEXICO' => 'México',
            'SUDAFRICA' => 'Sudáfrica',
            'KOREA DEL SUR' => 'Korea del Sur',
            'CANADA' => 'Canadá',
            'SUIZA' => 'Suiza',
            'QATAR' => 'Qatar',
            'BRASIL' => 'Brasil',
            'MARRUECOS' => 'Marruecos',
            'ESCOCIA' => 'Escocia',
            'HAITI' => 'Haití',
            'ESTADOS UNIDOS' => 'Estados Unidos',
            'AUSTRALIA' => 'Australia',
            'PARAGUAY' => 'Paraguay',
            'ALEMANIA' => 'Alemania',
            'ECUADOR' => 'Ecuador',
            'COSTA DE MARFIL' => 'Costa Marfil',
            'CURACAO' => 'Curazao',
            'PAISES BAJOS' => 'Países Bajos',
            'JAPON' => 'Japón',
            'TUNEZ' => 'Túnez',
            'BELGICA' => 'Bélgica',
            'IRAN' => 'Irán',
            'EGIPTO' => 'Egipto',
            'NUEVA ZELANDA' => 'Nueva Zelanda',
            'ESPAÑA' => 'España',
            'ESPANA' => 'España',
            'URUGUAY' => 'Uruguay',
            'ARABIA SAUDITA' => 'Arabia Saudita',
            'CABO VERDE' => 'Cabo Verde',
            'FRANCIA' => 'Francia',
            'SENEGAL' => 'Senegal',
            'NORUEGA' => 'Noruega',
            'ARGENTINA' => 'Argentina',
            'AUSTRIA' => 'Austria',
            'ARGELIA' => 'Argelia',
            'JORDANIA' => 'Jordan',
            'JORDAN' => 'Jordan',
            'PORTUGAL' => 'Portugal',
            'COLOMBIA' => 'Colombia',
            'UZBEKISTAN' => 'Uzbekistan',
            'INGLATERRA' => 'Inglaterra',
            'CROACIA' => 'Croacia',
            'PANAMA' => 'Panamá',
            'GHANA' => 'Ghana',
        ];

        return $map[$n] ?? Str::title(Str::lower($name));
    }
}
