<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Country;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FavoriteTeamThemeResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_country_theme_has_required_personalization_keys(): void
    {
        $themes = config('world-cup-themes.themes', []);
        $defaultKey = config('world-cup-themes.default');

        $requiredKeys = [
            'tickerClass',
            'teamNameClass',
            'statsValueClass',
            'buttonSecondaryClass',
            'buttonPrimaryClass',
        ];

        $missingByTheme = [];

        foreach ($themes as $key => $theme) {
            if ($key === $defaultKey) {
                continue;
            }

            foreach ($requiredKeys as $requiredKey) {
                if (!array_key_exists($requiredKey, $theme) || blank($theme[$requiredKey])) {
                    $missingByTheme[$key][] = $requiredKey;
                }
            }
        }

        $this->assertEmpty(
            $missingByTheme,
            'Some country themes are missing required keys: '.json_encode($missingByTheme)
        );
    }

    public function test_middleware_resolves_country_theme_case_insensitively(): void
    {
        $country = Country::query()->create([
            'name' => 'BR',
            'code' => 'BR',
            'flag_path' => '/flags/br.svg',
        ]);

        $team = Team::query()->create([
            'country_id' => $country->id,
            'group_id' => null,
            'name' => 'Brazil',
            'group_position' => 1,
            'type' => 'international',
            'shield_path' => '/shields/br.png',
        ]);

        $user = User::factory()->create([
            'favorite_team_id' => $team->id,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(static fn () => $user);

        $shared = app(HandleInertiaRequests::class)->share($request);
        $theme = $shared['auth']['user']['favorite_team_theme'] ?? null;

        $this->assertIsArray($theme);
        $this->assertSame('br', $theme['key'] ?? null);
        $this->assertNotEmpty($theme['tickerClass'] ?? null);
        $this->assertNotEmpty($theme['buttonSecondaryClass'] ?? null);
        $this->assertNotEmpty($theme['buttonPrimaryClass'] ?? null);
    }
}

