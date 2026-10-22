<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_throttling_protects_against_brute_force(): void
    {
        // 15 attempts allowed per minute on auth limiter
        for ($i = 0; $i < 15; $i++) {
            $this->postJson('/api/login', [
                'email' => 'attacker@example.com',
                'password' => 'wrong-pass',
            ]);
        }

        // 16th attempt must be throttled with 429
        $throttledResponse = $this->postJson('/api/login', [
            'email' => 'attacker@example.com',
            'password' => 'wrong-pass',
        ]);

        $throttledResponse->assertStatus(429);
    }

    public function test_strict_tenant_isolation_prevents_cross_workspace_data_leaks(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);

        $workspaceA = Workspace::create([
            'name' => 'Private Workspace A',
            'slug' => 'private-ws-a',
            'owner_id' => $userA->id,
        ]);
        WorkspaceMember::create([
            'workspace_id' => $workspaceA->id,
            'user_id' => $userA->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $boardA = Board::create([
            'workspace_id' => $workspaceA->id,
            'user_id' => $userA->id,
            'title' => 'Top Secret Board',
        ]);
        $listA = BoardList::create([
            'board_id' => $boardA->id,
            'title' => 'Confidential',
            'position' => 0,
        ]);
        $taskA = Task::create([
            'board_list_id' => $listA->id,
            'title' => 'Confidential Roadmap',
            'priority' => 'high',
            'position' => 0,
        ]);
        TaskComment::create([
            'task_id' => $taskA->id,
            'user_id' => $userA->id,
            'content' => 'Classified information here',
        ]);

        // User B attempts to access User A's assets
        $this->actingAs($userB, 'sanctum')
            ->getJson("/api/boards/{$boardA->id}")
            ->assertStatus(403);

        $this->actingAs($userB, 'sanctum')
            ->getJson("/api/tasks/{$taskA->id}")
            ->assertStatus(403);

        $this->actingAs($userB, 'sanctum')
            ->getJson("/api/tasks/{$taskA->id}/comments")
            ->assertStatus(403);

        $this->actingAs($userB, 'sanctum')
            ->getJson("/api/boards/{$boardA->id}/analytics")
            ->assertStatus(403);

        $this->actingAs($userB, 'sanctum')
            ->getJson("/api/workspaces/{$workspaceA->id}/analytics")
            ->assertStatus(403);
    }

    public function test_xss_payloads_in_comments_are_safely_handled(): void
    {
        $user = User::factory()->create();
        $board = Board::create([
            'user_id' => $user->id,
            'title' => 'Safe Board',
        ]);
        $list = BoardList::create([
            'board_id' => $board->id,
            'title' => 'Column',
            'position' => 0,
        ]);
        $task = Task::create([
            'board_list_id' => $list->id,
            'title' => 'XSS Testing Task',
            'priority' => 'medium',
            'position' => 0,
        ]);

        $xssPayload = '<script>alert("hacked")</script>';

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/comments", [
                'content' => $xssPayload,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.content', $xssPayload);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'content' => $xssPayload,
        ]);
    }

    public function test_eager_loading_prevents_n_plus_one_queries_on_board_details(): void
    {
        $user = User::factory()->create();
        $board = Board::create([
            'user_id' => $user->id,
            'title' => 'Performance Test Board',
        ]);

        // Create 3 lists with 3 tasks each
        for ($i = 0; $i < 3; $i++) {
            $list = BoardList::create([
                'board_id' => $board->id,
                'title' => "List {$i}",
                'position' => $i,
            ]);

            for ($j = 0; $j < 3; $j++) {
                Task::create([
                    'board_list_id' => $list->id,
                    'title' => "Task {$i}-{$j}",
                    'priority' => 'medium',
                    'position' => $j,
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/boards/{$board->id}");

        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Query count must remain minimal (under 12 queries total including sanctum & relations)
        $this->assertLessThanOrEqual(12, $queryCount);
    }
}

