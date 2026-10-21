<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_labels_and_attach_to_task(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        // 1. Create label
        $labelResponse = $this->actingAs($user)->postJson("/api/boards/{$board->id}/labels", [
            'name' => 'Bug',
            'color' => '#ef4444',
        ]);
        $labelResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Bug')
            ->assertJsonPath('data.color', '#ef4444');

        $labelId = $labelResponse->json('data.id');

        // 2. Attach label to task
        $attachResponse = $this->actingAs($user)->postJson("/api/tasks/{$task->id}/labels/{$labelId}");
        $attachResponse->assertStatus(200);

        $this->assertDatabaseHas('task_labels', [
            'task_id' => $task->id,
            'label_id' => $labelId,
        ]);

        // 3. Detach label
        $detachResponse = $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}/labels/{$labelId}");
        $detachResponse->assertStatus(200);

        $this->assertDatabaseMissing('task_labels', [
            'task_id' => $task->id,
            'label_id' => $labelId,
        ]);
    }

    public function test_can_sync_task_assignees(): void
    {
        $owner = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        $syncResponse = $this->actingAs($owner)->postJson("/api/tasks/{$task->id}/assignees", [
            'user_ids' => [$user1->id, $user2->id],
        ]);

        $syncResponse->assertStatus(200);

        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $user2->id,
        ]);

        // Remove one user
        $removeResponse = $this->actingAs($owner)->deleteJson("/api/tasks/{$task->id}/assignees/{$user1->id}");
        $removeResponse->assertStatus(200);

        $this->assertDatabaseMissing('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $user2->id,
        ]);
    }

    public function test_can_manage_checklists_and_calculate_progress(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        // 1. Create checklist
        $checklistResponse = $this->actingAs($user)->postJson("/api/tasks/{$task->id}/checklists", [
            'title' => 'QA Verification',
        ]);
        $checklistResponse->assertStatus(201)
            ->assertJsonPath('data.title', 'QA Verification');

        $checklistId = $checklistResponse->json('data.id');

        // 2. Add two items
        $item1Response = $this->actingAs($user)->postJson("/api/checklists/{$checklistId}/items", [
            'title' => 'Unit tests pass',
        ]);
        $item1Response->assertStatus(201);
        $item1Id = $item1Response->json('data.id');

        $item2Response = $this->actingAs($user)->postJson("/api/checklists/{$checklistId}/items", [
            'title' => 'Security review approved',
        ]);
        $item2Response->assertStatus(201);

        // 3. Mark first item as completed
        $toggleResponse = $this->actingAs($user)->putJson("/api/checklist-items/{$item1Id}", [
            'is_completed' => true,
        ]);
        $toggleResponse->assertStatus(200)
            ->assertJsonPath('data.is_completed', true);

        // 4. Check task representation has 50% progress
        $taskResponse = $this->actingAs($user)->getJson("/api/tasks/{$task->id}");
        $taskResponse->assertStatus(200)
            ->assertJsonPath('data.checklist_summary.total', 2)
            ->assertJsonPath('data.checklist_summary.completed', 1)
            ->assertJsonPath('data.checklist_summary.percentage', 50);
    }

    public function test_can_upload_and_delete_attachments(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $task = Task::factory()->create(['board_list_id' => $list->id]);

        $file = UploadedFile::fake()->create('spec.pdf', 500, 'application/pdf');

        $uploadResponse = $this->actingAs($user)->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => $file,
        ]);

        $uploadResponse->assertStatus(201)
            ->assertJsonPath('data.file_name', 'spec.pdf');

        $attachmentId = $uploadResponse->json('data.id');
        $this->assertDatabaseHas('task_attachments', [
            'id' => $attachmentId,
            'task_id' => $task->id,
            'file_name' => 'spec.pdf',
        ]);

        // Delete attachment
        $deleteResponse = $this->actingAs($user)->deleteJson("/api/attachments/{$attachmentId}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('task_attachments', [
            'id' => $attachmentId,
        ]);
    }
}

