<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $zipPath = $this->resolveFlagsZipPath();

        if (!$zipPath || !file_exists($zipPath)) {
            $this->command->error("ZIP file not found: {$zipPath}");
            return;
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) === TRUE) {
            $fifaFlagPath = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {

                $fileName = $zip->getNameIndex($i);

                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (!in_array($ext, ['png', 'svg'], true)) {
                    continue;
                }

                $code = strtolower(pathinfo($fileName, PATHINFO_FILENAME));

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    continue;
                }

                $flagPath = "flags/{$code}.{$ext}";

                Storage::disk('public')->put($flagPath, $content);

                if ($code === 'fifa') {
                    $fifaFlagPath = $flagPath;
                }

                DB::table('countries')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => strtoupper($code),
                        'flag_path' => $flagPath,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            $zip->close();

            $this->applyFifaFlagFallback($fifaFlagPath);
        }

        $this->command->info("Countries imported successfully.");
    }

    private function resolveFlagsZipPath(): ?string
    {
        $candidates = [
            base_path('database/data/flags.zip'),
            base_path('database/data/flags'),
            base_path('database/data/w2560.zip'),
        ];

        // Optional local fallback. Keep disabled in Railway/production for deterministic deploys.
        if (filter_var(env('SEEDER_ALLOW_STORAGE_FALLBACK', false), FILTER_VALIDATE_BOOL)) {
            $candidates[] = storage_path('app/flags/flags.zip');
            $candidates[] = storage_path('app/flags/flags');
            $candidates[] = storage_path('app/flags/w2560.zip');
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function applyFifaFlagFallback(?string $fifaFlagPath): void
    {
        if (!$fifaFlagPath) {
            $fifaFlagPath = $this->resolveExistingFifaFlagPath();
        }

        if (!$fifaFlagPath) {
            // No FIFA asset available, skip fallback setup gracefully.
            return;
        }

        DB::table('countries')->updateOrInsert(
            ['code' => 'fifa'],
            [
                'name' => 'FIFA',
                'flag_path' => $fifaFlagPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Ensure countries without flag use FIFA as default visual fallback.
        DB::table('countries')
            ->where(function ($query) {
                $query->whereNull('flag_path')->orWhere('flag_path', '');
            })
            ->update([
                'flag_path' => $fifaFlagPath,
                'updated_at' => now(),
            ]);
    }

    private function resolveExistingFifaFlagPath(): ?string
    {
        $candidates = ['flags/fifa.svg', 'flags/fifa.png'];

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
