<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create primary test user
        $primaryUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Create primary user's boards
        $projectBoard = Board::firstOrCreate(
            ['user_id' => $primaryUser->id, 'title' => 'Mini Trello Launch'],
            ['description' => 'MVP launch tasks, sprints, and release tracking.']
        );

        $marketingBoard = Board::firstOrCreate(
            ['user_id' => $primaryUser->id, 'title' => 'Marketing & Outreach'],
            ['description' => 'Social media campaigns and user acquisition.']
        );

        // 3. Create default lists for project board
        $columns = [
            ['title' => 'To Do', 'position' => 0],
            ['title' => 'In Progress', 'position' => 1],
            ['title' => 'Done', 'position' => 2],
        ];

        $listModels = [];
        foreach ($columns as $col) {
            $listModels[$col['title']] = BoardList::firstOrCreate(
                ['board_id' => $projectBoard->id, 'title' => $col['title']],
                ['position' => $col['position']]
            );
        }

        // 4. Create sample tasks
        if (isset($listModels['To Do'])) {
            Task::firstOrCreate(
                ['board_list_id' => $listModels['To Do']->id, 'title' => 'Set up automated backups'],
                [
                    'description' => 'Configure daily cron backups for MySQL database.',
                    'priority' => 'medium',
                    'due_date' => now()->addDays(5)->toDateString(),
                    'position' => 0,
                ]
            );

            Task::firstOrCreate(
                ['board_list_id' => $listModels['To Do']->id, 'title' => 'Review Arabic translations'],
                [
                    'description' => 'Ensure all terminology matches standard Arabic UI guidelines.',
                    'priority' => 'low',
                    'due_date' => now()->addDays(7)->toDateString(),
                    'position' => 1,
                ]
            );
        }

        if (isset($listModels['In Progress'])) {
            Task::firstOrCreate(
                ['board_list_id' => $listModels['In Progress']->id, 'title' => 'Implement Kanban Drag & Drop'],
                [
                    'description' => 'Refine optimistic movement and backend sync with SortableJS.',
                    'priority' => 'high',
                    'due_date' => now()->addDays(2)->toDateString(),
                    'position' => 0,
                ]
            );
        }

        if (isset($listModels['Done'])) {
            Task::firstOrCreate(
                ['board_list_id' => $listModels['Done']->id, 'title' => 'Database schema & repository pattern'],
                [
                    'description' => 'Implemented migrations, models, repository interfaces and Eloquent bindings.',
                    'priority' => 'high',
                    'due_date' => now()->subDay()->toDateString(),
                    'position' => 0,
                ]
            );
        }

        // 5. Create a secondary user for isolation tests
        $secondaryUser = User::firstOrCreate(
            ['email' => 'other@example.com'],
            [
                'name' => 'Other User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $otherBoard = Board::firstOrCreate(
            ['user_id' => $secondaryUser->id, 'title' => 'Private Board'],
            ['description' => 'Board that must not be accessible to demo user.']
        );

        BoardList::firstOrCreate(
            ['board_id' => $otherBoard->id, 'title' => 'Secret Column'],
            ['position' => 0]
        );
    }
}
