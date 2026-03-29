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
        $zipPath = storage_path('app/flags/w2560.zip');

        if (!file_exists($zipPath)) {
            $this->command->error("ZIP file not found: {$zipPath}");
            return;
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) === TRUE) {

            for ($i = 0; $i < $zip->numFiles; $i++) {

                $fileName = $zip->getNameIndex($i);

                if (!str_ends_with($fileName, '.png')) {
                    continue;
                }

                $code = strtolower(pathinfo($fileName, PATHINFO_FILENAME));

                $content = $zip->getFromIndex($i);

                $flagPath = "flags/{$fileName}";

                Storage::disk('public')->put($flagPath, $content);

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
        }

        $this->command->info("Countries imported successfully.");
    }
}
