<?php

namespace App\Actions\Boards;

use App\Models\Board;
use App\Models\User;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateBoardAction
{
    /**
     * Default Kanban columns created for every new board.
     *
     * @var list<array{title: string, position: int}>
     */
    protected const DEFAULT_COLUMNS = [
        ['title' => 'To Do', 'position' => 0],
        ['title' => 'In Progress', 'position' => 1],
        ['title' => 'Done', 'position' => 2],
    ];

    public function __construct(
        protected BoardRepositoryInterface $boardRepository,
        protected BoardListRepositoryInterface $boardListRepository
    ) {}

    /**
     * Create a board along with its default columns in an atomic transaction.
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return Board
     */
    public function execute(User $user, array $data): Board
    {
        return DB::transaction(function () use ($user, $data) {
            $board = $this->boardRepository->create([
                'user_id' => $user->id,
                'workspace_id' => $data['workspace_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            foreach (self::DEFAULT_COLUMNS as $column) {
                $this->boardListRepository->create([
                    'board_id' => $board->id,
                    'title' => $column['title'],
                    'position' => $column['position'],
                ]);
            }

            return $this->boardRepository->findWithListsAndTasks($board->id, $user->id) ?? $board;
        });
    }
}

