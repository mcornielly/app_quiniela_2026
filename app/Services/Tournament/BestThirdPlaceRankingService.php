<?php

namespace App\Services\Tournament;

use Illuminate\Support\Collection;

class BestThirdPlaceRankingService
{
    public function rank(Collection $groupStandings): Collection
    {
        return $groupStandings
            ->map(fn (array $rows) => $rows[2] ?? null)
            ->filter()
            ->sort([$this, 'compareRows'])
            ->values();
    }

    public function compareRows(array $left, array $right): int
    {
        return
            $right['points'] <=> $left['points']
            ?: $right['gd'] <=> $left['gd']
            ?: $right['gf'] <=> $left['gf']
            ?: $this->fairPlayScore($right) <=> $this->fairPlayScore($left)
            ?: $this->rankingScore($right) <=> $this->rankingScore($left)
            ?: strcmp((string) ($left['team']->group?->name ?? ''), (string) ($right['team']->group?->name ?? ''))
            ?: strcmp((string) ($left['team']->name ?? ''), (string) ($right['team']->name ?? ''));
    }

    private function fairPlayScore(array $row): float
    {
        return (float) ($row['fair_play'] ?? 0);
    }

    private function rankingScore(array $row): float
    {
        return (float) ($row['ranking_score'] ?? 0);
    }
}
