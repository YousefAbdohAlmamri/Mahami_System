<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_to_boards_returns_401(): void
    {
        $response = $this->getJson('/api/boards');
        $response->assertStatus(401);
    }

    public function test_user_can_list_their_boards_only(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $board1 = Board::factory()->create(['user_id' => $user1->id, 'title' => 'User 1 Board']);
        BoardList::factory()->create(['board_id' => $board1->id]);

        Board::factory()->create(['user_id' => $user2->id, 'title' => 'User 2 Board']);

        $response = $this->actingAs($user1, 'sanctum')->getJson('/api/boards');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $board1->id)
            ->assertJsonPath('data.0.title', 'User 1 Board')
            ->assertJsonPath('data.0.lists_count', 1);
    }

    public function test_user_can_create_board_with_default_three_columns(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/boards', [
            'title' => 'New Product Roadmap',
            'description' => 'Planning for Q1 releases.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Product Roadmap')
            ->assertJsonPath('data.description', 'Planning for Q1 releases.')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonCount(3, 'data.lists');

        $boardId = $response->json('data.id');

        $this->assertDatabaseHas('boards', [
            'id' => $boardId,
            'user_id' => $user->id,
            'title' => 'New Product Roadmap',
        ]);

        // Verify the 3 default columns in database
        $this->assertDatabaseHas('board_lists', [
            'board_id' => $boardId,
            'title' => 'To Do',
            'position' => 0,
        ]);
        $this->assertDatabaseHas('board_lists', [
            'board_id' => $boardId,
            'title' => 'In Progress',
            'position' => 1,
        ]);
        $this->assertDatabaseHas('board_lists', [
            'board_id' => $boardId,
            'title' => 'Done',
            'position' => 2,
        ]);
    }

    public function test_board_creation_fails_when_title_is_invalid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/boards', [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_board_creation_ignores_client_provided_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/boards', [
            'title' => 'Secure Board',
            'user_id' => $otherUser->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('boards', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_view_own_board_with_lists_and_tasks(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id, 'title' => 'Active Project']);

        $col = BoardList::factory()->create(['board_id' => $board->id, 'title' => 'Doing', 'position' => 0]);
        $task = Task::factory()->create(['board_list_id' => $col->id, 'title' => 'Write Docs', 'position' => 0]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/boards/{$board->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $board->id)
            ->assertJsonPath('data.lists.0.title', 'Doing')
            ->assertJsonPath('data.lists.0.tasks.0.title', 'Write Docs');
    }

    public function test_user_cannot_view_another_users_board(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/boards/{$board->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/boards/{$board->id}", [
            'title' => 'Updated Board Title',
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Board Title')
            ->assertJsonPath('data.description', 'Updated Description');

        $this->assertDatabaseHas('boards', [
            'id' => $board->id,
            'title' => 'Updated Board Title',
        ]);
    }

    public function test_user_cannot_update_another_users_board(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id, 'title' => 'Original Title']);

        $response = $this->actingAs($stranger, 'sanctum')->putJson("/api/boards/{$board->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Original Title', $board->fresh()->title);
    }

    public function test_user_can_delete_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/boards/{$board->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Board deleted successfully.']);

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
        $this->assertDatabaseMissing('board_lists', ['id' => $list->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_another_users_board(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger, 'sanctum')->deleteJson("/api/boards/{$board->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('boards', ['id' => $board->id]);
    }
}

