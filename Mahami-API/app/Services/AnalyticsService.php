<?php

namespace App\Services;

use App\Models\Board;
use App\Models\Task;
use App\Models\Workspace;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;

class AnalyticsService
{
    public function __construct(
        protected BoardRepositoryInterface $boardRepository,
        protected WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    /**
     * Calculate comprehensive analytics for a board.
     */
    public function getBoardAnalytics(Board $board): array
    {
        $this->boardRepository->loadForAnalytics($board);

        $allTasks = collect();
        $statusBreakdown = [];
        $completedTasksCount = 0;
        $today = now()->format('Y-m-d');

        $lists = $board->lists->sortBy('position')->values();
        $lastListId = $lists->last()?->id;

        foreach ($lists as $list) {
            $tasks = $list->tasks;
            $allTasks = $allTasks->concat($tasks);

            // Infer completion if column title indicates done or is the final list in standard flow
            $isDoneColumn = (bool) preg_match('/(done|مكتمل|منجز|تم|archive)/ui', $list->title) ||
                ($lists->count() > 1 && $list->id === $lastListId);

            if ($isDoneColumn) {
                $completedTasksCount += $tasks->count();
            }

            $statusBreakdown[] = [
                'list_id' => $list->id,
                'list_title' => $list->title,
                'task_count' => $tasks->count(),
                'is_completed_column' => $isDoneColumn,
            ];
        }

        $totalTasks = $allTasks->count();
        $completionRate = $totalTasks > 0 ? (int) round(($completedTasksCount / $totalTasks) * 100) : 0;

        // Overdue & Due Today
        $overdueTasks = $allTasks->filter(function ($task) use ($today) {
            return $task->due_date && $task->due_date->format('Y-m-d') < $today;
        });

        $dueTodayTasks = $allTasks->filter(function ($task) use ($today) {
            return $task->due_date && $task->due_date->format('Y-m-d') === $today;
        });

        // Priority breakdown
        $priorityCounts = [
            'high' => $allTasks->where('priority', 'high')->count(),
            'medium' => $allTasks->where('priority', 'medium')->count(),
            'low' => $allTasks->where('priority', 'low')->count(),
        ];

        // Assignee workload
        $memberWorkload = [];
        foreach ($allTasks as $task) {
            foreach ($task->assignees as $assignee) {
                if (!isset($memberWorkload[$assignee->id])) {
                    $memberWorkload[$assignee->id] = [
                        'user_id' => $assignee->id,
                        'name' => $assignee->name,
                        'email' => $assignee->email,
                        'assigned_tasks' => 0,
                    ];
                }
                $memberWorkload[$assignee->id]['assigned_tasks']++;
            }
        }

        return [
            'board_id' => $board->id,
            'board_title' => $board->title,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasksCount,
            'completion_rate' => $completionRate,
            'overdue_tasks_count' => $overdueTasks->count(),
            'due_today_count' => $dueTodayTasks->count(),
            'priority_breakdown' => $priorityCounts,
            'status_breakdown' => $statusBreakdown,
            'member_workload' => array_values($memberWorkload),
        ];
    }

    /**
     * Calculate comprehensive analytics across all boards in a workspace.
     */
    public function getWorkspaceAnalytics(Workspace $workspace): array
    {
        $boards = $this->workspaceRepository->getBoardsForAnalytics($workspace);

        $totalBoards = $boards->count();
        $totalTasks = 0;
        $completedTasks = 0;
        $overdueTasks = 0;
        $today = now()->format('Y-m-d');
        $priorityTotals = ['high' => 0, 'medium' => 0, 'low' => 0];
        $boardSummaries = [];

        foreach ($boards as $board) {
            $boardStats = $this->getBoardAnalytics($board);

            $totalTasks += $boardStats['total_tasks'];
            $completedTasks += $boardStats['completed_tasks'];
            $overdueTasks += $boardStats['overdue_tasks_count'];

            $priorityTotals['high'] += $boardStats['priority_breakdown']['high'];
            $priorityTotals['medium'] += $boardStats['priority_breakdown']['medium'];
            $priorityTotals['low'] += $boardStats['priority_breakdown']['low'];

            $boardSummaries[] = [
                'id' => $board->id,
                'title' => $board->title,
                'total_tasks' => $boardStats['total_tasks'],
                'completion_rate' => $boardStats['completion_rate'],
                'overdue_count' => $boardStats['overdue_tasks_count'],
            ];
        }

        $overallCompletionRate = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

        return [
            'workspace_id' => $workspace->id,
            'workspace_name' => $workspace->name,
            'total_boards' => $totalBoards,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overall_completion_rate' => $overallCompletionRate,
            'overdue_tasks_count' => $overdueTasks,
            'members_count' => $this->workspaceRepository->countMembers($workspace),
            'priority_breakdown' => $priorityTotals,
            'board_summaries' => $boardSummaries,
        ];
    }
}

