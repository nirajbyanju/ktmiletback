<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function __construct(
        private readonly UserNotificationService $userNotificationService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:all,read,unread',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $notifications = $this->userNotificationService->paginateForUser($request->user(), $validated);

        return response()->json([
            'success' => true,
            'data' => collect($notifications->items())
                ->map(fn ($notification) => $this->userNotificationService->format($notification))
                ->values(),
            'meta' => [
                'unread_count' => $this->userNotificationService->unreadCount($request->user()),
            ],
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $this->userNotificationService->unreadCount($request->user()),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $this->userNotificationService->markAsRead($request->user(), $notificationId);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->userNotificationService->format($notification),
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updatedCount = $this->userNotificationService->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $updatedCount,
            ],
            'message' => 'All notifications marked as read.',
        ]);
    }
}
