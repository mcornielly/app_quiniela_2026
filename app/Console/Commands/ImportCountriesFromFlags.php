<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportCountriesFromFlags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-countries-from-flags {zip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import countries from a ZIP file containing flag images';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $zipPath = $this->argument('zip');

        if (!file_exists($zipPath)) {
            $this->error("ZIP file not found: {$zipPath}");
            return Command::FAILURE;
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) === TRUE) {

            $this->info("Reading flags...");

            for ($i = 0; $i < $zip->numFiles; $i++) {

                $fileName = $zip->getNameIndex($i);

                if (!str_ends_with($fileName, '.png')) {
                    continue;
                }

                // ISO code desde nombre del archivo
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

                $this->line("Imported: {$code}");
            }

            $zip->close();

            $this->info("Countries imported successfully.");

            return Command::SUCCESS;

        }

        $this->error("Could not open ZIP file.");

        return Command::FAILURE;
    }
}
