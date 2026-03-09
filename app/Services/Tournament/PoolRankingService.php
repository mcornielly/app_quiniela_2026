<?php

namespace App\Services\Tournament;

use App\Models\PoolEntry;

class PoolRankingService
{
    public function getRanking(int $tournamentId, int $limit = 15)
    {
        return PoolEntry::where('tournament_id', $tournamentId)
            ->orderByDesc('total_points')
            ->orderByDesc('exact_hits')
            ->orderByDesc('correct_results')
            ->limit($limit)
            ->get();
    }
}
