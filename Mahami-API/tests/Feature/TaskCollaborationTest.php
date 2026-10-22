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
use Tests\TestCase;

class TaskCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Board $board;
    private BoardList $list;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->board = Board::create([
            'user_id' => $this->user->id,
            'title' => 'Collaboration Board',
        ]);
        $this->list = BoardList::create([
            'board_id' => $this->board->id,
            'title' => 'In Review',
            'position' => 0,
        ]);
        $this->task = Task::create([
            'board_list_id' => $this->list->id,
            'title' => 'Implement Task Comments',
            'priority' => 'high',
            'position' => 0,
        ]);
    }

    public function test_user_can_create_and_list_comments_on_task(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/comments", [
                'content' => 'Hey @developer please review this implementation! #urgent',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.content', 'Hey @developer please review this implementation! #urgent')
            ->assertJsonPath('data.mentions', ['developer'])
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.can_delete', true);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        // Check activity log automatically generated
        $this->assertDatabaseHas('activity_logs', [
            'board_id' => $this->board->id,
            'task_id' => $this->task->id,
            'action' => 'commented',
        ]);

        // List comments
        $listResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks/{$this->task->id}/comments");

        $listResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Hey @developer please review this implementation! #urgent');
    }

    public function test_comment_author_can_update_and_delete_their_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Initial comment text',
        ]);

        // Update comment
        $updateResponse = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated comment text by author',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.content', 'Updated comment text by author');

        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
            'content' => 'Updated comment text by author',
        ]);

        // Delete comment
        $deleteResponse = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/comments/{$comment->id}");

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_unauthorized_user_cannot_update_or_delete_another_users_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Secret comment',
        ]);

        $otherUser = User::factory()->create();

        // Other user attempts to update
        $updateResponse = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Malicious update',
            ]);

        $updateResponse->assertStatus(403);

        // Other user attempts to delete
        $deleteResponse = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/comments/{$comment->id}");

        $deleteResponse->assertStatus(403);
    }

    public function test_user_can_view_task_and_board_activities(): void
    {
        // Generate a comment which creates an activity
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/comments", [
                'content' => 'Activity generating comment',
            ]);

        // Task activities
        $taskActivityRes = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks/{$this->task->id}/activities");

        $taskActivityRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'commented');

        // Board activities
        $boardActivityRes = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/boards/{$this->board->id}/activities");

        $boardActivityRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.board_id', $this->board->id);
    }
}

