<?php

namespace App\Http\Controllers;

use App\Services\FootballApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FootballController extends Controller
{
    public function __construct(
        private readonly FootballApiService $api
    ) {
    }

    public function countries(): JsonResponse
    {
        return $this->respond(fn () => $this->api->getCountries());
    }

    public function venues(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->api->getVenues($request->only(['id', 'name', 'city', 'country', 'search'])));
    }

    public function teams(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->api->getTeams($request->only(['id', 'name', 'league', 'season', 'country', 'search'])));
    }

    public function fixtures(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->api->getFixtures($request->only([
            'id',
            'league',
            'season',
            'team',
            'date',
            'from',
            'to',
            'status',
            'timezone',
            'venue',
        ])));
    }

    public function fixtureDetail(int $id): JsonResponse
    {
        return $this->respond(fn () => [
            'fixture' => $this->api->getFixtureById($id),
            'events' => $this->api->getFixtureEvents($id),
            'lineups' => $this->api->getFixtureLineups($id),
            'statistics' => $this->api->getFixtureStatistics($id),
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->api->getLiveFixtures($request->integer('league') ?: null));
    }

    public function standings(int $league, int $season): JsonResponse
    {
        return $this->respond(fn () => $this->api->getStandings($league, $season));
    }

    private function respond(callable $resolver): JsonResponse
    {
        try {
            return response()->json($resolver());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Football API request failed.',
                'error' => $exception->getMessage(),
            ], 502);
        }
    }
}
