<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_task_in_their_list(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/lists/{$list->id}/tasks", [
            'title' => 'Design Landing Page',
            'description' => 'Figma mockups and wireframes.',
            'priority' => 'high',
            'due_date' => '2026-11-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Design Landing Page')
            ->assertJsonPath('data.description', 'Figma mockups and wireframes.')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_date', '2026-11-15')
            ->assertJsonPath('data.position', 0);

        $this->assertDatabaseHas('tasks', [
            'board_list_id' => $list->id,
            'title' => 'Design Landing Page',
            'priority' => 'high',
            'due_date' => '2026-11-15',
            'position' => 0,
        ]);
    }

    public function test_user_cannot_create_a_task_in_another_users_list(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($stranger, 'sanctum')->postJson("/api/lists/{$list->id}/tasks", [
            'title' => 'Unauthorized Task',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('tasks', ['title' => 'Unauthorized Task']);
    }

    public function test_task_creation_validation_rules(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        // Empty title
        $response = $this->actingAs($user, 'sanctum')->postJson("/api/lists/{$list->id}/tasks", [
            'title' => '',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['title']);

        // Invalid priority
        $response = $this->actingAs($user, 'sanctum')->postJson("/api/lists/{$list->id}/tasks", [
            'title' => 'Valid Title',
            'priority' => 'urgent_critical',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['priority']);

        // Invalid due date
        $response = $this->actingAs($user, 'sanctum')->postJson("/api/lists/{$list->id}/tasks", [
            'title' => 'Valid Title',
            'due_date' => 'not-a-date',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['due_date']);
    }

    public function test_user_can_view_a_task(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id, 'title' => 'Inspect Me']);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', 'Inspect Me');
    }

    public function test_user_cannot_view_another_users_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_a_task(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create([
            'board_list_id' => $list->id,
            'title' => 'Initial Title',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/tasks/{$task->id}", [
            'title' => 'Revised Title',
            'priority' => 'high',
            'description' => 'Revised details.',
            'due_date' => '2026-12-01',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Revised Title')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_date', '2026-12-01');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Revised Title',
            'priority' => 'high',
        ]);
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id, 'title' => 'Owner Secret']);

        $response = $this->actingAs($stranger, 'sanctum')->putJson("/api/tasks/{$task->id}", [
            'title' => 'Hacked Task',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Owner Secret', $task->fresh()->title);
    }

    public function test_user_can_delete_a_task_and_positions_are_recompacted(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        $t0 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 0]);
        $t1 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 1]);
        $t2 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 2]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/tasks/{$t1->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully.']);

        $this->assertDatabaseMissing('tasks', ['id' => $t1->id]);
        $this->assertEquals(0, $t0->fresh()->position);
        $this->assertEquals(1, $t2->fresh()->position);
    }

    public function test_user_cannot_delete_another_users_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        $response = $this->actingAs($stranger, 'sanctum')->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_user_can_move_a_task_within_the_same_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        $t0 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 0]);
        $t1 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 1]);
        $t2 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 2]);

        // Move t0 to position 2
        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/tasks/{$t0->id}/move", [
            'target_list_id' => $list->id,
            'position' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.position', 2);

        $this->assertEquals(2, $t0->fresh()->position);
        $this->assertEquals(0, $t1->fresh()->position);
        $this->assertEquals(1, $t2->fresh()->position);
    }

    public function test_user_can_move_a_task_to_another_column_in_same_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $colA = BoardList::factory()->create(['board_id' => $board->id]);
        $colB = BoardList::factory()->create(['board_id' => $board->id]);

        $taskA0 = Task::factory()->create(['board_list_id' => $colA->id, 'position' => 0]);
        $taskA1 = Task::factory()->create(['board_list_id' => $colA->id, 'position' => 1]);

        $taskB0 = Task::factory()->create(['board_list_id' => $colB->id, 'position' => 0]);

        // Move taskA0 to Col B at position 0
        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/tasks/{$taskA0->id}/move", [
            'target_list_id' => $colB->id,
            'position' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.board_list_id', $colB->id)
            ->assertJsonPath('data.position', 0);

        // In colA, taskA1 should shift down to 0
        $this->assertEquals(0, $taskA1->fresh()->position);

        // In colB, taskB0 should shift up to 1
        $this->assertEquals(1, $taskB0->fresh()->position);
        $this->assertEquals(0, $taskA0->fresh()->position);
    }

    public function test_user_cannot_move_a_task_to_a_column_on_a_different_board(): void
    {
        $user = User::factory()->create();
        $board1 = Board::factory()->create(['user_id' => $user->id]);
        $board2 = Board::factory()->create(['user_id' => $user->id]);

        $col1 = BoardList::factory()->create(['board_id' => $board1->id]);
        $foreignCol = BoardList::factory()->create(['board_id' => $board2->id]);

        $task = Task::factory()->create(['board_list_id' => $col1->id, 'position' => 0]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/tasks/{$task->id}/move", [
            'target_list_id' => $foreignCol->id,
            'position' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_list_id']);
    }

    public function test_user_cannot_move_another_users_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $col = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $col->id]);

        $strangerBoard = Board::factory()->create(['user_id' => $stranger->id]);
        $strangerCol = BoardList::factory()->create(['board_id' => $strangerBoard->id]);

        $response = $this->actingAs($stranger, 'sanctum')->patchJson("/api/tasks/{$task->id}/move", [
            'target_list_id' => $strangerCol->id,
            'position' => 0,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reorder_tasks_within_a_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        $t0 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 0]);
        $t1 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 1]);
        $t2 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 2]);

        // Reorder: t2, t0, t1
        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/lists/{$list->id}/tasks/reorder", [
            'ordered_ids' => [$t2->id, $t0->id, $t1->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $t2->id)
            ->assertJsonPath('data.0.position', 0)
            ->assertJsonPath('data.1.id', $t0->id)
            ->assertJsonPath('data.1.position', 1)
            ->assertJsonPath('data.2.id', $t1->id)
            ->assertJsonPath('data.2.position', 2);

        $this->assertEquals(0, $t2->fresh()->position);
        $this->assertEquals(1, $t0->fresh()->position);
        $this->assertEquals(2, $t1->fresh()->position);
    }

    public function test_user_cannot_reorder_tasks_with_foreign_task_ids(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $col1 = BoardList::factory()->create(['board_id' => $board->id]);
        $col2 = BoardList::factory()->create(['board_id' => $board->id]);

        $task1 = Task::factory()->create(['board_list_id' => $col1->id]);
        $foreignTask = Task::factory()->create(['board_list_id' => $col2->id]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/lists/{$col1->id}/tasks/reorder", [
            'ordered_ids' => [$task1->id, $foreignTask->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ordered_ids']);
    }
}

