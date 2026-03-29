<?php

namespace App\Services\Tournament;

use App\Models\PoolEntry;
use App\Models\Rule;
use App\Models\Tournament;
use Carbon\CarbonInterface;

class PoolEntryRuleService
{
    public function resolveRuleForTournament(Tournament $tournament): Rule
    {
        $rule = Rule::query()
            ->where('active', true)
            ->where('tournament_id', $tournament->id)
            ->first();

        if ($rule) {
            return $rule;
        }

        $closeAt = $tournament->deadline_at ?: now()->setDate(2026, 6, 10)->endOfDay();
        $startsAt = $closeAt->copy()->addDay()->startOfDay();

        return new Rule([
            'tournament_id' => $tournament->id,
            'name' => 'Regla fallback de quiniela',
            'tournament_starts_at' => $startsAt,
            'participation_closes_at' => $closeAt,
            'exact_score_points' => 5,
            'correct_result_points' => 3,
            'unpaid_after_window_action' => 'locked',
            'active' => true,
        ]);
    }

    public function isParticipationOpen(Tournament $tournament, ?CarbonInterface $at = null): bool
    {
        $now = $at ?: now();
        $rule = $this->resolveRuleForTournament($tournament);

        if (!$rule->participation_closes_at) {
            return true;
        }

        return $now->lte($rule->participation_closes_at);
    }

    public function evaluatePoolEntry(PoolEntry $poolEntry, ?CarbonInterface $at = null): array
    {
        $poolEntry->loadMissing('tournament');

        $now = $at ?: now();
        $rule = $this->resolveRuleForTournament($poolEntry->tournament);
        $isPaid = $poolEntry->paid_at !== null;
        $isOpen = $this->isParticipationOpen($poolEntry->tournament, $now);

        if ($isPaid) {
            return [
                'status' => 'paid_locked',
                'is_locked' => true,
                'can_edit' => false,
                'can_inactivate' => false,
                'reason' => 'Quiniela pagada: activa y bloqueada.',
                'rule' => $rule,
            ];
        }

        if ($isOpen) {
            return [
                'status' => 'draft',
                'is_locked' => false,
                'can_edit' => true,
                'can_inactivate' => true,
                'reason' => 'Participacion abierta: se permite editar o inactivar.',
                'rule' => $rule,
            ];
        }

        $action = $rule->unpaid_after_window_action === 'cancelled' ? 'cancelled' : 'locked';

        return [
            'status' => $action,
            'is_locked' => true,
            'can_edit' => false,
            'can_inactivate' => false,
            'reason' => $action === 'cancelled'
                ? 'Participacion cerrada: quiniela no pagada cancelada automaticamente.'
                : 'Participacion cerrada: quiniela no pagada bloqueada.',
            'rule' => $rule,
        ];
    }

    public function syncPoolEntryStatus(PoolEntry $poolEntry): PoolEntry
    {
        $state = $this->evaluatePoolEntry($poolEntry);

        if ($poolEntry->status !== $state['status']) {
            $poolEntry->forceFill([
                'status' => $state['status'],
            ])->save();
        }

        return $poolEntry;
    }
}
