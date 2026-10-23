<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepository
    ) {}

    /**
     * Get the latest notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $this->notificationRepository->latestForUser($user, 40)
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? 'general',
                    'task_id' => $n->data['task_id'] ?? null,
                    'board_id' => $n->data['board_id'] ?? null,
                    'workspace_id' => $n->data['workspace_id'] ?? null,
                    'workspace_name' => $n->data['workspace_name'] ?? null,
                    'invitation_id' => $n->data['invitation_id'] ?? null,
                    'token' => $n->data['token'] ?? null,
                    'role' => $n->data['role'] ?? null,
                    'accepted' => $n->data['accepted'] ?? null,
                    'actor' => $n->data['actor'] ?? null,
                    'message' => $n->data['message'] ?? '',
                    'data' => $n->data,
                    'read_at' => $n->read_at?->toISOString(),
                    'created_at' => $n->created_at?->toISOString(),
                ];
            });

        return response()->json([
            'unread_count' => $this->notificationRepository->unreadCount($user),
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $this->notificationRepository->findForUserOrFail($user, $id);
        $this->notificationRepository->markAsRead($notification);

        return response()->json([
            'message' => 'Notification marked as read',
            'unread_count' => $this->notificationRepository->unreadCount($user),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationRepository->markAllAsRead($request->user());

        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $this->notificationRepository->findForUserOrFail($user, $id);
        $this->notificationRepository->delete($notification);

        return response()->json([
            'message' => 'Notification deleted successfully',
            'unread_count' => $this->notificationRepository->unreadCount($user),
        ]);
    }
}
