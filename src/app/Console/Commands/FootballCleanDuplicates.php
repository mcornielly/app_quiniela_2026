<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Game;
use App\Models\GroupStanding;
use App\Models\TournamentTeam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FootballCleanDuplicates extends Command
{
    protected $signature = 'football:clean-duplicates';
    protected $description = 'Merge duplicate teams sharing the same API ID or similar names';

    public function handle(): int
    {
        $this->info("Starting duplicate cleanup...");

        // 1. Merge by API Team ID (The most reliable match)
        $duplicates = Team::whereNotNull('api_team_id')
            ->select('api_team_id')
            ->groupBy('api_team_id')
            ->havingRaw('COUNT(id) > 1')
            ->pluck('api_team_id');

        foreach ($duplicates as $apiId) {
            $teams = Team::where('api_team_id', $apiId)->orderBy('id')->get();
            $this->mergeTeams($teams, "API ID: $apiId");
        }

        // 2. Merge by Name (case insensitive, normalized)
        // This handles cases where one has API ID and other doesn't
        $allTeams = Team::all();
        $grouped = $allTeams->groupBy(fn($t) => $this->normalize($t->name));

        foreach ($grouped as $normalizedName => $teams) {
            if ($teams->count() > 1) {
                $this->mergeTeams($teams, "Name: $normalizedName");
            }
        }

        $this->info("Cleanup finished.");
        return self::SUCCESS;
    }

    private function mergeTeams($teams, $reason)
    {
        // Strategy: 
        // 1. Find the "best" team (the one with API info or the one already in a group)
        $bestTeam = $teams->sortByDesc(function($t) {
            $score = 0;
            if ($t->api_team_id) $score += 100;
            if ($t->group_id) $score += 200; // Priority to teams already in groups
            if ($t->coach_name) $score += 50;
            return $score;
        })->first();

        $others = $teams->reject(fn($t) => $t->id === $bestTeam->id);

        if ($others->isEmpty()) return;

        $this->warn("Merging duplicates for $reason into {$bestTeam->name} (ID: {$bestTeam->id})");

        foreach ($others as $other) {
            $this->line("  - Merging ID: {$other->id} ({$other->name})");

            DB::transaction(function() use ($bestTeam, $other) {
                // Update Games
                Game::where('home_team_id', $other->id)->update(['home_team_id' => $bestTeam->id]);
                Game::where('away_team_id', $other->id)->update(['away_team_id' => $bestTeam->id]);

                // Update Tournament Entries (handle duplicates)
                $existingEntriesIds = TournamentTeam::where('team_id', $bestTeam->id)->pluck('tournament_id');
                TournamentTeam::where('team_id', $other->id)
                    ->whereIn('tournament_id', $existingEntriesIds)
                    ->delete();
                TournamentTeam::where('team_id', $other->id)->update(['team_id' => $bestTeam->id]);

                // Update Group Standings (handle duplicates)
                $existingStandingsGroups = GroupStanding::where('team_id', $bestTeam->id)->pluck('group_id');
                GroupStanding::where('team_id', $other->id)
                    ->whereIn('group_id', $existingStandingsGroups)
                    ->delete();
                GroupStanding::where('team_id', $other->id)->update(['team_id' => $bestTeam->id]);

                // Transfer Group Info if missing
                if (!$bestTeam->group_id && $other->group_id) {
                    $bestTeam->update([
                        'group_id' => $other->group_id,
                        'group_position' => $other->group_position
                    ]);
                }

                // Transfer Country Info if missing
                if (!$bestTeam->country_id && $other->country_id) {
                    $bestTeam->update(['country_id' => $other->country_id]);
                }

                // Delete the duplicate
                $other->delete();
            });
        }
    }

    private function normalize($string)
    {
        $string = str_replace('-', ' ', $string);
        $string = preg_replace('/[^a-zA-Z0-9]/', '', $string);
        return strtolower($string);
    }
}
