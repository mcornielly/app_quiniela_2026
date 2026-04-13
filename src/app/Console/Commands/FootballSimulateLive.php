<?php

namespace App\Console\Commands;

use App\Events\GameStatusUpdated;
use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FootballSimulateLive extends Command
{
    protected $signature = 'football:simulate-live
        {--tournament-year=2026 : Tournament year}
        {--tournament-type=world_cup : Tournament type}
        {--games=3 : Number of matches to simulate}
        {--ticks=18 : Number of simulation iterations}
        {--interval=8 : Seconds between iterations}
        {--goal-chance=28 : Goal probability per match/tick (0-100)}
        {--reset : Reset selected games before starting}
        {--finish : Mark games as finished at the end (can notify users)}';

    protected $description = 'Simulate live match progression for demo sessions using local games data';

    public function handle(): int
    {
        $year = (int) $this->option('tournament-year');
        $type = (string) $this->option('tournament-type');
        $gamesLimit = max(1, (int) $this->option('games'));
        $ticks = max(1, (int) $this->option('ticks'));
        $interval = max(1, (int) $this->option('interval'));
        $goalChance = max(0, min(100, (int) $this->option('goal-chance')));
        $reset = (bool) $this->option('reset');
        $finish = (bool) $this->option('finish');

        $tournament = Tournament::query()
            ->where('year', $year)
            ->where('type', $type)
            ->first();

        if (! $tournament) {
            $this->error("Tournament {$type} {$year} not found.");
            return self::FAILURE;
        }

        $games = $this->selectGames((int) $tournament->id, $gamesLimit);
        if ($games->isEmpty()) {
            $this->warn('No eligible games found to simulate.');
            return self::SUCCESS;
        }

        if ($reset) {
            foreach ($games as $game) {
                $game->update([
                    'status' => 'scheduled',
                    'home_score' => null,
                    'away_score' => null,
                    'winner_team_id' => null,
                    'result_type' => null,
                ]);
            }
            $games = $games->fresh(['homeTeam.country', 'awayTeam.country']);
        }

        $this->info('Starting live simulation session...');
        $this->line("- Tournament: {$tournament->name} {$tournament->year}");
        $this->line("- Games: {$games->count()} | Ticks: {$ticks} | Interval: {$interval}s | Goal chance: {$goalChance}%");
        if ($finish) {
            $this->warn('Finish mode enabled: selected games will be marked as finished at the end.');
        }
        $this->newLine();

        foreach ($games as $game) {
            $payload = [
                'status' => 'in_progress',
            ];

            if (! is_numeric($game->home_score)) {
                $payload['home_score'] = 0;
            }
            if (! is_numeric($game->away_score)) {
                $payload['away_score'] = 0;
            }

            $game->update($payload);
            $game->refresh();

            $this->line("▶ Match #{$game->match_number} {$this->teamName($game, 'home')} vs {$this->teamName($game, 'away')} started");
            $this->broadcastUpdate($game, 'start');
        }

        for ($tick = 1; $tick <= $ticks; $tick++) {
            foreach ($games as $game) {
                if (! $game->fresh() || $game->status !== 'in_progress') {
                    continue;
                }

                $this->simulateTick($game, $goalChance, $tick);
            }

            if ($tick < $ticks) {
                sleep($interval);
            }
        }

        if ($finish) {
            $this->finalizeGames($games);
        }

        $this->newLine();
        $this->info('Simulation completed.');

        return self::SUCCESS;
    }

    private function selectGames(int $tournamentId, int $limit): Collection
    {
        return Game::query()
            ->with(['homeTeam.country', 'awayTeam.country'])
            ->where('tournament_id', $tournamentId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('status')
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->orderBy('match_number')
            ->limit($limit)
            ->get();
    }

    private function simulateTick(Game $game, int $goalChance, int $tick): void
    {
        $home = is_numeric($game->home_score) ? (int) $game->home_score : 0;
        $away = is_numeric($game->away_score) ? (int) $game->away_score : 0;
        $goalScored = random_int(1, 100) <= $goalChance;
        $homeScored = false;
        $scorerName = null;

        if ($goalScored) {
            $homeScored = random_int(0, 1) === 1;
            if ($homeScored) {
                $home++;
            } else {
                $away++;
            }

            $scorerName = $this->resolveScorerName($game, $homeScored);
        }

        $game->update([
            'home_score' => $home,
            'away_score' => $away,
            'status' => 'in_progress',
        ]);
        $game->refresh();

        $minute = min(90, $tick * 5);
        $marker = $goalScored ? 'GOAL' : 'tick';
        $this->line("[{$marker} {$minute}'] #{$game->match_number} {$this->teamName($game, 'home')} {$home} - {$away} {$this->teamName($game, 'away')}");

        $this->broadcastUpdate($game, 'update', [
            'minute' => $minute,
            'playerName' => $scorerName,
        ]);
    }

    private function finalizeGames(Collection $games): void
    {
        $this->newLine();
        $this->info('Finalizing games...');

        foreach ($games as $game) {
            $fresh = $game->fresh();
            if (! $fresh) {
                continue;
            }

            $home = is_numeric($fresh->home_score) ? (int) $fresh->home_score : 0;
            $away = is_numeric($fresh->away_score) ? (int) $fresh->away_score : 0;

            $winner = null;
            $resultType = null;
            if ($home > $away) {
                $winner = $fresh->home_team_id;
                $resultType = 'normal';
            } elseif ($away > $home) {
                $winner = $fresh->away_team_id;
                $resultType = 'normal';
            }

            $fresh->update([
                'status' => 'finished',
                'winner_team_id' => $winner,
                'result_type' => $resultType,
            ]);

            $this->line("■ FT #{$fresh->match_number} {$this->teamName($fresh, 'home')} {$home} - {$away} {$this->teamName($fresh, 'away')}");
        }
    }

    private function teamName(Game $game, string $side): string
    {
        if ($side === 'home') {
            return $game->homeTeam?->name ?? ($game->home_slot ?: 'Local');
        }

        return $game->awayTeam?->name ?? ($game->away_slot ?: 'Visitante');
    }

    private function broadcastUpdate(Game $game, string $type, array $meta = []): void
    {
        try {
            broadcast(GameStatusUpdated::fromGame($game, $type, $meta));
        } catch (\Throwable) {
            // Keep simulation running even if websocket server is unavailable.
        }
    }

    private function resolveScorerName(Game $game, bool $homeScored): string
    {
        $team = $homeScored ? $game->homeTeam : $game->awayTeam;

        if (! $team) {
            return 'Jugador por confirmar';
        }

        $player = $team->players()
            ->inRandomOrder()
            ->value('name');

        return $player ?: 'Jugador por confirmar';
    }
}
