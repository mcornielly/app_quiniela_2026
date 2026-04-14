<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\GameHistory;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class FootballResetLive extends Command
{
    protected $signature = 'football:reset-live
        {--tournament-year=2026 : Tournament year}
        {--tournament-type=world_cup : Tournament type}
        {--only-statuses=in_progress,finished : Comma-separated game statuses to reset}
        {--keep-history : Keep game_histories rows}
        {--skip-cache-clear : Skip optimize:clear after reset}';

    protected $description = 'Reset live/finished games back to scheduled state and optionally clear historical live data';

    public function handle(): int
    {
        $year = (int) $this->option('tournament-year');
        $type = (string) $this->option('tournament-type');
        $statuses = collect(explode(',', (string) $this->option('only-statuses')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique()
            ->values();

        $tournament = Tournament::query()
            ->where('year', $year)
            ->where('type', $type)
            ->first();

        if (! $tournament) {
            $this->error("Tournament {$type} {$year} not found.");
            return self::FAILURE;
        }

        if ($statuses->isEmpty()) {
            $this->error('No statuses provided to reset.');
            return self::FAILURE;
        }

        $gamesQuery = Game::query()
            ->where('tournament_id', $tournament->id)
            ->whereIn('status', $statuses->all());

        $gamesCount = (clone $gamesQuery)->count();

        $this->info("Resetting Juego Directo for {$tournament->name} {$tournament->year}");
        $this->line('- Statuses: ' . $statuses->join(', '));
        $this->line("- Games to reset: {$gamesCount}");

        if ($gamesCount > 0) {
            $gamesQuery->update([
                'status' => 'scheduled',
                'home_score' => null,
                'away_score' => null,
                'winner_team_id' => null,
                'result_type' => null,
            ]);
        }

        $historyDeleted = 0;

        if (! $this->option('keep-history') && Schema::hasTable('game_histories')) {
            $historyDeleted = GameHistory::query()
                ->where('tournament_id', $tournament->id)
                ->delete();
        }

        if (! $this->option('skip-cache-clear')) {
            Artisan::call('optimize:clear');
            $this->line('Cache cleared with optimize:clear.');
        }

        $remainingGames = Game::query()
            ->where('tournament_id', $tournament->id)
            ->whereIn('status', $statuses->all())
            ->count();

        $remainingHistory = Schema::hasTable('game_histories')
            ? GameHistory::query()->where('tournament_id', $tournament->id)->count()
            : 0;

        $this->newLine();
        $this->info('Reset completed.');
        $this->line("- Remaining games in selected statuses: {$remainingGames}");
        $this->line("- Historical rows deleted: {$historyDeleted}");
        $this->line("- Remaining history rows for tournament: {$remainingHistory}");

        return self::SUCCESS;
    }
}
