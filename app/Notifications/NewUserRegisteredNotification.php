<?php

namespace App\Notifications;

use App\Models\User;

class NewUserRegisteredNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly User $registeredUser,
    ) {
    }

    protected function type(): string
    {
        return 'user.registered';
    }

    protected function payload(): array
    {
        return $this->buildPayload(
            type: $this->type(),
            title: 'New registration',
            message: "{$this->registeredUser->display_name} registered with {$this->registeredUser->email}.",
            actionUrl: '/admin/rbac',
            actionLabel: 'View users',
            entity: [
                'type' => 'user',
                'id' => $this->registeredUser->id,
            ],
            actor: $this->userData($this->registeredUser),
            meta: [
                'roles' => $this->registeredUser->getRoleNames()->values()->all(),
                'phone' => $this->registeredUser->phone,
            ],
        );
    }
}
