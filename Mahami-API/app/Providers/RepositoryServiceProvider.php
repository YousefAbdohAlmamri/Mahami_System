<?php

namespace App\Providers;

use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\LabelRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\TaskAttachmentRepositoryInterface;
use App\Repositories\Contracts\TaskChecklistRepositoryInterface;
use App\Repositories\Contracts\TaskCommentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\WorkspaceInvitationRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Repositories\Eloquent\EloquentActivityLogRepository;
use App\Repositories\Eloquent\EloquentLabelRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentTaskAttachmentRepository;
use App\Repositories\Eloquent\EloquentTaskChecklistRepository;
use App\Repositories\Eloquent\EloquentTaskCommentRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentBoardListRepository;
use App\Repositories\Eloquent\EloquentBoardRepository;
use App\Repositories\Eloquent\EloquentTaskRepository;
use App\Repositories\Eloquent\EloquentTeamRepository;
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
        TeamRepositoryInterface::class => EloquentTeamRepository::class,
        ActivityLogRepositoryInterface::class => EloquentActivityLogRepository::class,
        LabelRepositoryInterface::class => EloquentLabelRepository::class,
        NotificationRepositoryInterface::class => EloquentNotificationRepository::class,
        TaskAttachmentRepositoryInterface::class => EloquentTaskAttachmentRepository::class,
        TaskChecklistRepositoryInterface::class => EloquentTaskChecklistRepository::class,
        TaskCommentRepositoryInterface::class => EloquentTaskCommentRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
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

