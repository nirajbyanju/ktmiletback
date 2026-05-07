<?php

namespace App\Services;

use App\Models\InqueryFollowup;
use App\Models\Property;
use App\Models\User;
use App\Notifications\InquiryFollowupCreatedNotification;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\PropertyCreatedNotification;
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

    public function notifyPropertyCreated(Property $property, ?User $actor = null): void
    {
        $this->sendToRecipients(
            new PropertyCreatedNotification($property, $actor),
            array_filter([$actor])
        );
    }

    public function notifyInquiryFollowupCreated(InqueryFollowup $followup, ?User $actor = null): void
    {
        $this->sendToRecipients(
            new InquiryFollowupCreatedNotification($followup, $actor),
            array_filter([$actor])
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
