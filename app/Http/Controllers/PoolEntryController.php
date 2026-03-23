<?php

namespace App\Http\Controllers;

use App\Events\AdminPoolActivityBroadcast;
use App\Models\Game;
use App\Models\PoolEntry;
use App\Models\Prediction;
use App\Models\Rule;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\AdminPoolActivityNotification;
use App\Services\Tournament\PoolEntryRuleService;
use App\Services\Tournament\PredictionBracketResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PoolEntryController extends Controller
{
    public function __construct(
        private readonly PredictionBracketResolverService $predictionBracketResolverService,
        private readonly PoolEntryRuleService $poolEntryRuleService,
    ) {
    }

    public function index(Request $request): Response
    {
        $poolEntries = PoolEntry::query()
            ->withTrashed()
            ->with([
                'tournament:id,name,year',
                'predictions' => fn ($query) => $query
                    ->with([
                        'game:id,match_date,match_time,home_team_id,away_team_id,home_slot,away_slot,home_score,away_score,status',
                        'game.homeTeam:id,name,country_id',
                        'game.homeTeam.country:id,code,flag_path',
                        'game.awayTeam:id,name,country_id',
                        'game.awayTeam.country:id,code,flag_path',
                        'predictedHomeTeam:id,name,country_id',
                        'predictedHomeTeam.country:id,code,flag_path',
                        'predictedAwayTeam:id,name,country_id',
                        'predictedAwayTeam.country:id,code,flag_path',
                    ])
                    ->latest('id'),
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (PoolEntry $poolEntry) {
                if (!$poolEntry->trashed()) {
                    $this->poolEntryRuleService->syncPoolEntryStatus($poolEntry);
                }
                $state = $this->poolEntryRuleService->evaluatePoolEntry($poolEntry);
                $orderedPredictions = $poolEntry->predictions
                    ->filter(fn ($prediction) => $prediction->game !== null)
                    ->sortByDesc(fn ($prediction) => ($prediction->game->match_date?->format('Y-m-d') ?? '0000-00-00') . ' ' . ($prediction->game->match_time ?? '00:00:00'))
                    ->values();

                $playedPredictions = $orderedPredictions
                    ->filter(fn ($prediction) => $prediction->game->status === 'finished'
                        && is_numeric($prediction->game->home_score)
                        && is_numeric($prediction->game->away_score))
                    ->values();

                $previewPredictions = ($playedPredictions->isNotEmpty() ? $playedPredictions : $orderedPredictions)
                    ->take(3)
                    ->map(function ($prediction) {
                        $game = $prediction->game;
                        $predictedHomeTeam = $prediction->predictedHomeTeam ?? $game->homeTeam;
                        $predictedAwayTeam = $prediction->predictedAwayTeam ?? $game->awayTeam;
                        $homeCode = strtoupper($predictedHomeTeam?->country?->code ?? preg_replace('/[^A-Za-z]/', '', $game->home_slot ?: 'TBD'));
                        $awayCode = strtoupper($predictedAwayTeam?->country?->code ?? preg_replace('/[^A-Za-z]/', '', $game->away_slot ?: 'TBD'));

                        return [
                            'homeName' => $predictedHomeTeam?->name ?? $game->home_slot ?? 'TBD',
                            'awayName' => $predictedAwayTeam?->name ?? $game->away_slot ?? 'TBD',
                            'homeCode' => Str::substr($homeCode, 0, 3),
                            'awayCode' => Str::substr($awayCode, 0, 3),
                            'homeFlagUrl' => $this->flagUrl($predictedHomeTeam?->country?->flag_path),
                            'awayFlagUrl' => $this->flagUrl($predictedAwayTeam?->country?->flag_path),
                            'predictedHomeScore' => (int) $prediction->home_score,
                            'predictedAwayScore' => (int) $prediction->away_score,
                            'predictedScore' => "{$prediction->home_score} - {$prediction->away_score}",
                            'actualScore' => is_numeric($game->home_score) && is_numeric($game->away_score)
                                ? "{$game->home_score} - {$game->away_score}"
                                : null,
                            'awardedPoints' => is_numeric($game->home_score) && is_numeric($game->away_score)
                                ? (int) ($prediction->points ?? 0)
                                : null,
                        ];
                    })
                    ->values();

                $pointsEarned = (int) $poolEntry->predictions->sum('points');

                return [
                    'id' => $poolEntry->id,
                    'registrationNumber' => $poolEntry->id,
                    'name' => $poolEntry->name,
                    'status' => $poolEntry->trashed() ? 'inactive' : $poolEntry->status,
                    'isInactive' => $poolEntry->trashed(),
                    'isLocked' => $state['is_locked'],
                    'canEdit' => $state['can_edit'],
                    'canInactivate' => !$poolEntry->trashed() && $state['can_inactivate'],
                    'canRestore' => $poolEntry->trashed() && $state['can_inactivate'],
                    'completionPercent' => $poolEntry->completion_percent,
                    'totalPoints' => $pointsEarned,
                    'pointsEarned' => $pointsEarned,
                    'exactHits' => $poolEntry->exact_hits,
                    'correctResults' => $poolEntry->correct_results,
                    'createdAt' => $poolEntry->created_at?->format('d/m/Y H:i'),
                    'createdDate' => $poolEntry->created_at?->format('Y-m-d'),
                    'matchesCount' => (int) $playedPredictions->count(),
                    'latestPredictions' => $previewPredictions,
                    'tournament' => [
                        'id' => $poolEntry->tournament?->id,
                        'name' => $poolEntry->tournament?->name,
                        'year' => $poolEntry->tournament?->year,
                    ],
                ];
            });

        return Inertia::render('Pools/Index', [
            'poolEntries' => $poolEntries,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Pools/Create');
    }

    public function show(Request $request, PoolEntry $poolEntry): Response
    {
        abort_unless((int) $poolEntry->user_id === (int) $request->user()->id, 403);

        $poolEntry->load([
            'tournament:id,name,year',
            'predictions' => fn ($query) => $query
                ->with([
                    'game:id,match_number,stage,match_date,match_time,venue,status,home_team_id,away_team_id,home_slot,away_slot,home_score,away_score',
                    'game.homeTeam:id,name,country_id,group_id',
                    'game.homeTeam.country:id,code,flag_path',
                    'game.awayTeam:id,name,country_id,group_id',
                    'game.awayTeam.country:id,code,flag_path',
                    'game.homeTeam.group:id,name',
                    'game.awayTeam.group:id,name',
                    'predictedHomeTeam:id,name,country_id,group_id',
                    'predictedHomeTeam.country:id,code,flag_path',
                    'predictedHomeTeam.group:id,name',
                    'predictedAwayTeam:id,name,country_id,group_id',
                    'predictedAwayTeam.country:id,code,flag_path',
                    'predictedAwayTeam.group:id,name',
                ])
                ->latest('id'),
        ]);
        $this->poolEntryRuleService->syncPoolEntryStatus($poolEntry);
        $state = $this->poolEntryRuleService->evaluatePoolEntry($poolEntry);

        $predictions = $poolEntry->predictions
            ->filter(fn ($prediction) => $prediction->game !== null)
            ->sortBy(function ($prediction) {
                $stageOrder = [
                    'group' => 10,
                    'round_32' => 20,
                    'round_16' => 30,
                    'quarter' => 40,
                    'semi' => 50,
                    'third_place' => 60,
                    'final' => 70,
                ];

                $stageKey = $stageOrder[$prediction->game->stage] ?? 999;
                $matchNumber = (int) ($prediction->game->match_number ?? 999);
                $dateKey = $prediction->game->match_date?->format('Y-m-d') ?? '9999-12-31';
                $timeKey = $prediction->game->match_time ?? '23:59:59';

                return sprintf('%03d-%03d-%s-%s', $stageKey, $matchNumber, $dateKey, $timeKey);
            })
            ->values()
            ->map(function ($prediction) {
                $game = $prediction->game;
                $predictedHomeTeam = $prediction->predictedHomeTeam ?? $game->homeTeam;
                $predictedAwayTeam = $prediction->predictedAwayTeam ?? $game->awayTeam;
                $hasOfficialResult = $game->status === 'finished'
                    && is_numeric($game->home_score)
                    && is_numeric($game->away_score);

                $predictedHomeScore = (int) $prediction->home_score;
                $predictedAwayScore = (int) $prediction->away_score;
                $officialHomeScore = $hasOfficialResult ? (int) $game->home_score : null;
                $officialAwayScore = $hasOfficialResult ? (int) $game->away_score : null;

                $predictedOutcome = $this->outcomeForScores($predictedHomeScore, $predictedAwayScore);
                $officialOutcome = $hasOfficialResult
                    ? $this->outcomeForScores($officialHomeScore, $officialAwayScore)
                    : null;
                $resolvedGroupName = $predictedHomeTeam?->group?->name
                    ?? $predictedAwayTeam?->group?->name
                    ?? $game->homeTeam?->group?->name
                    ?? $game->awayTeam?->group?->name;

                return [
                    'id' => $prediction->id,
                    'matchId' => $game->id,
                    'matchNumber' => $game->match_number,
                    'stage' => $game->stage,
                    'stageLabel' => $this->stageLabel($game->stage),
                    'groupName' => $resolvedGroupName ? "Grupo {$resolvedGroupName}" : null,
                    'status' => $game->status,
                    'statusLabel' => $this->statusLabel($game->status),
                    'matchDateIso' => $game->match_date?->format('Y-m-d'),
                    'matchDate' => $game->match_date?->format('d/m/Y'),
                    'matchTime' => $game->match_time ? Str::substr($game->match_time, 0, 5) : '--:--',
                    'venue' => $game->venue,
                    'homeTeamName' => $predictedHomeTeam?->name ?? $game->home_slot ?? 'Por definir',
                    'awayTeamName' => $predictedAwayTeam?->name ?? $game->away_slot ?? 'Por definir',
                    'homeTeamCode' => Str::upper(Str::substr($predictedHomeTeam?->country?->code ?? preg_replace('/[^A-Za-z]/', '', $game->home_slot ?: 'TBD'), 0, 3)),
                    'awayTeamCode' => Str::upper(Str::substr($predictedAwayTeam?->country?->code ?? preg_replace('/[^A-Za-z]/', '', $game->away_slot ?: 'TBD'), 0, 3)),
                    'homeFlagUrl' => $this->flagUrl($predictedHomeTeam?->country?->flag_path),
                    'awayFlagUrl' => $this->flagUrl($predictedAwayTeam?->country?->flag_path),
                    'predictedHomeScore' => $predictedHomeScore,
                    'predictedAwayScore' => $predictedAwayScore,
                    'predictedScore' => "{$predictedHomeScore} - {$predictedAwayScore}",
                    'actualHomeScore' => $officialHomeScore,
                    'actualAwayScore' => $officialAwayScore,
                    'actualScore' => $hasOfficialResult ? "{$officialHomeScore} - {$officialAwayScore}" : null,
                    'awardedPoints' => $hasOfficialResult ? (int) ($prediction->points ?? 0) : null,
                    'hasOfficialResult' => $hasOfficialResult,
                    'isExactHit' => $hasOfficialResult
                        ? ($predictedHomeScore === $officialHomeScore && $predictedAwayScore === $officialAwayScore)
                        : false,
                    'isCorrectResult' => $hasOfficialResult
                        ? $predictedOutcome === $officialOutcome
                        : false,
                ];
            })
            ->values();

        $playedPredictions = $predictions->where('hasOfficialResult', true)->values();
        $pendingPredictions = $predictions->where('hasOfficialResult', false)->values();

        $exactHits = (int) $playedPredictions->where('isExactHit', true)->count();
        $correctResults = (int) $playedPredictions->where('isCorrectResult', true)->count();
        $totalPoints = (int) $playedPredictions->sum(fn ($prediction) => (int) ($prediction['awardedPoints'] ?? 0));

        return Inertia::render('Pools/Show', [
            'poolEntry' => [
                'id' => $poolEntry->id,
                'registrationNumber' => $poolEntry->id,
                'name' => $poolEntry->name,
                'status' => $poolEntry->status,
                'statusLabel' => $this->statusLabelFromPool($poolEntry->status),
                'isLocked' => $state['is_locked'],
                'canEdit' => $state['can_edit'],
                'canInactivate' => $state['can_inactivate'],
                'completionPercent' => (int) ($poolEntry->completion_percent ?? 0),
                'createdAt' => $poolEntry->created_at?->format('d/m/Y H:i'),
                'createdDate' => $poolEntry->created_at?->format('Y-m-d'),
                'tournament' => [
                    'id' => $poolEntry->tournament?->id,
                    'name' => $poolEntry->tournament?->name,
                    'year' => $poolEntry->tournament?->year,
                ],
                'stats' => [
                    'totalPoints' => $totalPoints,
                    'matchesTotal' => (int) $predictions->count(),
                    'matchesPlayed' => (int) $playedPredictions->count(),
                    'matchesPending' => (int) $pendingPredictions->count(),
                    'exactHits' => $exactHits,
                    'correctResults' => $correctResults,
                ],
                'playedPredictions' => $playedPredictions,
                'pendingPredictions' => $pendingPredictions,
                'allPredictions' => $predictions,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tournament_id' => ['required', 'integer', 'exists:tournaments,id'],
            'predictions' => ['required', 'array', 'min:1'],
            'predictions.*.game_id' => ['required', 'integer', 'exists:games,id'],
            'predictions.*.home_score' => ['required', 'integer', 'min:0', 'max:20'],
            'predictions.*.away_score' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $user = $request->user();
        $tournament = Tournament::query()->findOrFail($validated['tournament_id']);
        $participationOpen = $this->poolEntryRuleService->isParticipationOpen($tournament);

        if (! $participationOpen) {
            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'La ventana de participacion para esta quiniela ya cerro.');
        }

        /** @var \Illuminate\Support\Collection<int, array<string, int>> $predictionPayloads */
        $predictionPayloads = collect($validated['predictions']);
        $gameIds = $predictionPayloads->pluck('game_id')->all();

        $games = Game::query()
            ->with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('tournament_id', $tournament->id)
            ->whereIn('id', $gameIds)
            ->get()
            ->keyBy('id');

        if ($games->count() !== count(array_unique($gameIds))) {
            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'Hay partidos invalidos en la quiniela. Recarga la pagina e intentalo nuevamente.');
        }

        if (count($gameIds) !== $tournament->games()->count()) {
            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'Debes completar todos los partidos antes de registrar la quiniela.');
        }

        if (count(array_unique($gameIds)) !== count($gameIds)) {
            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'Hay partidos repetidos en el envio. Revisa la quiniela e intentalo nuevamente.');
        }

        $hasInvalidKnockoutDraw = $predictionPayloads->contains(function (array $prediction) use ($games) {
            /** @var \App\Models\Game|null $game */
            $game = $games->get($prediction['game_id']);

            return $game
                && $game->stage !== 'group'
                && (int) $prediction['home_score'] === (int) $prediction['away_score'];
        });

        if ($hasInvalidKnockoutDraw) {
            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'En fases eliminatorias debes definir un ganador. Revisa los empates de tu quiniela.');
        }

        try {
            $resolvedBracket = $this->predictionBracketResolverService->resolve($tournament, $predictionPayloads);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'No pudimos validar el bracket de tu quiniela. Revisa los cruces y vuelve a intentarlo.');
        }

        $resolvedBracketTeamsByGameId = $this->resolvedBracketTeamsByGameId($resolvedBracket);

        try {
            $poolEntry = DB::transaction(function () use ($user, $tournament, $predictionPayloads, $games, $resolvedBracketTeamsByGameId) {
                $poolEntry = PoolEntry::query()->create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $user->id,
                    'name' => 'Quiniela en registro',
                    'status' => 'draft',
                    'completion_percent' => 100,
                ]);

                $poolEntry->predictions()->createMany(
                    $predictionPayloads
                        ->map(function (array $prediction) use ($games, $resolvedBracketTeamsByGameId) {
                            /** @var \App\Models\Game $game */
                            $game = $games->get($prediction['game_id']);
                            $resolvedTeams = $resolvedBracketTeamsByGameId[$game->id] ?? [];

                            return [
                                'game_id' => $game->id,
                                'predicted_home_team_id' => $resolvedTeams['predicted_home_team_id'] ?? null,
                                'predicted_away_team_id' => $resolvedTeams['predicted_away_team_id'] ?? null,
                                'predicted_winner_team_id' => $resolvedTeams['predicted_winner_team_id'] ?? null,
                                'home_score' => $prediction['home_score'],
                                'away_score' => $prediction['away_score'],
                                'points' => 0,
                            ];
                        })
                        ->values()
                        ->all()
                );

                $poolEntry->forceFill([
                    'name' => "Quiniela #{$poolEntry->id}",
                ])->save();

                $this->poolEntryRuleService->syncPoolEntryStatus($poolEntry);

                return $poolEntry->fresh(['predictions', 'tournament']);
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'No pudimos registrar tu quiniela en este momento. Intentalo nuevamente.');
        }

        $this->notifyAdminPoolActivity($poolEntry, $user, 'created');

        return redirect()
            ->route('predictions.worldcup')
            ->with('success', 'La quiniela fue registrada exitosamente.')
            ->with('created_pool_entry', $this->buildCreatedPoolEntryPayload($poolEntry, $resolvedBracket['predictedChampion'] ?? null));
    }

    public function updatePrediction(Request $request, PoolEntry $poolEntry, Prediction $prediction): RedirectResponse
    {
        abort_unless((int) $poolEntry->user_id === (int) $request->user()->id, 403);

        if ((int) $prediction->pool_entry_id !== (int) $poolEntry->id) {
            abort(404);
        }

        $poolEntry->loadMissing('tournament');
        $this->poolEntryRuleService->syncPoolEntryStatus($poolEntry);
        $state = $this->poolEntryRuleService->evaluatePoolEntry($poolEntry);

        if (! $state['can_edit']) {
            return redirect()
                ->route('pools.show', $poolEntry->id)
                ->with('error', 'Esta quiniela no se puede editar por su estado actual (pago o ventana cerrada).');
        }

        $validated = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:20'],
            'away_score' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $prediction->loadMissing('game:id,stage');
        $homeScore = (int) $validated['home_score'];
        $awayScore = (int) $validated['away_score'];

        if ($prediction->game && $prediction->game->stage !== 'group' && $homeScore === $awayScore) {
            return redirect()
                ->route('pools.show', $poolEntry->id)
                ->with('error', 'En fases eliminatorias debes definir un ganador. No se permite empate.');
        }

        try {
            DB::transaction(function () use ($poolEntry, $prediction, $homeScore, $awayScore) {
                $prediction->forceFill([
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                ])->save();

                $predictions = $poolEntry->predictions()
                    ->with('game:id,stage')
                    ->get();

                $predictionPayloads = $predictions
                    ->map(fn (Prediction $item) => [
                        'game_id' => (int) $item->game_id,
                        'home_score' => (int) $item->home_score,
                        'away_score' => (int) $item->away_score,
                    ]);

                $resolvedBracket = $this->predictionBracketResolverService->resolve(
                    $poolEntry->tournament,
                    $predictionPayloads
                );
                $resolvedTeamsByGameId = $this->resolvedBracketTeamsByGameId($resolvedBracket);

                foreach ($predictions as $item) {
                    $resolvedTeams = $resolvedTeamsByGameId[$item->game_id] ?? [];

                    $predictedWinnerTeamId = $resolvedTeams['predicted_winner_team_id'] ?? null;
                    if ($item->game && $item->game->stage !== 'group' && (int) $item->home_score === (int) $item->away_score) {
                        $predictedWinnerTeamId = null;
                    }

                    $item->forceFill([
                        'predicted_home_team_id' => $resolvedTeams['predicted_home_team_id'] ?? null,
                        'predicted_away_team_id' => $resolvedTeams['predicted_away_team_id'] ?? null,
                        'predicted_winner_team_id' => $predictedWinnerTeamId,
                    ])->save();
                }

                $this->recalculatePoolEntryScoreSummary($poolEntry);
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('pools.show', $poolEntry->id)
                ->with('error', 'No pudimos actualizar este pronostico en este momento. Intentalo nuevamente.');
        }

        return redirect()
            ->route('pools.show', $poolEntry->id)
            ->with('success', 'Pronostico actualizado correctamente.');
    }

    public function destroy(Request $request, PoolEntry $poolEntry): RedirectResponse
    {
        abort_unless((int) $poolEntry->user_id === (int) $request->user()->id, 403);

        $poolEntry->loadMissing('tournament');
        $state = $this->poolEntryRuleService->evaluatePoolEntry($poolEntry);

        if (! $state['can_inactivate']) {
            return redirect()
                ->route('pools.index')
                ->with('error', 'Esta quiniela no se puede inactivar por su estado actual (pago o ventana cerrada).');
        }

        $poolEntry->forceFill([
            'status' => 'inactive',
        ])->save();

        $poolEntry->delete();

        $this->notifyAdminPoolActivity($poolEntry, $request->user(), 'inactivated');

        return redirect()
            ->route('pools.index')
            ->with('success', 'La quiniela fue inactivada correctamente.');
    }

    public function restore(Request $request, int $poolEntry): RedirectResponse
    {
        $entry = PoolEntry::withTrashed()
            ->where('id', $poolEntry)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$entry->trashed()) {
            return redirect()
                ->route('pools.index')
                ->with('success', 'La quiniela ya estaba activa.');
        }

        $state = $this->poolEntryRuleService->evaluatePoolEntry($entry);
        if (!$state['can_inactivate']) {
            return redirect()
                ->route('pools.index')
                ->with('error', 'No se puede reactivar esta quiniela por su estado actual (pago o ventana cerrada).');
        }

        $entry->restore();
        $entry->forceFill([
            'status' => 'draft',
        ])->save();
        $this->poolEntryRuleService->syncPoolEntryStatus($entry);
        $this->notifyAdminPoolActivity($entry, $request->user(), 'reactivated');

        return redirect()
            ->route('pools.index')
            ->with('success', 'La quiniela fue reactivada correctamente.');
    }

    private function notifyAdminPoolActivity(PoolEntry $poolEntry, User $actor, string $action): void
    {
        try {
            $admins = User::query()->where('is_admin', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AdminPoolActivityNotification($poolEntry, $actor, $action));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            broadcast(AdminPoolActivityBroadcast::fromPoolEntryAction(
                $poolEntry,
                $actor,
                $action
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function buildCreatedPoolEntryPayload(PoolEntry $poolEntry, $predictedChampion): array
    {
        return [
            'id' => $poolEntry->id,
            'registrationNumber' => $poolEntry->id,
            'name' => $poolEntry->name,
            'status' => $poolEntry->status,
            'completionPercent' => $poolEntry->completion_percent,
            'tournamentName' => $poolEntry->tournament?->name,
            'predictedChampionName' => $predictedChampion?->name,
            'createdAt' => $poolEntry->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function resolvedBracketTeamsByGameId(array $resolvedBracket): array
    {
        $gamesByMatchNumber = $resolvedBracket['gamesByMatchNumber'] ?? [];
        $resolvedByGameId = [];

        foreach ($gamesByMatchNumber as $resolvedGame) {
            $gameId = (int) ($resolvedGame['id'] ?? 0);

            if ($gameId <= 0) {
                continue;
            }

            $resolvedByGameId[$gameId] = [
                'predicted_home_team_id' => $resolvedGame['home_team']?->id ?? null,
                'predicted_away_team_id' => $resolvedGame['away_team']?->id ?? null,
                'predicted_winner_team_id' => $resolvedGame['winner_team']?->id ?? null,
            ];
        }

        return $resolvedByGameId;
    }

    private function recalculatePoolEntryScoreSummary(PoolEntry $poolEntry): void
    {
        $poolEntry->loadMissing('tournament');

        $rule = Rule::query()
            ->where('active', true)
            ->where('tournament_id', $poolEntry->tournament_id)
            ->first();

        $exactScorePoints = (int) ($rule?->exact_score_points ?? 5);
        $correctResultPoints = (int) ($rule?->correct_result_points ?? 3);

        $predictions = $poolEntry->predictions()
            ->with('game:id,home_score,away_score')
            ->get();

        $totalPoints = 0;
        $exactHits = 0;
        $correctResults = 0;

        foreach ($predictions as $prediction) {
            $game = $prediction->game;
            $points = 0;

            if ($game && is_numeric($game->home_score) && is_numeric($game->away_score)) {
                $realHome = (int) $game->home_score;
                $realAway = (int) $game->away_score;
                $predHome = (int) $prediction->home_score;
                $predAway = (int) $prediction->away_score;

                if ($predHome === $realHome && $predAway === $realAway) {
                    $points = $exactScorePoints;
                    $exactHits++;
                } else {
                    $predictedResult = $this->outcomeForScores($predHome, $predAway);
                    $officialResult = $this->outcomeForScores($realHome, $realAway);

                    if ($predictedResult === $officialResult) {
                        $points = $correctResultPoints;
                        $correctResults++;
                    }
                }
            }

            if ((int) $prediction->points !== $points) {
                $prediction->forceFill(['points' => $points])->save();
            }

            $totalPoints += $points;
        }

        $poolEntry->forceFill([
            'total_points' => $totalPoints,
            'exact_hits' => $exactHits,
            'correct_results' => $correctResults,
        ])->save();
    }

    private function flagUrl(?string $flagPath): ?string
    {
        if (! $flagPath) {
            return null;
        }

        if (Str::startsWith($flagPath, ['http://', 'https://', '/storage/'])) {
            return $flagPath;
        }

        return Storage::url($flagPath);
    }

    private function outcomeForScores(int $homeScore, int $awayScore): string
    {
        if ($homeScore > $awayScore) {
            return 'home';
        }

        if ($awayScore > $homeScore) {
            return 'away';
        }

        return 'draw';
    }

    private function stageLabel(?string $stage): string
    {
        return match ($stage) {
            'group' => 'Fase de grupos',
            'round_32' => 'Dieciseisavos',
            'round_16' => 'Octavos',
            'quarter' => 'Cuartos',
            'semi' => 'Semifinal',
            'third_place' => 'Tercer puesto',
            'final' => 'Final',
            default => 'Partido',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'finished' => 'Finalizado',
            'in_progress' => 'En juego',
            default => 'Pendiente',
        };
    }

    private function statusLabelFromPool(?string $status): string
    {
        return match ($status) {
            'draft' => 'Borrador',
            'finished' => 'Finalizada',
            'inactive' => 'Inactiva',
            'locked' => 'Bloqueada',
            'cancelled' => 'Cancelada',
            'paid_locked' => 'Activa',
            default => 'Activa',
        };
    }
}
