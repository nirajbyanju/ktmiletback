<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserNotificationService
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));
        $status = $filters['status'] ?? 'all';

        $query = $user->notifications()->latest();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        }

        if ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): ?DatabaseNotification
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return null;
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);
    }

    public function format(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? $notification->type,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'severity' => $data['severity'] ?? 'info',
            'action_url' => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'entity' => $data['entity'] ?? [],
            'actor' => $data['actor'] ?? [],
            'meta' => $data['meta'] ?? [],
            'read_at' => optional($notification->read_at)->toISOString(),
            'created_at' => optional($notification->created_at)->toISOString(),
        ];
    }
}
