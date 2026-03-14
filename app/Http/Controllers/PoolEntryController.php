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
        $entries = PoolEntry::query()
            ->with('tournament:id,name,year')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (PoolEntry $entry) {
                return [
                    'id' => $entry->id,
                    'registration_number' => $entry->id,
                    'name' => $entry->name,
                    'status' => $entry->status,
                    'completion_percent' => $entry->completion_percent,
                    'total_points' => $entry->total_points,
                    'exact_hits' => $entry->exact_hits,
                    'correct_results' => $entry->correct_results,
                    'created_at' => $entry->created_at?->format('d/m/Y H:i'),
                    'tournament' => [
                        'id' => $entry->tournament?->id,
                        'name' => $entry->tournament?->name,
                        'year' => $entry->tournament?->year,
                    ],
                ];
            });

        return Inertia::render('Pools/Index', [
            'entries' => $entries,
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

        /** @var \Illuminate\Support\Collection<int, array<string, int>> $submittedPredictions */
        $submittedPredictions = collect($validated['predictions']);
        $gameIds = $submittedPredictions->pluck('game_id')->all();

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
            $createdEntry = DB::transaction(function () use ($user, $tournament, $submittedPredictions, $games) {
                $entry = PoolEntry::query()->create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $user->id,
                    'name' => 'Quiniela en registro',
                    'status' => 'complete',
                    'completion_percent' => 100,
                ]);

                $entry->predictions()->createMany(
                    $submittedPredictions
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

                $entry->forceFill([
                    'name' => "Quiniela #{$entry->id}",
                ])->save();

                return $entry->fresh(['predictions', 'tournament']);
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
            ->with('pool_entry_created', $this->buildCreatedEntryPayload($createdEntry, $games, $submittedPredictions));
    }

    private function buildCreatedEntryPayload(PoolEntry $entry, Collection $games, Collection $submittedPredictions): array
    {
        $finalGame = $games->firstWhere('stage', 'final');
        $finalPrediction = $finalGame
            ? $submittedPredictions->firstWhere('game_id', $finalGame->id)
            : null;

        return [
            'id' => $entry->id,
            'registration_number' => $entry->id,
            'name' => $entry->name,
            'status' => $entry->status,
            'completion_percent' => $entry->completion_percent,
            'tournament_name' => $entry->tournament?->name,
            'champion_name' => $this->resolveChampionName($finalGame, $finalPrediction),
            'created_at' => $entry->created_at?->format('d/m/Y H:i'),
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
