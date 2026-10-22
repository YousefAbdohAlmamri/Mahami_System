<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_workspaces(): void
    {
        $response = $this->getJson('/api/workspaces');
        $response->assertStatus(401);
    }

    public function test_user_can_list_their_workspaces(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Workspace where user is owner
        $ws1 = Workspace::factory()->create(['owner_id' => $user->id]);
        $ws1->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        // Workspace where user is member
        $ws2 = Workspace::factory()->create(['owner_id' => $otherUser->id]);
        $ws2->members()->attach($otherUser->id, ['role' => 'owner', 'status' => 'active']);
        $ws2->members()->attach($user->id, ['role' => 'member', 'status' => 'active']);

        // Workspace user is NOT in
        $ws3 = Workspace::factory()->create(['owner_id' => $otherUser->id]);
        $ws3->members()->attach($otherUser->id, ['role' => 'owner', 'status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/workspaces');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/workspaces', [
            'name' => 'Acme Corporation',
            'description' => 'Our main workspace',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Acme Corporation')
            ->assertJsonPath('data.current_user_role', 'owner');

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Acme Corporation',
            'owner_id' => $user->id,
        ]);

        $this->assertDatabaseHas('workspace_members', [
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_view_workspace_they_are_not_in(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/workspaces/{$workspace->id}");

        $response->assertStatus(403);
    }

    public function test_owner_and_admin_can_update_workspace_but_member_cannot(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $owner->id, 'name' => 'Original Name']);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'status' => 'active']);
        $workspace->members()->attach($member->id, ['role' => 'member', 'status' => 'active']);

        // Admin can update
        $resAdmin = $this->actingAs($admin, 'sanctum')->putJson("/api/workspaces/{$workspace->id}", [
            'name' => 'Admin Updated Name',
        ]);
        $resAdmin->assertStatus(200);

        // Member cannot update
        $resMember = $this->actingAs($member, 'sanctum')->putJson("/api/workspaces/{$workspace->id}", [
            'name' => 'Member Trying Update',
        ]);
        $resMember->assertStatus(403);
    }

    public function test_only_owner_can_delete_workspace(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $workspace->members()->attach($admin->id, ['role' => 'admin', 'status' => 'active']);

        // Admin fails
        $resAdmin = $this->actingAs($admin, 'sanctum')->deleteJson("/api/workspaces/{$workspace->id}");
        $resAdmin->assertStatus(403);

        // Owner succeeds
        $resOwner = $this->actingAs($owner, 'sanctum')->deleteJson("/api/workspaces/{$workspace->id}");
        $resOwner->assertStatus(200);

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }

    public function test_owner_can_transfer_ownership_to_active_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $stranger = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $workspace->members()->attach($member->id, ['role' => 'member', 'status' => 'active']);

        // Cannot transfer to non-member
        $resInvalid = $this->actingAs($owner, 'sanctum')->postJson("/api/workspaces/{$workspace->id}/transfer-ownership", [
            'new_owner_id' => $stranger->id,
        ]);
        $resInvalid->assertStatus(422);

        // Transfer to member
        $resValid = $this->actingAs($owner, 'sanctum')->postJson("/api/workspaces/{$workspace->id}/transfer-ownership", [
            'new_owner_id' => $member->id,
        ]);
        $resValid->assertStatus(200)
            ->assertJsonPath('data.owner.id', $member->id);

        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'owner_id' => $member->id]);
        $this->assertDatabaseHas('workspace_members', ['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'admin']);
    }

    public function test_workspace_invitations_and_acceptance_flow(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        // 1a. Send invite via alias route (/members/invite)
        $resInviteAlias = $this->actingAs($owner, 'sanctum')->postJson("/api/workspaces/{$workspace->id}/members/invite", [
            'email' => 'alias_test@example.com',
            'role' => 'member',
        ]);
        $resInviteAlias->assertStatus(201);
        $this->assertNotEmpty($resInviteAlias->json('data.token'));

        // 1b. Send invite via standard route (/invitations)
        $resInvite = $this->actingAs($owner, 'sanctum')->postJson("/api/workspaces/{$workspace->id}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ]);
        $resInvite->assertStatus(201);
        $token = $resInvite->json('data.token');
        $this->assertNotEmpty($token);

        // Verify that registered user received an in-app notification
        $this->assertEquals(1, $invitee->unreadNotifications()->count());
        $invNotification = $invitee->unreadNotifications()->first();
        $this->assertEquals('workspace_invitation', $invNotification->data['type']);
        $this->assertEquals($token, $invNotification->data['token']);

        // 2. Cannot send duplicate invite if already active member
        // (will test after accepting)

        // 3. Public preview of invitation by token
        $resPreview = $this->getJson("/api/invitations/{$token}");
        $resPreview->assertStatus(200)
            ->assertJsonPath('data.email', 'invitee@example.com')
            ->assertJsonPath('data.is_valid', true);

        // 4. Accept invitation
        $resAccept = $this->actingAs($invitee, 'sanctum')->postJson("/api/invitations/{$token}/accept");
        $resAccept->assertStatus(200);

        // Assert member joined
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $invitee->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        // Assert invitee notification is now marked as read
        $this->assertEquals(0, $invitee->unreadNotifications()->count());

        // Assert inviter (owner) received acceptance notification
        $this->assertTrue($owner->notifications()->where('data->type', 'workspace_invitation_response')->exists());
        $responseNotif = $owner->notifications()->where('data->type', 'workspace_invitation_response')->first();
        $this->assertTrue($responseNotif->data['accepted']);

        // 5. Token cannot be reused
        $resReuse = $this->actingAs($invitee, 'sanctum')->postJson("/api/invitations/{$token}/accept");
        $resReuse->assertStatus(422);

        // 6. Test declining invitation flow
        $declinedUser = User::factory()->create(['email' => 'declined@example.com']);
        $resDeclinedInvite = $this->actingAs($owner, 'sanctum')->postJson("/api/workspaces/{$workspace->id}/invitations", [
            'email' => 'declined@example.com',
            'role' => 'guest',
        ]);
        $resDeclinedInvite->assertStatus(201);
        $declinedToken = $resDeclinedInvite->json('data.token');

        $this->actingAs($declinedUser, 'sanctum')->postJson("/api/invitations/{$declinedToken}/decline")->assertOk();
        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $declinedUser->id,
        ]);
        // Owner received decline notification
        $declineNotif = $owner->notifications()->where('data->type', 'workspace_invitation_response')->where('data->accepted', false)->first();
        $this->assertNotNull($declineNotif);

        // 7. Inviting again now returns 422 because user is already a member
        $resDup = $this->actingAs($owner, 'sanctum')->postJson("/api/workspaces/{$workspace->id}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'admin',
        ]);
        $resDup->assertStatus(422);
    }

    public function test_member_role_update_and_removal(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $workspace->members()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $workspace->members()->attach($member->id, ['role' => 'member', 'status' => 'active']);

        // Update role to admin
        $resUpdate = $this->actingAs($owner, 'sanctum')->putJson("/api/workspaces/{$workspace->id}/members/{$member->id}", [
            'role' => 'admin',
        ]);
        $resUpdate->assertStatus(200);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);

        // Remove member
        $resRemove = $this->actingAs($owner, 'sanctum')->deleteJson("/api/workspaces/{$workspace->id}/members/{$member->id}");
        $resRemove->assertStatus(200);
        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_boards_can_be_scoped_by_workspace(): void
    {
        $user = User::factory()->create();
        $ws1 = Workspace::factory()->create(['owner_id' => $user->id]);
        $ws1->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        $ws2 = Workspace::factory()->create(['owner_id' => $user->id]);
        $ws2->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        // Board in WS 1
        $board1 = Board::factory()->create(['user_id' => $user->id, 'workspace_id' => $ws1->id]);
        // Board in WS 2
        $board2 = Board::factory()->create(['user_id' => $user->id, 'workspace_id' => $ws2->id]);

        $resWs1 = $this->actingAs($user, 'sanctum')->getJson("/api/boards?workspace_id={$ws1->id}");
        $resWs1->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $board1->id);

        $resWs2 = $this->actingAs($user, 'sanctum')->getJson("/api/boards?workspace_id={$ws2->id}");
        $resWs2->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $board2->id);
    }
}

