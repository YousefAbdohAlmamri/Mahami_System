<?php

namespace Tests\Unit;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Eloquent\EloquentBoardListRepository;
use App\Repositories\Eloquent\EloquentBoardRepository;
use App\Repositories\Eloquent\EloquentTaskRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_container_binds_repository_interfaces(): void
    {
        $boardRepo = app(BoardRepositoryInterface::class);
        $listRepo = app(BoardListRepositoryInterface::class);
        $taskRepo = app(TaskRepositoryInterface::class);

        $this->assertInstanceOf(EloquentBoardRepository::class, $boardRepo);
        $this->assertInstanceOf(EloquentBoardListRepository::class, $listRepo);
        $this->assertInstanceOf(EloquentTaskRepository::class, $taskRepo);
    }

    public function test_board_repository_filters_by_user_and_orders(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $repo = app(BoardRepositoryInterface::class);

        $board1 = $repo->create(['user_id' => $user1->id, 'title' => 'User 1 Board']);
        $board2 = $repo->create(['user_id' => $user2->id, 'title' => 'User 2 Board']);

        $user1Boards = $repo->getForUser($user1->id);

        $this->assertCount(1, $user1Boards);
        $this->assertEquals($board1->id, $user1Boards->first()->id);
        $this->assertNull($repo->findByIdForUser($board2->id, $user1->id));
    }

    public function test_board_repository_eager_loads_lists_and_tasks_ordered_by_position(): void
    {
        $user = User::factory()->create();
        $boardRepo = app(BoardRepositoryInterface::class);
        $listRepo = app(BoardListRepositoryInterface::class);
        $taskRepo = app(TaskRepositoryInterface::class);

        $board = $boardRepo->create(['user_id' => $user->id, 'title' => 'Eager Load Test Board']);

        $list2 = $listRepo->create(['board_id' => $board->id, 'title' => 'Second', 'position' => 1]);
        $list1 = $listRepo->create(['board_id' => $board->id, 'title' => 'First', 'position' => 0]);

        $task2 = $taskRepo->create(['board_list_id' => $list1->id, 'title' => 'Task 2', 'position' => 1]);
        $task1 = $taskRepo->create(['board_list_id' => $list1->id, 'title' => 'Task 1', 'position' => 0]);

        $loaded = $boardRepo->findWithListsAndTasks($board->id, $user->id);

        $this->assertNotNull($loaded);
        $this->assertTrue($loaded->relationLoaded('lists'));
        $this->assertEquals('First', $loaded->lists->first()->title);
        $this->assertEquals('Second', $loaded->lists->last()->title);

        $firstListTasks = $loaded->lists->first()->tasks;
        $this->assertCount(2, $firstListTasks);
        $this->assertEquals('Task 1', $firstListTasks->first()->title);
        $this->assertEquals('Task 2', $firstListTasks->last()->title);
    }

    public function test_board_list_repository_reorder(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $listRepo = app(BoardListRepositoryInterface::class);

        $list1 = $listRepo->create(['board_id' => $board->id, 'title' => 'L1', 'position' => 0]);
        $list2 = $listRepo->create(['board_id' => $board->id, 'title' => 'L2', 'position' => 1]);
        $list3 = $listRepo->create(['board_id' => $board->id, 'title' => 'L3', 'position' => 2]);

        // Reverse order: L3, L2, L1
        $success = $listRepo->reorder($board->id, [$list3->id, $list2->id, $list1->id]);

        $this->assertTrue($success);
        $ordered = $listRepo->getForBoard($board->id);

        $this->assertEquals($list3->id, $ordered[0]->id);
        $this->assertEquals(0, $ordered[0]->position);
        $this->assertEquals($list2->id, $ordered[1]->id);
        $this->assertEquals(1, $ordered[1]->position);
        $this->assertEquals($list1->id, $ordered[2]->id);
        $this->assertEquals(2, $ordered[2]->position);
    }

    public function test_task_repository_move_between_lists(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $listRepo = app(BoardListRepositoryInterface::class);
        $taskRepo = app(TaskRepositoryInterface::class);

        $colA = $listRepo->create(['board_id' => $board->id, 'title' => 'Col A']);
        $colB = $listRepo->create(['board_id' => $board->id, 'title' => 'Col B']);

        $taskA1 = $taskRepo->create(['board_list_id' => $colA->id, 'title' => 'A1', 'position' => 0]);
        $taskA2 = $taskRepo->create(['board_list_id' => $colA->id, 'title' => 'A2', 'position' => 1]);

        $taskB1 = $taskRepo->create(['board_list_id' => $colB->id, 'title' => 'B1', 'position' => 0]);

        // Move taskA1 from Col A to Col B at position 0
        $moved = $taskRepo->move($taskA1, $colB->id, 0);

        $this->assertEquals($colB->id, $moved->board_list_id);
        $this->assertEquals(0, $moved->position);

        // B1 should have shifted to position 1
        $this->assertEquals(1, $taskB1->fresh()->position);

        // A2 should have shifted down to position 0 in Col A
        $this->assertEquals(0, $taskA2->fresh()->position);
    }

    public function test_task_repository_delete_recompacts_positions(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $listRepo = app(BoardListRepositoryInterface::class);
        $taskRepo = app(TaskRepositoryInterface::class);

        $list = $listRepo->create(['board_id' => $board->id, 'title' => 'Test List']);

        $t0 = $taskRepo->create(['board_list_id' => $list->id, 'title' => 'T0', 'position' => 0]);
        $t1 = $taskRepo->create(['board_list_id' => $list->id, 'title' => 'T1', 'position' => 1]);
        $t2 = $taskRepo->create(['board_list_id' => $list->id, 'title' => 'T2', 'position' => 2]);

        // Delete middle task t1
        $taskRepo->delete($t1);

        $this->assertEquals(0, $t0->fresh()->position);
        $this->assertEquals(1, $t2->fresh()->position);
    }
}

