<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function latestForUser(User $user, int $limit = 40): Collection
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function findForUserOrFail(User $user, string $id): DatabaseNotification
    {
        return $user->notifications()->findOrFail($id);
    }

    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function delete(DatabaseNotification $notification): void
    {
        $notification->delete();
    }

    public function markInvitationNotificationsRead(User $user, string $token): int
    {
        return $user->notifications()
            ->where('data->type', 'workspace_invitation')
            ->where('data->token', $token)
            ->update(['read_at' => now()]);
    }
}
