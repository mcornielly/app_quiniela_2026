<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class TeamShieldsSeeder extends Seeder
{
    public function run(): void
    {
        $zipPath = $this->resolveZipPath();
        $slugToIso = $this->slugToIsoMap();
        $countryNameToIso = $this->countryNameToIsoMap();
        $teamNameToIso = $this->teamNameToIsoMap();

        if ($zipPath && file_exists($zipPath)) {
            $zip = new ZipArchive();

            if ($zip->open($zipPath) === true) {
                Storage::disk('public')->makeDirectory('shield');

                foreach ($this->selectBestPngEntries($zip) as $entryName) {
                    $filename = basename($entryName);
                    $iso = $this->resolveIsoFromFilename(
                        pathinfo($filename, PATHINFO_FILENAME),
                        $slugToIso,
                        $countryNameToIso,
                        $teamNameToIso
                    );

                    if (!$iso) {
                        continue;
                    }

                    $contents = $zip->getFromName($entryName);

                    if ($contents === false) {
                        continue;
                    }

                    Storage::disk('public')->put("shield/{$iso}.png", $contents);
                }

                $zip->close();
            } else {
                $this->command?->error("Unable to open zip file: {$zipPath}");
            }
        } else {
            $this->command?->warn('No shield zip found (expected database/data/fifa-world-cup-2026-shield(.zip) or fifa-world-cup-2026.football-shield.zip).');
        }

        $this->cleanupLegacyShieldFiles($slugToIso);
        $updated = $this->syncTeamsFromExistingFiles($slugToIso, $countryNameToIso, $teamNameToIso);
        $updated += $this->applyFifaShieldFallback();

        $this->command?->info("Updated {$updated} teams with shield paths.");
    }

    private function syncTeamsFromExistingFiles(array $slugToIso, array $countryNameToIso = [], array $teamNameToIso = []): int
    {
        $existingFiles = [];

        foreach ($this->shieldFiles() as $path) {
            if (!str_ends_with(strtolower($path), '.png')) {
                continue;
            }

            $basename = pathinfo($path, PATHINFO_FILENAME);
            $iso = $this->resolveIsoFromFilename($basename, $slugToIso, $countryNameToIso, $teamNameToIso);

            if (!$iso) {
                continue;
            }

            $isIsoNamedFile = strtolower($basename) === $iso;

            if (!isset($existingFiles[$iso]) || $isIsoNamedFile) {
                $existingFiles[$iso] = $path;
            }
        }

        $updated = 0;

        Team::query()
            ->with('country')
            ->whereNotNull('country_id')
            ->get()
            ->each(function (Team $team) use ($existingFiles, &$updated) {
                $countryCode = strtolower((string) $team->country?->code);
                $shieldPath = $existingFiles[$countryCode] ?? null;

                if (!$shieldPath) {
                    return;
                }

                if ($team->shield_path !== $shieldPath) {
                    $team->forceFill(['shield_path' => $shieldPath])->save();
                    $updated++;
                }
            });

        return $updated;
    }

    private function cleanupLegacyShieldFiles(array $slugToIso): void
    {
        foreach ($this->shieldFiles() as $path) {
            $basename = pathinfo($path, PATHINFO_FILENAME);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $iso = $this->resolveIsoFromFilename($basename, $slugToIso);

            if (!$iso) {
                continue;
            }

            $target = "shield/{$iso}.{$extension}";

            if ($path === $target) {
                continue;
            }

            if (Storage::disk('public')->exists($target)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * Read shield files directly from the filesystem.
     * This avoids incomplete listings seen with Storage::files() on some Docker/Windows mounts.
     *
     * @return array<int, string>
     */
    private function shieldFiles(): array
    {
        $absolutePattern = storage_path('app/public/shield/*.png');
        $absoluteFiles = glob($absolutePattern) ?: [];

        return array_map(
            static fn (string $absolutePath): string => 'shield/' . basename($absolutePath),
            $absoluteFiles
        );
    }

    private function resolveIsoFromFilename(
        string $basename,
        array $slugToIso,
        array $countryNameToIso = [],
        array $teamNameToIso = []
    ): ?string
    {
        $basenameLower = strtolower($basename);
        $isoSet = array_flip(array_values($slugToIso));

        if (isset($isoSet[$basenameLower])) {
            return $basenameLower;
        }

        foreach ($this->filenameCandidates($basename) as $candidate) {
            if (isset($slugToIso[$candidate])) {
                return $slugToIso[$candidate];
            }
        }

        foreach ($this->filenameCandidates($basename) as $candidate) {
            if (isset($countryNameToIso[$candidate])) {
                return $countryNameToIso[$candidate];
            }

            if (isset($teamNameToIso[$candidate])) {
                return $teamNameToIso[$candidate];
            }
        }

        return null;
    }

    private function slugToIsoMap(): array
    {
        return [
            'alemania' => 'de',
            'arabiasaudita' => 'sa',
            'argelia' => 'dz',
            'algeria' => 'dz',
            'argentina' => 'ar',
            'argentina2' => 'ar',
            'australia' => 'au',
            'austria' => 'at',
            'bangladesh' => 'bd',
            'belgica' => 'be',
            'belgium' => 'be',
            'bolivia' => 'bo',
            'bosnia' => 'ba',
            'bosniaandherzegovina' => 'ba',
            'brasil' => 'br',
            'brazil' => 'br',
            'caboverde' => 'cv',
            'camerun' => 'cm',
            'canada' => 'ca',
            'chile' => 'cl',
            'china' => 'cn',
            'colombia' => 'co',
            'congodr' => 'cd',
            'coreadelsur' => 'kr',
            'costarica' => 'cr',
            'cotedivoire' => 'ci',
            'croacia' => 'hr',
            'croatia' => 'hr',
            'curacao' => 'cw',
            'dinamarca' => 'dk',
            'dutch' => 'nl',
            'ecuador' => 'ec',
            'egipto' => 'eg',
            'egypt' => 'eg',
            'elsalvador' => 'sv',
            'escocia' => 'gb-sct',
            'eslovaquia' => 'sk',
            'eslovenia' => 'si',
            'espana' => 'es',
            'spain' => 'es',
            'estonia' => 'ee',
            'finlandia' => 'fi',
            'francia' => 'fr',
            'france' => 'fr',
            'germany' => 'de',
            'gales' => 'gb-wls',
            'ghana' => 'gh',
            'haiti' => 'ht',
            'grecia' => 'gr',
            'guatemala' => 'gt',
            'honduras' => 'hn',
            'hungria' => 'hu',
            'inglaterra' => 'gb-eng',
            'england' => 'gb-eng',
            'iran' => 'ir',
            'irlanda' => 'ie',
            'irlandadelnorte' => 'gb-nir',
            'islandia' => 'is',
            'israel' => 'il',
            'italia' => 'it',
            'iraq' => 'iq',
            'jamaica' => 'jm',
            'japon' => 'jp',
            'japan' => 'jp',
            'jordan' => 'jo',
            'kosovo' => 'xk',
            'letonia' => 'lv',
            'marruecos' => 'ma',
            'morocco' => 'ma',
            'mexico' => 'mx',
            'nigeria' => 'ng',
            'noruega' => 'no',
            'norway' => 'no',
            'nuevazelanda' => 'nz',
            'newzealand' => 'nz',
            'paisesbajos' => 'nl',
            'panama' => 'pa',
            'paraguay' => 'py',
            'peru' => 'pe',
            'portuguesefootballfederation' => 'pt',
            'polonia' => 'pl',
            'portugal' => 'pt',
            'qatar' => 'qa',
            'republicacheca' => 'cz',
            'czechrepublic' => 'cz',
            'republicadominicana' => 'do',
            'republica_dominicana' => 'do',
            'rumania' => 'ro',
            'rusia' => 'ru',
            'saudiarabia' => 'sa',
            'sanmarino' => 'sm',
            'san_marino' => 'sm',
            'scotland' => 'gb-sct',
            'senegal' => 'sn',
            'serbia' => 'rs',
            'sudafrica' => 'za',
            'southafrica' => 'za',
            'southkorea' => 'kr',
            'suecia' => 'se',
            'sweden' => 'se',
            'suiza' => 'ch',
            'switzerland' => 'ch',
            'tahiti' => 'pf',
            'tunez' => 'tn',
            'tunisia' => 'tn',
            'turquia' => 'tr',
            'turkey' => 'tr',
            'ucrania' => 'ua',
            'uruguay' => 'uy',
            'fifa' => 'fifa',
            'usa' => 'us',
            'uzbekistan' => 'uz',
            'venezuela' => 've',
        ];
    }

    private function resolveZipPath(): ?string
    {
        $candidates = [
            base_path('database/data/fifa-world-cup-2026-shield'),
            base_path('database/data/fifa-world-cup-2026-shield.zip'),
            base_path('database/data/fifa-world-cup-2026.football-shield.zip'),
        ];

        // Optional local fallback. Keep disabled in Railway/production for deterministic deploys.
        if (filter_var(env('SEEDER_ALLOW_STORAGE_FALLBACK', false), FILTER_VALIDATE_BOOL)) {
            $candidates[] = storage_path('app/shield/fifa-world-cup-2026-shield');
            $candidates[] = storage_path('app/shield/fifa-world-cup-2026-shield.zip');
            $candidates[] = storage_path('app/shield/fifa-world-cup-2026.football-shield.zip');
        }

        // Legacy source kept as optional fallback, disabled by default.
        if (filter_var(env('SHIELD_IMPORT_LEGACY_ZIP', false), FILTER_VALIDATE_BOOL)) {
            $candidates[] = storage_path('app/shield/shield.zip');
        }

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function applyFifaShieldFallback(): int
    {
        $fifaShieldPath = 'shield/fifa.png';

        if (!Storage::disk('public')->exists($fifaShieldPath)) {
            return 0;
        }

        $updated = 0;

        Team::query()
            ->where(function ($query) {
                $query->whereNull('shield_path')->orWhere('shield_path', '');
            })
            ->get()
            ->each(function (Team $team) use (&$updated, $fifaShieldPath): void {
                $team->forceFill(['shield_path' => $fifaShieldPath])->save();
                $updated++;
            });

        return $updated;
    }

    /**
     * Keep one PNG by stem (country/team name), preferring medium assets for web (512x512 first).
     *
     * @return array<int, string>
     */
    private function selectBestPngEntries(ZipArchive $zip): array
    {
        $bestByStem = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            if (!$entryName || str_starts_with($entryName, '__MACOSX/') || !str_ends_with(strtolower($entryName), '.png')) {
                continue;
            }

            $stem = pathinfo(basename($entryName), PATHINFO_FILENAME);
            $score = $this->entryScore($entryName);

            if (!isset($bestByStem[$stem]) || $score > $bestByStem[$stem]['score']) {
                $bestByStem[$stem] = [
                    'entry' => $entryName,
                    'score' => $score,
                ];
            }
        }

        return array_values(array_map(
            static fn (array $item): string => $item['entry'],
            $bestByStem
        ));
    }

    private function entryScore(string $entryName): int
    {
        $entryName = strtolower($entryName);

        return match (true) {
            str_contains($entryName, '/512x512/') => 1000,
            str_contains($entryName, '/700x700/') => 900,
            str_contains($entryName, '/256x256/') => 800,
            str_contains($entryName, '/128x128/') => 700,
            str_contains($entryName, '/1500x1500/') => 600,
            str_contains($entryName, '/3000x3000/') => 500,
            str_contains($entryName, '/64x64/') => 400,
            default => 300,
        };
    }

    /**
     * @return array<int, string>
     */
    private function filenameCandidates(string $basename): array
    {
        $basename = strtolower($basename);
        $trimmed = preg_replace('/\.football-logos\.cc$/', '', $basename) ?? $basename;
        $trimmed = preg_replace('/-?national-team$/', '', $trimmed) ?? $trimmed;

        return array_values(array_unique([
            $this->normalize($basename),
            $this->normalize($trimmed),
        ]));
    }

    /**
     * @return array<string, string>
     */
    private function countryNameToIsoMap(): array
    {
        $map = [];

        Country::query()
            ->select(['name', 'code'])
            ->whereNotNull('code')
            ->get()
            ->each(function (Country $country) use (&$map): void {
                $slug = $this->normalize((string) $country->name);
                $iso = strtolower((string) $country->code);

                if ($slug !== '' && $iso !== '') {
                    $map[$slug] = $iso;
                }
            });

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function teamNameToIsoMap(): array
    {
        $map = [];

        Team::query()
            ->with('country:id,code')
            ->whereNotNull('country_id')
            ->get(['id', 'name', 'country_id'])
            ->each(function (Team $team) use (&$map): void {
                $slug = $this->normalize((string) $team->name);
                $iso = strtolower((string) $team->country?->code);

                if ($slug !== '' && $iso !== '') {
                    $map[$slug] = $iso;
                }
            });

        return $map;
    }

    private function normalize(?string $value): string
    {
        return Str::of($value ?? '')
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->value();
    }
}
