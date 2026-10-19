<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cascading_deletes_when_board_is_deleted(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        $this->assertDatabaseHas('boards', ['id' => $board->id]);
        $this->assertDatabaseHas('board_lists', ['id' => $list->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);

        $board->delete();

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
        $this->assertDatabaseMissing('board_lists', ['id' => $list->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_cascading_deletes_when_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('boards', ['id' => $board->id]);

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }

    public function test_task_priority_and_due_date_casting(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        $task = Task::create([
            'board_list_id' => $list->id,
            'title' => 'High priority task',
            'priority' => 'high',
            'due_date' => '2026-12-31',
            'position' => 0,
        ]);

        $this->assertEquals('high', $task->priority);
        $this->assertEquals('2026-12-31', $task->due_date->format('Y-m-d'));
        $this->assertIsInt($task->position);
    }
}

