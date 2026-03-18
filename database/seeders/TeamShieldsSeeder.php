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

        if (!file_exists($zipPath)) {
            $this->command?->warn('shield.zip was not found in storage/app/shield.');
            return;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            $this->command?->error('Unable to open shield.zip.');
            return;
        }

        Storage::disk('public')->makeDirectory('shield');

        $availableFiles = [];

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
            Storage::disk('public')->put("shield/{$filename}", $contents);
            $availableFiles[$this->normalize(pathinfo($filename, PATHINFO_FILENAME))] = "shield/{$filename}";
        }

        $zip->close();

        $updated = 0;

        Team::query()
            ->with('country')
            ->whereNotNull('country_id')
            ->get()
            ->each(function (Team $team) use ($availableFiles, &$updated) {
                $countryCode = strtolower((string) $team->country?->code);
                $candidates = $this->candidateSlugs($team->name, $team->country?->name, $countryCode);

                foreach ($candidates as $candidate) {
                    $shieldPath = $availableFiles[$candidate] ?? null;

                    if (!$shieldPath) {
                        continue;
                    }

                    if ($team->shield_path !== $shieldPath) {
                        $team->forceFill(['shield_path' => $shieldPath])->save();
                        $updated++;
                    }

                    return;
                }
            });

        $this->command?->info("Updated {$updated} teams with shield paths.");
    }

    private function candidateSlugs(?string $teamName, ?string $countryName, string $countryCode): array
    {
        $aliases = [
            'gb-eng' => ['inglaterra'],
            'kr' => ['coreadelsur'],
            'nl' => ['paisesbajos'],
            'sa' => ['arabiasaudita'],
            'cz' => ['republicacheca'],
            'do' => ['republica_dominicana'],
            'us' => ['usa'],
            'tn' => ['tunez'],
            'nz' => ['nuevazelanda'],
            'gb-wls' => ['gales'],
            'gb-nir' => ['irlandadelnorte'],
        ];

        $values = [
            $this->normalize($teamName),
            $this->normalize($countryName),
            ...($aliases[$countryCode] ?? []),
        ];

        return array_values(array_unique(array_filter($values)));
    }

    private function normalize(?string $value): string
    {
        $value = Str::of($value ?? '')
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->value();

        return match ($value) {
            'estadosunidos' => 'usa',
            'coreadelsur' => 'coreadelsur',
            'paisesbajos' => 'paisesbajos',
            'arabiasaudita' => 'arabiasaudita',
            'republicadominicana' => 'republica_dominicana',
            'republicacheca' => 'republicacheca',
            'sanmarino' => 'san_marino',
            default => $value,
        };
    }
}
