<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Models\Tournament;
use App\Models\Group;
use App\Models\Team;
use App\Models\Game;

class WorldCupSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {

            $tournament = Tournament::where('year', 2026)->firstOrFail();

            $file = storage_path('app/WCup_2026_4.0_en2.xlsx');

            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getSheetByName('DailySchedule');

            $rows = $sheet->toArray();

            $stage = 'group';

            foreach ($rows as $row) {

                $firstColumn = trim((string)$row[0]);

                /*
                |--------------------------------------------------------------------------
                | Detect stage rows
                |--------------------------------------------------------------------------
                */

                if ($firstColumn === 'Round of 32') {
                    $stage = 'round_32';
                    continue;
                }

                if ($firstColumn === 'Round of 16') {
                    $stage = 'round_16';
                    continue;
                }

                if ($firstColumn === 'Quarter final') {
                    $stage = 'quarter';
                    continue;
                }

                if ($firstColumn === 'Semi-Final') {
                    $stage = 'semi';
                    continue;
                }

                if ($firstColumn === 'Third place') {
                    $stage = 'third_place';
                    continue;
                }

                if ($firstColumn === 'Final') {
                    $stage = 'final';
                    continue;
                }

                $matchNo = $row[4];

                if (!is_numeric($matchNo)) {
                    continue;
                }

                $date       = $row[0];
                $time       = $row[1];
                $teamName1  = $row[2];
                $teamName2  = $row[3];
                $slot1      = $row[5];
                $slot2      = $row[6];
                $venue      = $row[7];

                $homeTeam = $this->getTeam($teamName1, $slot1, $tournament->id);
                $awayTeam = $this->getTeam($teamName2, $slot2, $tournament->id);

                $matchDate = date('Y-m-d H:i:s', strtotime("$date $time"));

                Game::updateOrCreate(

                    [
                        'match_number' => $matchNo
                    ],

                    [
                        'tournament_id' => $tournament->id,
                        'home_team_id' => $homeTeam,
                        'away_team_id' => $awayTeam,
                        'stage' => $stage,
                        'venue' => $venue,
                        'match_date' => $matchDate
                    ]
                );
            }

            DB::commit();

            $this->command->info('World Cup 2026 schedule imported successfully.');

        } catch (\Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }

    private function getTeam($teamName, $slot, $tournamentId)
    {
        if (!$slot) {
            return null;
        }

        $groupLetter = substr($slot, 0, 1);

        $group = Group::firstOrCreate([
            'name' => $groupLetter,
            'tournament_id' => $tournamentId
        ]);

        $team = Team::firstOrCreate(

            [
                'name' => $teamName
            ],

            [
                'short_code' => $slot,
                'group_id' => $group->id,
                'type' => 'national'
            ]
        );

        DB::table('tournament_team')->updateOrInsert([

            'tournament_id' => $tournamentId,
            'team_id' => $team->id

        ]);

        return $team->id;
    }
}
