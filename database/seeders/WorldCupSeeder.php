<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use App\Models\Tournament;
use App\Models\Group;
use App\Models\Team;
use App\Models\Game;
use App\Models\Country;
use Carbon\Carbon;

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

            /*
            |--------------------------------------------------------------------------
            | Cache groups to avoid repeated queries
            |--------------------------------------------------------------------------
            */

            $groupsCache = [];

            foreach ($rows as $row) {

                $firstColumn = trim((string)$row[0]);

                /*
                |--------------------------------------------------------------------------
                | Detect stages
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
                $slot1      = trim((string)$row[5]);
                $slot2      = trim((string)$row[6]);
                $venue      = $row[7];

                /*
                |--------------------------------------------------------------------------
                | Convert date
                |--------------------------------------------------------------------------
                */

                if (is_numeric($date)) {

                    $matchDate = Date::excelToDateTimeObject($date)->format('Y-m-d');

                } else {

                    // remove weekday if present (Thu, Fri, etc.)
                    $date = trim($date);

                    if (str_contains($date, ',')) {
                        $parts = explode(',', $date);
                        $date = trim($parts[1]);
                    }

                    $matchDate = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');

                }

                /*
                |--------------------------------------------------------------------------
                | Convert time
                |--------------------------------------------------------------------------
                */

                $matchTime = Carbon::createFromFormat('H:i', $time)->format('H:i:s');

                /*
                |--------------------------------------------------------------------------
                | Resolve teams or slots
                |--------------------------------------------------------------------------
                */

                [$homeTeamId, $homeSlot] = $this->resolveTeam(
                    $teamName1,
                    $slot1,
                    $tournament->id,
                    $stage,
                    $groupsCache
                );

                [$awayTeamId, $awaySlot] = $this->resolveTeam(
                    $teamName2,
                    $slot2,
                    $tournament->id,
                    $stage,
                    $groupsCache
                );

                Game::updateOrCreate(

                    [
                        'match_number' => $matchNo
                    ],

                    [
                        'tournament_id' => $tournament->id,

                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,

                        'home_slot' => $homeSlot,
                        'away_slot' => $awaySlot,

                        'stage' => $stage,
                        'venue' => $venue,

                        'match_date' => $matchDate,
                        'match_time' => $matchTime
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

    private function resolveTeam($teamName, $slot, $tournamentId, $stage, &$groupsCache)
    {
        if (!$slot) {
            return [null, null];
        }

        /*
        |--------------------------------------------------------------------------
        | Knockout stage → keep slot reference
        |--------------------------------------------------------------------------
        */

        if ($stage !== 'group') {

            return [null, $slot];
        }

        /*
        |--------------------------------------------------------------------------
        | Group stage → resolve team normally
        |--------------------------------------------------------------------------
        */

        $groupLetter = substr($slot, 0, 1);
        $position = intval(substr($slot, 1));

        /*
        |--------------------------------------------------------------------------
        | Use cached group
        |--------------------------------------------------------------------------
        */

        if (!isset($groupsCache[$groupLetter])) {

            $groupsCache[$groupLetter] = Group::firstOrCreate([

                'name' => $groupLetter,
                'tournament_id' => $tournamentId

            ]);
        }

        $group = $groupsCache[$groupLetter];

        /*
        |--------------------------------------------------------------------------
        | Create team if needed
        |--------------------------------------------------------------------------
        */

        $countryId = $this->resolveCountryId($teamName);

        $team = Team::firstOrCreate(

            [
                'name' => $teamName,
                'type' => 'international'
            ],

            [
                'country_id' => $countryId,
                'group_id' => $group->id,
                'group_position' => $position
            ]
        );

        if ($countryId && !$team->country_id) {
            $team->update(['country_id' => $countryId]);
        }

        if (!$team->group_id || !$team->group_position) {
            $team->update([
                'group_id' => $group->id,
                'group_position' => $position,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Attach team to tournament
        |--------------------------------------------------------------------------
        */

        DB::table('tournament_team')->updateOrInsert([

            'tournament_id' => $tournamentId,
            'team_id' => $team->id

        ]);

        return [$team->id, null];
    }

    private function resolveCountryId(?string $teamName): ?int
    {
        $code = $this->resolveCountryCodeFromTeamName($teamName);

        if (!$code) {
            return null;
        }

        return Country::where('code', $code)->value('id');
    }

    private function resolveCountryCodeFromTeamName(?string $teamName): ?string
    {
        if (!$teamName) {
            return null;
        }

        $map = [
            'algeria' => 'dz',
            'argentina' => 'ar',
            'australia' => 'au',
            'austria' => 'at',
            'belgium' => 'be',
            'brazil' => 'br',
            'canada' => 'ca',
            'cape verde' => 'cv',
            'colombia' => 'co',
            'croatia' => 'hr',
            'curacao' => 'cw',
            'curaçao' => 'cw',
            'ecuador' => 'ec',
            'egypt' => 'eg',
            'england' => 'gb-eng',
            'france' => 'fr',
            'germany' => 'de',
            'ghana' => 'gh',
            'haiti' => 'ht',
            'ir iran' => 'ir',
            'iran' => 'ir',
            'ivory coast' => 'ci',
            'japan' => 'jp',
            'jordan' => 'jo',
            'mexico' => 'mx',
            'morocco' => 'ma',
            'netherlands' => 'nl',
            'new zealand' => 'nz',
            'norway' => 'no',
            'panama' => 'pa',
            'paraguay' => 'py',
            'portugal' => 'pt',
            'qatar' => 'qa',
            'rep. of korea' => 'kr',
            'republic of korea' => 'kr',
            'south korea' => 'kr',
            'saudi arabia' => 'sa',
            'scotland' => 'gb-sct',
            'senegal' => 'sn',
            'south africa' => 'za',
            'spain' => 'es',
            'switzerland' => 'ch',
            'tunisia' => 'tn',
            'uruguay' => 'uy',
            'usa' => 'us',
            'united states' => 'us',
            'uzbekistan' => 'uz',
        ];

        return $map[strtolower(trim($teamName))] ?? null;
    }
}
