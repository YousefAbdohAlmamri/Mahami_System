<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Board $board;
    private BoardList $list;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['name' => 'Alice']);
        $this->userB = User::factory()->create(['name' => 'Bob']);

        $this->board = Board::create([
            'user_id' => $this->userA->id,
            'title' => 'Project Notifications Board',
        ]);
        $this->list = BoardList::create([
            'board_id' => $this->board->id,
            'title' => 'To Do',
            'position' => 0,
        ]);
        $this->task = Task::create([
            'board_list_id' => $this->list->id,
            'title' => 'Deploy Realtime Service',
            'priority' => 'high',
            'position' => 0,
        ]);
    }

    public function test_user_receives_notification_when_assigned_to_task(): void
    {
        // User A assigns User B
        $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/assignees", [
                'user_ids' => [$this->userB->id],
            ])
            ->assertOk();

        // User B fetches their notifications
        $response = $this->actingAs($this->userB, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'task_assigned')
            ->assertJsonPath('data.0.task_id', $this->task->id);
    }

    public function test_user_receives_notification_when_mentioned_in_comment(): void
    {
        $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/comments", [
                'content' => 'Hello @Bob check this out!',
            ])
            ->assertStatus(201);

        // User B receives mention notification
        $response = $this->actingAs($this->userB, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.type', 'mention');
    }

    public function test_user_can_mark_notification_as_read_and_mark_all_as_read(): void
    {
        $this->userB->notify(new TaskAssignedNotification($this->task, $this->userA));
        $this->userB->notify(new TaskDueReminderNotification($this->task, false));

        $this->assertEquals(2, $this->userB->unreadNotifications()->count());

        $firstNotification = $this->userB->unreadNotifications()->first();

        // Mark single notification as read
        $this->actingAs($this->userB, 'sanctum')
            ->patchJson("/api/notifications/{$firstNotification->id}/read")
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        // Mark all as read
        $this->actingAs($this->userB, 'sanctum')
            ->postJson('/api/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertEquals(0, $this->userB->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_delete_notification(): void
    {
        $this->userB->notify(new TaskAssignedNotification($this->task, $this->userA));
        $notification = $this->userB->notifications()->first();

        $this->actingAs($this->userB, 'sanctum')
            ->deleteJson("/api/notifications/{$notification->id}")
            ->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_due_date_reminders_command_sends_notifications(): void
    {
        $today = now()->format('Y-m-d');
        $this->task->update(['due_date' => $today]);
        $this->task->assignees()->attach($this->userB->id);

        $this->artisan('mahami:send-due-reminders')
            ->expectsOutputToContain('Successfully sent 1 due date reminders.')
            ->assertSuccessful();

        $this->assertEquals(1, $this->userB->notifications()->count());
        $this->assertEquals('task_due_reminder', $this->userB->notifications()->first()->data['type']);
    }
}

