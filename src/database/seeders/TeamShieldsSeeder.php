<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class TeamShieldsSeeder extends Seeder
{
    public function run(): void
    {
        $zipPath = storage_path('app/shield/shield.zip');
        $slugToIso = $this->slugToIsoMap();

        if (file_exists($zipPath)) {
            $zip = new ZipArchive();

            if ($zip->open($zipPath) === true) {
                Storage::disk('public')->makeDirectory('shield');

                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $entryName = $zip->getNameIndex($index);

                    if (!$entryName || str_starts_with($entryName, '__MACOSX/') || !str_ends_with(strtolower($entryName), '.png')) {
                        continue;
                    }

                    $contents = $zip->getFromIndex($index);

                    if ($contents === false) {
                        continue;
                    }

                    $filename = basename($entryName);
                    $iso = $this->resolveIsoFromFilename(pathinfo($filename, PATHINFO_FILENAME), $slugToIso);

                    if (!$iso) {
                        continue;
                    }

                    Storage::disk('public')->put("shield/{$iso}.png", $contents);
                }

                $zip->close();
            } else {
                $this->command?->error('Unable to open shield.zip.');
            }
        } else {
            $this->command?->warn('shield.zip was not found in storage/app/shield.');
        }

        $this->cleanupLegacyShieldFiles($slugToIso);
        $updated = $this->syncTeamsFromExistingFiles($slugToIso);

        $this->command?->info("Updated {$updated} teams with shield paths.");
    }

    private function syncTeamsFromExistingFiles(array $slugToIso): int
    {
        $existingFiles = [];

        foreach (Storage::disk('public')->files('shield') as $path) {
            if (!str_ends_with(strtolower($path), '.png')) {
                continue;
            }

            $basename = pathinfo($path, PATHINFO_FILENAME);
            $iso = $this->resolveIsoFromFilename($basename, $slugToIso);

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
        foreach (Storage::disk('public')->files('shield') as $path) {
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

    private function resolveIsoFromFilename(string $basename, array $slugToIso): ?string
    {
        $basenameLower = strtolower($basename);
        $isoSet = array_flip(array_values($slugToIso));

        if (isset($isoSet[$basenameLower])) {
            return $basenameLower;
        }

        $slug = $this->normalize($basename);

        return $slugToIso[$slug] ?? null;
    }

    private function slugToIsoMap(): array
    {
        return [
            'alemania' => 'de',
            'arabiasaudita' => 'sa',
            'argelia' => 'dz',
            'argentina' => 'ar',
            'argentina2' => 'ar',
            'australia' => 'au',
            'austria' => 'at',
            'bangladesh' => 'bd',
            'belgica' => 'be',
            'bolivia' => 'bo',
            'bosnia' => 'ba',
            'brasil' => 'br',
            'camerun' => 'cm',
            'canada' => 'ca',
            'chile' => 'cl',
            'china' => 'cn',
            'colombia' => 'co',
            'coreadelsur' => 'kr',
            'costarica' => 'cr',
            'croacia' => 'hr',
            'dinamarca' => 'dk',
            'ecuador' => 'ec',
            'egipto' => 'eg',
            'elsalvador' => 'sv',
            'escocia' => 'gb-sct',
            'eslovaquia' => 'sk',
            'eslovenia' => 'si',
            'espana' => 'es',
            'estonia' => 'ee',
            'finlandia' => 'fi',
            'francia' => 'fr',
            'gales' => 'gb-wls',
            'ghana' => 'gh',
            'grecia' => 'gr',
            'guatemala' => 'gt',
            'honduras' => 'hn',
            'hungria' => 'hu',
            'inglaterra' => 'gb-eng',
            'iran' => 'ir',
            'irlanda' => 'ie',
            'irlandadelnorte' => 'gb-nir',
            'islandia' => 'is',
            'israel' => 'il',
            'italia' => 'it',
            'jamaica' => 'jm',
            'japon' => 'jp',
            'kosovo' => 'xk',
            'letonia' => 'lv',
            'marruecos' => 'ma',
            'mexico' => 'mx',
            'nigeria' => 'ng',
            'noruega' => 'no',
            'nuevazelanda' => 'nz',
            'paisesbajos' => 'nl',
            'panama' => 'pa',
            'paraguay' => 'py',
            'peru' => 'pe',
            'polonia' => 'pl',
            'portugal' => 'pt',
            'qatar' => 'qa',
            'republicacheca' => 'cz',
            'republicadominicana' => 'do',
            'republica_dominicana' => 'do',
            'rumania' => 'ro',
            'rusia' => 'ru',
            'sanmarino' => 'sm',
            'san_marino' => 'sm',
            'senegal' => 'sn',
            'serbia' => 'rs',
            'sudafrica' => 'za',
            'southafrica' => 'za',
            'suecia' => 'se',
            'suiza' => 'ch',
            'tahiti' => 'pf',
            'tunez' => 'tn',
            'turquia' => 'tr',
            'ucrania' => 'ua',
            'uruguay' => 'uy',
            'usa' => 'us',
            'venezuela' => 've',
        ];
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