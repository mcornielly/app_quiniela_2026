<?php

namespace App\Services\Tournament;

use App\Models\Game;
use Illuminate\Support\Collection;
use RuntimeException;

class ThirdPlaceAssignmentService
{
    public function assign(Collection $qualifiedThirdRows, Collection $round32Games, ?string $bracketKey = null): array
    {
        $qualifiedThirdRows = $qualifiedThirdRows->values();
        $qualifiedByGroup = $qualifiedThirdRows->keyBy(fn (array $row) => $row['team']->group?->name);

        $slotCandidates = [];

        foreach ($round32Games as $game) {
            foreach (['home', 'away'] as $side) {
                $slot = $side === 'home' ? $game->home_slot : $game->away_slot;

                if (!preg_match('/^3\-([A-Z]+)$/', (string) $slot, $matches)) {
                    continue;
                }

                $allowedGroups = collect(str_split($matches[1]))
                    ->filter(fn (string $group) => $qualifiedByGroup->has($group))
                    ->values()
                    ->all();

                $slotKey = $this->slotKey($game, $side);

                $slotCandidates[$slotKey] = [
                    'match_number' => $game->match_number,
                    'side' => $side,
                    'slot' => $slot,
                    'allowed_groups' => $allowedGroups,
                ];
            }
        }

        $configuredAssignment = $this->resolveConfiguredAssignment(
            $qualifiedByGroup,
            $slotCandidates,
            $bracketKey
        );

        if ($configuredAssignment !== null) {
            return $configuredAssignment;
        }

        return $this->resolveSequentialAssignment($slotCandidates, $qualifiedByGroup);
    }

    private function resolveConfiguredAssignment(
        Collection $qualifiedByGroup,
        array $slotCandidates,
        ?string $bracketKey
    ): ?array {
        if (!$bracketKey) {
            return null;
        }

        $matrix = config("tournament_brackets.{$bracketKey}.third_place_matrix", []);
        if (!is_array($matrix) || $matrix === []) {
            return null;
        }

        $combinationKey = $qualifiedByGroup
            ->keys()
            ->sort()
            ->implode('');

        $configuredMatches = $matrix[$combinationKey] ?? null;
        if (!is_array($configuredMatches) || $configuredMatches === []) {
            return null;
        }

        $assignment = [];

        foreach ($slotCandidates as $slotKey => $slotCandidate) {
            $groupLetter = $configuredMatches[$slotCandidate['match_number']] ?? null;

            if (!$groupLetter || !$qualifiedByGroup->has($groupLetter)) {
                throw new RuntimeException("La matriz de terceros no define el partido {$slotCandidate['match_number']} para la combinacion {$combinationKey}.");
            }

            if (!in_array($groupLetter, $slotCandidate['allowed_groups'], true)) {
                throw new RuntimeException("La matriz de terceros asigna el grupo {$groupLetter} a un slot invalido ({$slotCandidate['slot']}).");
            }

            $assignment[$slotKey] = $qualifiedByGroup->get($groupLetter);
        }

        if (count(collect($assignment)->map(fn (array $row) => $row['team']->group?->name)->unique()) !== count($assignment)) {
            throw new RuntimeException("La matriz de terceros repite grupos para la combinacion {$combinationKey}.");
        }

        return $assignment;
    }

    private function resolveSequentialAssignment(
        array $slotCandidates,
        Collection $qualifiedByGroup,
    ): array {
        $orderedSlots = collect($slotCandidates)
            ->sort(function (array $left, array $right) {
                return
                    ($left['match_number'] <=> $right['match_number'])
                    ?: strcmp($left['side'], $right['side']);
            })
            ->values();

        $usedGroups = [];
        $assignment = [];

        foreach ($orderedSlots as $slotCandidate) {
            $selectedGroup = collect($slotCandidate['allowed_groups'])
                ->reject(fn (string $group) => isset($usedGroups[$group]))
                ->sort(function (string $left, string $right) use ($qualifiedByGroup) {
                    $leftIndex = $qualifiedByGroup->keys()->search($left);
                    $rightIndex = $qualifiedByGroup->keys()->search($right);

                    return $leftIndex <=> $rightIndex ?: strcmp($left, $right);
                })
                ->first();

            if (!$selectedGroup || !$qualifiedByGroup->has($selectedGroup)) {
                throw new RuntimeException("No se pudo resolver la asignacion secuencial de terceros para el partido {$slotCandidate['match_number']}.");
            }

            $assignment["{$slotCandidate['match_number']}:{$slotCandidate['side']}"] = $qualifiedByGroup->get($selectedGroup);
            $usedGroups[$selectedGroup] = true;
        }

        return $assignment;
    }

    public function slotKey(Game $game, string $side): string
    {
        return "{$game->match_number}:{$side}";
    }
}
