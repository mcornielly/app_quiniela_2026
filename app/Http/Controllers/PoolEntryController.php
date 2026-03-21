<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\PoolEntry;
use App\Models\Tournament;
use App\Services\Tournament\PredictionBracketResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PoolEntryController extends Controller
{
    public function __construct(
        private readonly PredictionBracketResolverService $predictionBracketResolverService,
    ) {
    }

    public function index(Request $request): Response
    {
        $poolEntries = PoolEntry::query()
            ->with([
                'tournament:id,name,year',
                'predictions' => fn ($query) => $query
                    ->with([
                        'game:id,match_date,match_time,home_team_id,away_team_id,home_slot,away_slot,home_score,away_score,status',
                        'game.homeTeam:id,name,country_id',
                        'game.homeTeam.country:id,code,flag_path',
                        'game.awayTeam:id,name,country_id',
                        'game.awayTeam.country:id,code,flag_path',
                    ])
                    ->latest('id'),
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (PoolEntry $poolEntry) {
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
                        $homeCode = strtoupper($game->homeTeam?->country?->code ?? preg_replace('/[^A-Za-z]/', '', $game->home_slot ?: 'TBD'));
                        $awayCode = strtoupper($game->awayTeam?->country?->code ?? preg_replace('/[^A-Za-z]/', '', $game->away_slot ?: 'TBD'));

                        return [
                            'homeName' => $game->homeTeam?->name ?? $game->home_slot ?? 'TBD',
                            'awayName' => $game->awayTeam?->name ?? $game->away_slot ?? 'TBD',
                            'homeCode' => Str::substr($homeCode, 0, 3),
                            'awayCode' => Str::substr($awayCode, 0, 3),
                            'homeFlagUrl' => $this->flagUrl($game->homeTeam?->country?->flag_path),
                            'awayFlagUrl' => $this->flagUrl($game->awayTeam?->country?->flag_path),
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
                    'status' => $poolEntry->status,
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

        try {
            $poolEntry = DB::transaction(function () use ($user, $tournament, $predictionPayloads, $games) {
                $poolEntry = PoolEntry::query()->create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $user->id,
                    'name' => 'Quiniela en registro',
                    'status' => 'complete',
                    'completion_percent' => 100,
                ]);

                $poolEntry->predictions()->createMany(
                    $predictionPayloads
                        ->map(function (array $prediction) use ($games) {
                            /** @var \App\Models\Game $game */
                            $game = $games->get($prediction['game_id']);

                            return [
                                'game_id' => $game->id,
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

                return $poolEntry->fresh(['predictions', 'tournament']);
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('predictions.worldcup')
                ->with('error', 'No pudimos registrar tu quiniela en este momento. Intentalo nuevamente.');
        }

        return redirect()
            ->route('predictions.worldcup')
            ->with('success', 'La quiniela fue registrada exitosamente.')
            ->with('created_pool_entry', $this->buildCreatedPoolEntryPayload($poolEntry, $resolvedBracket['predictedChampion'] ?? null));
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
}
