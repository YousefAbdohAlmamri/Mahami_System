<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function latestForUser(User $user, int $limit = 40): Collection;

    public function unreadCount(User $user): int;

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findForUserOrFail(User $user, string $id): DatabaseNotification;

    public function markAsRead(DatabaseNotification $notification): void;

    public function markAllAsRead(User $user): void;

    public function delete(DatabaseNotification $notification): void;

    /**
     * Mark the workspace-invitation notifications of a given token as read.
     */
    public function markInvitationNotificationsRead(User $user, string $token): int;
}
