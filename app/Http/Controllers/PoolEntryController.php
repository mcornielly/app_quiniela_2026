<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\PoolEntry;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PoolEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $poolEntries = PoolEntry::query()
            ->with('tournament:id,name,year')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (PoolEntry $poolEntry) {
                return [
                    'id' => $poolEntry->id,
                    'registrationNumber' => $poolEntry->id,
                    'name' => $poolEntry->name,
                    'status' => $poolEntry->status,
                    'completionPercent' => $poolEntry->completion_percent,
                    'totalPoints' => $poolEntry->total_points,
                    'exactHits' => $poolEntry->exact_hits,
                    'correctResults' => $poolEntry->correct_results,
                    'createdAt' => $poolEntry->created_at?->format('d/m/Y H:i'),
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
            ->with('created_pool_entry', $this->buildCreatedPoolEntryPayload($poolEntry, $games, $predictionPayloads));
    }

    private function buildCreatedPoolEntryPayload(PoolEntry $poolEntry, Collection $games, Collection $predictionPayloads): array
    {
        $finalGame = $games->firstWhere('stage', 'final');
        $finalPrediction = $finalGame
            ? $predictionPayloads->firstWhere('game_id', $finalGame->id)
            : null;

        return [
            'id' => $poolEntry->id,
            'registrationNumber' => $poolEntry->id,
            'name' => $poolEntry->name,
            'status' => $poolEntry->status,
            'completionPercent' => $poolEntry->completion_percent,
            'tournamentName' => $poolEntry->tournament?->name,
            'predictedChampionName' => $this->resolveChampionName($finalGame, $finalPrediction),
            'createdAt' => $poolEntry->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function resolveChampionName(?Game $finalGame, ?array $finalPrediction): ?string
    {
        if (!$finalGame || !$finalPrediction) {
            return null;
        }

        if ($finalPrediction['home_score'] > $finalPrediction['away_score']) {
            return $finalGame->homeTeam?->name ?? $finalGame->home_slot;
        }

        if ($finalPrediction['away_score'] > $finalPrediction['home_score']) {
            return $finalGame->awayTeam?->name ?? $finalGame->away_slot;
        }

        return 'Empate en la final';
    }
}
