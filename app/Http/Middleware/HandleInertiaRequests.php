<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? $this->sharedUser($user) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'created_pool_entry' => fn () => $request->session()->get('created_pool_entry'),
            ],
        ];
    }

    private function sharedUser(User $user): array
    {
        $user->loadMissing('favoriteTeam.country');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'is_admin' => $user->is_admin,
            'pool_entries_count' => $user->poolEntries()->count(),
            'favorite_team_id' => $user->favorite_team_id,
            'favorite_team' => $user->favoriteTeam ? [
                'id' => $user->favoriteTeam->id,
                'name' => $user->favoriteTeam->name,
                'country_code' => $user->favoriteTeam->country?->code,
                'flag_path' => $user->favoriteTeam->country?->flag_path,
                'shield_path' => $user->favoriteTeam->shield_path,
            ] : null,
            'favorite_team_theme' => $this->resolveFavoriteTeamTheme($user),
        ];
    }

    private function resolveFavoriteTeamTheme(User $user): ?array
    {
        $countryCode = $user->favoriteTeam?->country?->code;
        $themes = config('world-cup-themes.themes', []);
        $defaultKey = config('world-cup-themes.default');
        $defaultTheme = $defaultKey ? ($themes[$defaultKey] ?? null) : null;

        if (!$countryCode) {
            return $defaultTheme;
        }

        foreach ($themes as $theme) {
            $countryCodes = $theme['country_codes'] ?? [];

            if (in_array($countryCode, $countryCodes, true)) {
                return $theme;
            }
        }

        return $defaultTheme;
    }
}
