<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

abstract class BaseRealtimeNotification extends Notification
{
    use Queueable;

    final public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    final public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    final public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    final public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    final public function databaseType(object $notifiable): string
    {
        return $this->type();
    }

    final public function broadcastType(): string
    {
        return $this->type();
    }

    abstract protected function type(): string;

    abstract protected function payload(): array;

    protected function buildPayload(
        string $type,
        string $title,
        string $message,
        string $actionUrl,
        string $actionLabel,
        array $entity = [],
        array $actor = [],
        array $meta = [],
        string $severity = 'info',
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'entity' => $entity,
            'actor' => $actor,
            'meta' => $meta,
            'created_at' => now()->toISOString(),
        ];
    }

    protected function userData(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return [
            'id' => $user->id,
            'name' => $user->display_name,
            'username' => $user->username,
            'email' => $user->email,
        ];
    }
}
