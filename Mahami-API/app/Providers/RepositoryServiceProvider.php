<?php

namespace App\Providers;

use App\Repositories\Contracts\BoardListRepositoryInterface;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\WorkspaceInvitationRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Repositories\Eloquent\EloquentBoardListRepository;
use App\Repositories\Eloquent\EloquentBoardRepository;
use App\Repositories\Eloquent\EloquentTaskRepository;
use App\Repositories\Eloquent\EloquentWorkspaceInvitationRepository;
use App\Repositories\Eloquent\EloquentWorkspaceRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        BoardRepositoryInterface::class => EloquentBoardRepository::class,
        BoardListRepositoryInterface::class => EloquentBoardListRepository::class,
        TaskRepositoryInterface::class => EloquentTaskRepository::class,
        WorkspaceRepositoryInterface::class => EloquentWorkspaceRepository::class,
        WorkspaceInvitationRepositoryInterface::class => EloquentWorkspaceInvitationRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

