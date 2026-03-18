<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $tournament = Tournament::query()
            ->where('type', 'world_cup')
            ->orderByDesc('year')
            ->first();

        return Inertia::render('Dashboard', [
            'tournament' => $tournament ? [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'year' => $tournament->year,
                'logo' => $tournament->logo,
            ] : null,
            'favoriteTeams' => $this->favoriteTeams(),
        ]);
    }

    public function updateFavoriteTeam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'favorite_team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $teamId = $validated['favorite_team_id'] ?? null;

        if ($teamId !== null) {
            $allowedIds = collect($this->favoriteTeams())->pluck('id');

            abort_unless($allowedIds->contains($teamId), 422, 'El equipo seleccionado no esta disponible para personalizacion.');
        }

        $request->user()->update([
            'favorite_team_id' => $teamId,
        ]);

        return back()->with('success', $teamId ? 'Tu equipo favorito se actualizo correctamente.' : 'Tu banner volvio al estilo neutro.');
    }

    private function favoriteTeams(): array
    {
        $themes = config('world-cup-themes.themes', []);
        $themeByCountry = collect($themes)
            ->reject(fn (array $theme, string $key) => $key === config('world-cup-themes.default'))
            ->flatMap(function (array $theme) {
                return collect($theme['country_codes'] ?? [])
                    ->mapWithKeys(fn (string $countryCode) => [$countryCode => [
                        'key' => $theme['key'],
                        'label' => $theme['label'],
                    ]]);
            });

        if ($themeByCountry->isEmpty()) {
            return [];
        }

        return Team::query()
            ->with('country')
            ->whereHas('country', fn ($query) => $query->whereIn('code', $themeByCountry->keys()))
            ->orderBy('name')
            ->get()
            ->unique(fn (Team $team) => $team->country?->code ?? "team-{$team->id}")
            ->values()
            ->map(function (Team $team) use ($themeByCountry) {
                $countryCode = $team->country?->code;
                $theme = $countryCode ? $themeByCountry->get($countryCode) : null;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'country_code' => $countryCode,
                    'flag_path' => $team->country?->flag_path,
                    'shield_path' => $team->shield_path,
                    'theme_key' => $theme['key'] ?? null,
                    'theme_label' => $theme['label'] ?? null,
                ];
            })
            ->all();
    }
}
