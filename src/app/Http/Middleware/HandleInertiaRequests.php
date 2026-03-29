<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                'shield_path' => $this->resolveFavoriteTeamShieldPath($user),
            ] : null,
            'favorite_team_theme' => $this->resolveFavoriteTeamTheme($user),
        ];
    }

    private function resolveFavoriteTeamShieldPath(User $user): ?string
    {
        $team = $user->favoriteTeam;

        if (! $team) {
            return null;
        }

        if ($team->shield_path) {
            return $team->shield_path;
        }

        $countryCode = strtolower(trim((string) $team->country?->code));

        if ($countryCode === '') {
            return null;
        }

        $fallbackShieldPath = "shield/{$countryCode}.png";

        return Storage::disk('public')->exists($fallbackShieldPath)
            ? $fallbackShieldPath
            : null;
    }

    private function resolveFavoriteTeamTheme(User $user): ?array
    {
        $countryCode = strtolower(trim((string) ($user->favoriteTeam?->country?->code ?? '')));
        $themes = config('world-cup-themes.themes', []);
        $defaultKey = config('world-cup-themes.default');
        $defaultTheme = $defaultKey ? ($themes[$defaultKey] ?? null) : null;
/*   */        $baseTheme = $defaultTheme ?? ($themes['neutral'] ?? null);

        if (!$baseTheme) {
            return null;
        }

        if ($countryCode === '') {
            return $baseTheme;
        }

        foreach ($themes as $theme) {
            $countryCodes = array_map(
                static fn (string $code): string => strtolower(trim($code)),
                $theme['country_codes'] ?? []
            );

            if (in_array($countryCode, $countryCodes, true)) {
                return array_replace($baseTheme, $theme);
            }
        }

        return $baseTheme;
    }
}
