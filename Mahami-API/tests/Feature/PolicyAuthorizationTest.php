<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use App\Policies\BoardListPolicy;
use App\Policies\BoardPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_policy_enforces_ownership(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $policy = new BoardPolicy();

        $this->assertTrue($policy->view($owner, $board));
        $this->assertTrue($policy->update($owner, $board));
        $this->assertTrue($policy->delete($owner, $board));

        $this->assertFalse($policy->view($stranger, $board));
        $this->assertFalse($policy->update($stranger, $board));
        $this->assertFalse($policy->delete($stranger, $board));
    }

    public function test_board_list_policy_enforces_board_ownership(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $policy = new BoardListPolicy();

        $this->assertTrue($policy->view($owner, $list));
        $this->assertTrue($policy->create($owner, $board));
        $this->assertTrue($policy->update($owner, $list));
        $this->assertTrue($policy->delete($owner, $list));
        $this->assertTrue($policy->reorder($owner, $board));

        $this->assertFalse($policy->view($stranger, $list));
        $this->assertFalse($policy->create($stranger, $board));
        $this->assertFalse($policy->update($stranger, $list));
        $this->assertFalse($policy->delete($stranger, $list));
        $this->assertFalse($policy->reorder($stranger, $board));
    }

    public function test_task_policy_enforces_ownership_and_cross_board_movement_restrictions(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $boardA = Board::factory()->create(['user_id' => $owner->id]);
        $listA1 = BoardList::factory()->create(['board_id' => $boardA->id]);
        $listA2 = BoardList::factory()->create(['board_id' => $boardA->id]);

        $boardB = Board::factory()->create(['user_id' => $owner->id]);
        $listB = BoardList::factory()->create(['board_id' => $boardB->id]);

        $strangerBoard = Board::factory()->create(['user_id' => $stranger->id]);
        $strangerList = BoardList::factory()->create(['board_id' => $strangerBoard->id]);

        $task = Task::factory()->create(['board_list_id' => $listA1->id]);
        $policy = new TaskPolicy();

        // 1. Owner can view/update/delete task
        $this->assertTrue($policy->view($owner, $task));
        $this->assertTrue($policy->update($owner, $task));
        $this->assertTrue($policy->delete($owner, $task));

        // 2. Stranger cannot view/update/delete task
        $this->assertFalse($policy->view($stranger, $task));
        $this->assertFalse($policy->update($stranger, $task));
        $this->assertFalse($policy->delete($stranger, $task));

        // 3. Moving task within same board between lists is ALLOWED
        $this->assertTrue($policy->move($owner, $task, $listA2));

        // 4. Moving task to a list on a DIFFERENT board (even owned by same user) is DENIED
        $this->assertFalse($policy->move($owner, $task, $listB));

        // 5. Moving task to a stranger's list is DENIED
        $this->assertFalse($policy->move($owner, $task, $strangerList));
        $this->assertFalse($policy->move($stranger, $task, $strangerList));
    }
}

