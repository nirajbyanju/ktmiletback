<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class AdminNotificationService
{
    private const RECIPIENT_ROLES = ['Super Admin', 'Admin'];

    public function notifyNewRegistration(User $registeredUser): void
    {
        $this->sendToRecipients(
            new NewUserRegisteredNotification($registeredUser),
            [$registeredUser]
        );
    }

    protected function sendToRecipients(Notification $notification, array $users = []): void
    {
        $recipients = $this->resolveRecipients($users);

        if ($recipients->isEmpty()) {
            return;
        }

        NotificationFacade::send($recipients, $notification);
    }

    protected function resolveRecipients(array $users = []): Collection
    {
        $roleRecipients = User::query()
            ->select('users.*')
            ->role(self::RECIPIENT_ROLES)
            ->distinct()
            ->get();

        return $roleRecipients
            ->merge(collect($users)->filter(fn ($user) => $user instanceof User))
            ->unique('id')
            ->values();
    }
}
