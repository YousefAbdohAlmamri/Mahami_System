<?php

namespace Tests\Unit;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use App\Repositories\Contracts\LabelRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\TaskAttachmentRepositoryInterface;
use App\Repositories\Contracts\TaskChecklistRepositoryInterface;
use App\Repositories\Contracts\TaskCommentRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WorkspaceInvitationRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullRepositoryCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_repository_interfaces_are_bound(): void
    {
        $interfaces = [
            ActivityLogRepositoryInterface::class,
            LabelRepositoryInterface::class,
            NotificationRepositoryInterface::class,
            TaskAttachmentRepositoryInterface::class,
            TaskChecklistRepositoryInterface::class,
            TaskCommentRepositoryInterface::class,
            UserRepositoryInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $this->assertInstanceOf($interface, app($interface));
        }
    }

    public function test_controllers_do_not_query_eloquent_models_directly(): void
    {
        $pattern = '/\b(User|Task|Board|BoardList|Team|Workspace|WorkspaceInvitation|Label|TaskComment|TaskAttachment|TaskChecklist|TaskChecklistItem|ActivityLog)::(where|find|findOrFail|create|with|query|all|whereIn|first|count)\b/';

        $paths = array_merge(
            glob(app_path('Http/Controllers/Api/*.php')),
            glob(app_path('Services/*.php')),
            glob(app_path('Console/Commands/*.php')),
        );

        foreach ($paths as $path) {
            $this->assertSame(
                0,
                preg_match($pattern, file_get_contents($path)),
                basename($path).' must access data through a repository.'
            );
        }
    }

    public function test_user_repository(): void
    {
        $repo = app(UserRepositoryInterface::class);

        $user = $repo->create([
            'name' => 'Repo User',
            'email' => 'repo.user@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertSame($user->id, $repo->find($user->id)->id);
        $this->assertSame($user->id, $repo->findOrFail($user->id)->id);
        $this->assertSame($user->id, $repo->findByEmail('repo.user@example.com')->id);
        $this->assertNull($repo->findByEmail('missing@example.com'));
        $this->assertCount(1, $repo->findByNames(['Repo User', 'Nobody']));
    }

    public function test_label_repository(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $repo = app(LabelRepositoryInterface::class);

        $label = $repo->create($board, ['name' => 'Bug', 'color' => '#ff0000']);
        $this->assertCount(1, $repo->forBoard($board));

        $label = $repo->update($label, ['name' => 'Defect']);
        $this->assertSame('Defect', $label->fresh()->name);

        $this->assertTrue($repo->delete($label));
        $this->assertCount(0, $repo->forBoard($board));
    }

    public function test_checklist_repository_appends_positions(): void
    {
        $task = Task::factory()->create();
        $repo = app(TaskChecklistRepositoryInterface::class);

        $first = $repo->createChecklist($task, 'First');
        $second = $repo->createChecklist($task, 'Second');

        $this->assertSame(0, (int) $first->position);
        $this->assertSame(1, (int) $second->position);
        $this->assertTrue($second->relationLoaded('items'));

        $itemA = $repo->addItem($first, 'A');
        $itemB = $repo->addItem($first, 'B');
        $this->assertSame(0, (int) $itemA->position);
        $this->assertSame(1, (int) $itemB->position);
        $this->assertFalse((bool) $itemA->is_completed);

        $updated = $repo->updateItem($itemA, ['is_completed' => true]);
        $this->assertTrue((bool) $updated->fresh()->is_completed);

        $renamed = $repo->updateChecklist($first, ['title' => 'Renamed']);
        $this->assertSame('Renamed', $renamed->title);

        $this->assertTrue($repo->deleteItem($itemB));
        $this->assertTrue($repo->deleteChecklist($second));
    }

    public function test_comment_repository(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $repo = app(TaskCommentRepositoryInterface::class);

        $comment = $repo->create($task, $user->id, 'Hello');
        $this->assertCount(1, $repo->forTask($task));

        $loaded = $repo->findOrFailWithContext($comment->id);
        $this->assertTrue($loaded->relationLoaded('task'));

        $updated = $repo->update($loaded, 'Edited');
        $this->assertSame('Edited', $updated->content);
        $this->assertTrue($updated->relationLoaded('user'));

        $this->assertTrue($repo->delete($updated));
        $this->assertCount(0, $repo->forTask($task));
    }

    public function test_task_repository_relations_and_queries(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $t1 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 1, 'due_date' => now()->subDay()->format('Y-m-d')]);
        $t0 = Task::factory()->create(['board_list_id' => $list->id, 'position' => 0, 'due_date' => null]);

        $repo = app(TaskRepositoryInterface::class);

        // ordering + counting
        $this->assertEquals([$t0->id, $t1->id], $repo->getForList($list->id)->pluck('id')->all());
        $this->assertSame(2, $repo->countInList($list->id, [$t0->id, $t1->id]));
        $this->assertSame(1, $repo->countInList($list->id, [$t0->id, 999999]));

        // context loading
        $this->assertTrue($repo->findOrFailWithContext($t0->id)->relationLoaded('list'));

        // assignees
        $repo->syncAssignees($t1, [$owner->id, $other->id]);
        $this->assertEqualsCanonicalizing([$owner->id, $other->id], $repo->assigneeIds($t1));
        $repo->removeAssignee($t1, $other->id);
        $this->assertSame([$owner->id], $repo->assigneeIds($t1));
        $repo->addAssignee($t1, $other->id);
        $this->assertCount(2, $repo->assigneeIds($t1));

        // due tasks
        $due = $repo->getDueOnOrBefore(now()->format('Y-m-d'));
        $this->assertTrue($due->contains('id', $t1->id));
        $this->assertFalse($due->contains('id', $t0->id));

        // details refresh
        $fresh = $repo->refreshWithDetails($t1);
        $this->assertTrue($fresh->relationLoaded('assignees'));
        $this->assertTrue($fresh->relationLoaded('checklists'));
    }

    public function test_board_list_repository_extras(): void
    {
        $board = Board::factory()->create();
        $other = Board::factory()->create();
        $mine = BoardList::factory()->create(['board_id' => $board->id]);
        $foreign = BoardList::factory()->create(['board_id' => $other->id]);
        $repo = app(BoardListRepositoryInterface::class);

        $this->assertSame($mine->id, $repo->find($mine->id)->id);
        $this->assertNull($repo->find(999999));
        $this->assertSame(1, $repo->countInBoard($board->id, [$mine->id, $foreign->id]));
    }

    public function test_workspace_repository_membership_helpers(): void
    {
        $owner = User::factory()->create();
        $active = User::factory()->create();
        $suspended = User::factory()->create();
        $stranger = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $workspace->members()->attach($active->id, ['role' => 'member', 'status' => 'active']);
        $workspace->members()->attach($suspended->id, ['role' => 'member', 'status' => 'suspended']);

        $repo = app(WorkspaceRepositoryInterface::class);

        $this->assertSame($workspace->id, $repo->findOrFail($workspace->id)->id);
        $this->assertSame(2, $repo->countMembers($workspace));
        $this->assertCount(2, $repo->getMembers($workspace));

        $this->assertTrue($repo->hasMember($workspace, $suspended->id));
        $this->assertFalse($repo->hasMember($workspace, $stranger->id));

        $this->assertTrue($repo->isActiveMember($workspace, $active->id));
        $this->assertFalse($repo->isActiveMember($workspace, $suspended->id));

        $this->assertTrue($repo->isOwnerOrActiveMember($workspace, $owner->id));
        $this->assertTrue($repo->isOwnerOrActiveMember($workspace, $active->id));
        $this->assertFalse($repo->isOwnerOrActiveMember($workspace, $suspended->id));
        $this->assertFalse($repo->isOwnerOrActiveMember($workspace, $stranger->id));
    }

    public function test_invitation_repository_pending_helpers(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $repo = app(WorkspaceInvitationRepositoryInterface::class);

        $invitation = $repo->create($workspace, $owner, ['email' => 'Guest@Example.com', 'role' => 'member']);

        $this->assertCount(1, $repo->pendingForEmail('guest@example.com'));

        $this->assertSame(1, $repo->cancelPendingForEmail($workspace, 'guest@example.com'));
        $this->assertCount(0, $repo->pendingForEmail('guest@example.com'));
        $this->assertNotNull($invitation->fresh()->cancelled_at);
    }

    public function test_activity_log_repository(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);
        $repo = app(ActivityLogRepositoryInterface::class);

        $repo->create([
            'workspace_id' => null,
            'board_id' => $board->id,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'created',
            'description' => 'Test',
            'properties' => null,
            'created_at' => now(),
        ]);

        $this->assertCount(1, $repo->latestForTask($task->id));
        $this->assertCount(1, $repo->latestForBoard($board->id));
        $this->assertCount(0, $repo->latestForTask(999999));
    }

    public function test_notification_repository(): void
    {
        $user = User::factory()->create();
        $repo = app(NotificationRepositoryInterface::class);

        $this->assertSame(0, $repo->unreadCount($user));
        $this->assertCount(0, $repo->latestForUser($user));
    }
}
