<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardListApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_column_in_their_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/boards/{$board->id}/lists", [
            'title' => 'QA Review',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'QA Review')
            ->assertJsonPath('data.board_id', $board->id)
            ->assertJsonPath('data.position', 0);

        $this->assertDatabaseHas('board_lists', [
            'board_id' => $board->id,
            'title' => 'QA Review',
            'position' => 0,
        ]);
    }

    public function test_user_cannot_create_a_column_in_another_users_board(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger, 'sanctum')->postJson("/api/boards/{$board->id}/lists", [
            'title' => 'Unauthorized Column',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('board_lists', ['title' => 'Unauthorized Column']);
    }

    public function test_column_creation_fails_when_title_is_empty(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/boards/{$board->id}/lists", [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_can_update_a_column_title(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id, 'title' => 'Backlog']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/lists/{$list->id}", [
            'title' => 'Product Backlog',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Product Backlog');

        $this->assertDatabaseHas('board_lists', [
            'id' => $list->id,
            'title' => 'Product Backlog',
        ]);
    }

    public function test_user_cannot_update_another_users_column(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id, 'title' => 'Protected List']);

        $response = $this->actingAs($stranger, 'sanctum')->putJson("/api/lists/{$list->id}", [
            'title' => 'Hijacked List',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Protected List', $list->fresh()->title);
    }

    public function test_user_can_delete_a_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/lists/{$list->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'List deleted successfully.']);

        $this->assertDatabaseMissing('board_lists', ['id' => $list->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_another_users_column(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($stranger, 'sanctum')->deleteJson("/api/lists/{$list->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('board_lists', ['id' => $list->id]);
    }

    public function test_user_can_reorder_columns_in_their_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $col1 = BoardList::factory()->create(['board_id' => $board->id, 'title' => 'C1', 'position' => 0]);
        $col2 = BoardList::factory()->create(['board_id' => $board->id, 'title' => 'C2', 'position' => 1]);
        $col3 = BoardList::factory()->create(['board_id' => $board->id, 'title' => 'C3', 'position' => 2]);

        // Reorder in reverse: C3, C2, C1
        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/boards/{$board->id}/lists/reorder", [
            'ordered_ids' => [$col3->id, $col2->id, $col1->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $col3->id)
            ->assertJsonPath('data.0.position', 0)
            ->assertJsonPath('data.1.id', $col2->id)
            ->assertJsonPath('data.1.position', 1)
            ->assertJsonPath('data.2.id', $col1->id)
            ->assertJsonPath('data.2.position', 2);

        $this->assertEquals(0, $col3->fresh()->position);
        $this->assertEquals(1, $col2->fresh()->position);
        $this->assertEquals(2, $col1->fresh()->position);
    }

    public function test_user_cannot_reorder_columns_with_lists_from_another_board(): void
    {
        $user = User::factory()->create();
        $board1 = Board::factory()->create(['user_id' => $user->id]);
        $board2 = Board::factory()->create(['user_id' => $user->id]);

        $col1 = BoardList::factory()->create(['board_id' => $board1->id, 'position' => 0]);
        $foreignCol = BoardList::factory()->create(['board_id' => $board2->id, 'position' => 0]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/boards/{$board1->id}/lists/reorder", [
            'ordered_ids' => [$col1->id, $foreignCol->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ordered_ids']);
    }

    public function test_user_cannot_reorder_another_users_board_columns(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $col = BoardList::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($stranger, 'sanctum')->patchJson("/api/boards/{$board->id}/lists/reorder", [
            'ordered_ids' => [$col->id],
        ]);

        $response->assertStatus(403);
    }
}

