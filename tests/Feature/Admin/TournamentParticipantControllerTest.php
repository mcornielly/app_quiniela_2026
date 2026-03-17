<?php

namespace Tests\Feature\Admin;

use App\Models\Group;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentParticipantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_tournament_participants_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $tournament = Tournament::query()->create([
            'name' => 'World Cup 2026',
            'year' => 2026,
            'host_countries' => ['USA'],
            'status' => 'draft',
            'type' => 'world_cup',
        ]);

        $group = Group::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'A',
        ]);

        $team = Team::query()->create([
            'name' => 'Mexico',
            'group_id' => $group->id,
            'group_position' => 1,
            'type' => 'international',
        ]);

        TournamentTeam::query()->create([
            'tournament_id' => $tournament->id,
            'team_id' => $team->id,
            'fifa_ranking' => 15,
            'fair_play_points' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tournaments.participants.index', $tournament))
            ->assertOk();
    }

    public function test_admin_can_update_tournament_participant_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $tournament = Tournament::query()->create([
            'name' => 'World Cup 2026',
            'year' => 2026,
            'host_countries' => ['USA'],
            'status' => 'draft',
            'type' => 'world_cup',
        ]);

        $group = Group::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'A',
        ]);

        $team = Team::query()->create([
            'name' => 'Mexico',
            'group_id' => $group->id,
            'group_position' => 1,
            'type' => 'international',
        ]);

        $participant = TournamentTeam::query()->create([
            'tournament_id' => $tournament->id,
            'team_id' => $team->id,
            'fifa_ranking' => 15,
            'fair_play_points' => 0,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.tournaments.participants.update', [
                'tournament' => $tournament,
                'participant' => $participant,
            ]), [
                'fifa_ranking' => 12,
                'fair_play_points' => -3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tournament_team', [
            'id' => $participant->id,
            'fifa_ranking' => 12,
            'fair_play_points' => -3,
        ]);
    }
}
