<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Workspace $workspace;
    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Sara']);
        $this->workspace = Workspace::create([
            'name' => 'Analytics Workspace',
            'slug' => 'analytics-workspace',
            'owner_id' => $this->user->id,
        ]);
        WorkspaceMember::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->board = Board::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'title' => 'Development Board',
        ]);

        $todoList = BoardList::create([
            'board_id' => $this->board->id,
            'title' => 'قيد التنفيذ',
            'position' => 0,
        ]);

        $doneList = BoardList::create([
            'board_id' => $this->board->id,
            'title' => 'مكتملة',
            'position' => 1,
        ]);

        $task1 = Task::create([
            'board_list_id' => $todoList->id,
            'title' => 'Task One',
            'priority' => 'high',
            'due_date' => now()->subDays(2)->format('Y-m-d'), // Overdue
            'position' => 0,
        ]);
        $task1->assignees()->attach($this->user->id);

        Task::create([
            'board_list_id' => $doneList->id,
            'title' => 'Task Two',
            'priority' => 'low',
            'position' => 0,
        ]);
    }

    public function test_user_can_fetch_board_analytics(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/boards/{$this->board->id}/analytics");

        $response->assertOk()
            ->assertJsonPath('data.total_tasks', 2)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.completion_rate', 50)
            ->assertJsonPath('data.overdue_tasks_count', 1)
            ->assertJsonPath('data.priority_breakdown.high', 1)
            ->assertJsonPath('data.priority_breakdown.low', 1)
            ->assertJsonPath('data.member_workload.0.user_id', $this->user->id);
    }

    public function test_user_can_fetch_workspace_analytics(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/workspaces/{$this->workspace->id}/analytics");

        $response->assertOk()
            ->assertJsonPath('data.workspace_name', 'Analytics Workspace')
            ->assertJsonPath('data.total_boards', 1)
            ->assertJsonPath('data.total_tasks', 2)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.overall_completion_rate', 50)
            ->assertJsonPath('data.members_count', 1);
    }

    public function test_unauthorized_user_cannot_view_analytics(): void
    {
        $outsider = User::factory()->create();

        // Outsider attempts to view board analytics
        $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/boards/{$this->board->id}/analytics")
            ->assertStatus(403);

        // Outsider attempts to view workspace analytics
        $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/workspaces/{$this->workspace->id}/analytics")
            ->assertStatus(403);
    }
}
