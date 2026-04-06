<?php

namespace Tests\Feature;

use App\Models\PoolEntry;
use App\Models\Tournament;
use App\Models\User;
use App\Http\Controllers\PoolEntryController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationBurstTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_receives_five_unique_notifications_for_rapid_inactivate_restore_actions(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $actor = User::factory()->create([
            'is_admin' => false,
        ]);

        $tournament = Tournament::query()->create([
            'name' => 'World Cup 2026',
            'year' => 2026,
            'host_countries' => ['USA', 'Mexico', 'Canada'],
            'deadline_at' => now()->addDays(10),
            'status' => 'live',
            'type' => 'world_cup',
        ]);

        $poolEntry = PoolEntry::query()->create([
            'tournament_id' => $tournament->id,
            'user_id' => $actor->id,
            'name' => 'User 3 Pool #2',
            'status' => 'draft',
            'completion_percent' => 60,
            'entry_fee' => 15,
        ]);

        $controller = app(PoolEntryController::class);
        $method = new \ReflectionMethod(PoolEntryController::class, 'notifyAdminPoolActivity');
        $method->setAccessible(true);

        $method->invoke($controller, $poolEntry, $actor, 'inactivated');
        usleep(120000);
        $method->invoke($controller, $poolEntry, $actor, 'reactivated');
        usleep(100000);
        $method->invoke($controller, $poolEntry, $actor, 'inactivated');
        usleep(90000);
        $method->invoke($controller, $poolEntry, $actor, 'reactivated');
        usleep(70000);
        $method->invoke($controller, $poolEntry, $actor, 'inactivated');

        $notifications = $admin->fresh()->notifications()->latest()->get();
        $this->assertCount(5, $notifications);

        $actions = $notifications
            ->pluck('data.action')
            ->values()
            ->all();

        $this->assertSame(3, collect($actions)->filter(fn (string $action) => $action === 'inactivated')->count());
        $this->assertSame(2, collect($actions)->filter(fn (string $action) => $action === 'reactivated')->count());

        $keys = $notifications
            ->pluck('data.notificationKey')
            ->filter()
            ->values();

        $this->assertCount(5, $keys);
        $this->assertCount(5, $keys->unique());
    }
}
