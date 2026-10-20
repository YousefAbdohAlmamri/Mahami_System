<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_teams(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Space 1',
            'slug' => 'space-1',
        ]);

        $response = $this->getJson("/api/workspaces/{$workspace->id}/teams");
        $response->assertStatus(401);
    }

    public function test_workspace_owner_and_admin_can_create_teams_and_become_lead(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Tech Corp',
            'slug' => 'tech-corp',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $response = $this->actingAs($owner)->postJson("/api/workspaces/{$workspace->id}/teams", [
            'name' => 'Backend Engineers',
            'description' => 'Core backend platform',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Backend Engineers')
            ->assertJsonPath('data.current_user_role', 'lead');

        $this->assertDatabaseHas('workspace_teams', [
            'workspace_id' => $workspace->id,
            'name' => 'Backend Engineers',
            'created_by' => $owner->id,
        ]);

        $this->assertDatabaseHas('team_members', [
            'user_id' => $owner->id,
            'role' => 'lead',
        ]);
    }

    public function test_regular_workspace_member_cannot_create_teams(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Design Agency',
            'slug' => 'design-agency',
        ]);

        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'status' => 'active']);

        $response = $this->actingAs($member)->postJson("/api/workspaces/{$workspace->id}/teams", [
            'name' => 'Illustrators',
        ]);

        $response->assertStatus(403);
    }

    public function test_team_lead_can_update_team_details(): void
    {
        $owner = User::factory()->create();
        $lead = User::factory()->create();

        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Dev Studio',
            'slug' => 'dev-studio',
        ]);
        $workspace->members()->attach($lead->id, ['role' => 'member', 'status' => 'active']);

        $team = Team::create([
            'workspace_id' => $workspace->id,
            'name' => 'Frontend Devs',
            'created_by' => $owner->id,
        ]);
        $team->members()->attach($lead->id, ['role' => 'lead']);

        $response = $this->actingAs($lead)->putJson("/api/teams/{$team->id}", [
            'name' => 'Vue & React Engineers',
            'description' => 'Modern client stack',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Vue & React Engineers');
    }

    public function test_can_add_workspace_members_to_team_and_cannot_add_outsiders(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Finance App',
            'slug' => 'finance-app',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $team = Team::create([
            'workspace_id' => $workspace->id,
            'name' => 'QA Team',
            'created_by' => $owner->id,
        ]);
        $team->members()->attach($owner->id, ['role' => 'lead']);

        $colleague = User::factory()->create();
        $workspace->members()->attach($colleague->id, ['role' => 'member', 'status' => 'active']);

        $outsider = User::factory()->create();

        // 1. Add valid workspace colleague
        $addColleagueResponse = $this->actingAs($owner)->postJson("/api/teams/{$team->id}/members", [
            'user_id' => $colleague->id,
            'role' => 'member',
        ]);
        $addColleagueResponse->assertStatus(201)
            ->assertJsonPath('data.id', $colleague->id);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $colleague->id,
            'role' => 'member',
        ]);

        // 2. Reject outsider
        $addOutsiderResponse = $this->actingAs($owner)->postJson("/api/teams/{$team->id}/members", [
            'user_id' => $outsider->id,
            'role' => 'member',
        ]);
        $addOutsiderResponse->assertStatus(422);
    }

    public function test_can_assign_board_to_team_and_enforce_viewer_vs_editor_permissions(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Startup Space',
            'slug' => 'startup-space',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $board = Board::create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'title' => 'Product Roadmap',
        ]);

        $team = Team::create([
            'workspace_id' => $workspace->id,
            'name' => 'Marketing Team',
            'created_by' => $owner->id,
        ]);

        $marketingMember = User::factory()->create();
        $workspace->members()->attach($marketingMember->id, ['role' => 'member', 'status' => 'active']);
        $team->members()->attach($marketingMember->id, ['role' => 'member']);

        // 1. Assign board to marketing team as 'viewer'
        $assignResponse = $this->actingAs($owner)->postJson("/api/boards/{$board->id}/teams", [
            'team_id' => $team->id,
            'permission' => 'viewer',
        ]);
        $assignResponse->assertStatus(201)
            ->assertJsonPath('data.permission', 'viewer');

        // 2. Marketing member can view board
        $viewResponse = $this->actingAs($marketingMember)->getJson("/api/boards/{$board->id}");
        $viewResponse->assertStatus(200)
            ->assertJsonPath('data.title', 'Product Roadmap');

        // 3. Marketing member cannot update board (only 'admin' permission or workspace owner can update)
        $updateResponse = $this->actingAs($marketingMember)->putJson("/api/boards/{$board->id}", [
            'title' => 'Hacked Title',
        ]);
        $updateResponse->assertStatus(403);
    }

    public function test_user_not_in_assigned_team_cannot_view_restricted_board(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Secret Lab',
            'slug' => 'secret-lab',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $board = Board::create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'title' => 'Top Secret Project',
        ]);

        $secretTeam = Team::create([
            'workspace_id' => $workspace->id,
            'name' => 'Black Ops',
            'created_by' => $owner->id,
        ]);
        $board->teams()->attach($secretTeam->id, ['permission' => 'editor']);

        // Other workspace member not in Black Ops team
        $otherMember = User::factory()->create();
        $workspace->members()->attach($otherMember->id, ['role' => 'member', 'status' => 'active']);

        $response = $this->actingAs($otherMember)->getJson("/api/boards/{$board->id}");
        $response->assertStatus(403);
    }
}
