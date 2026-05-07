<?php

namespace App\Notifications;

use App\Models\Property;
use App\Models\User;

class PropertyCreatedNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly Property $property,
        private readonly ?User $actor = null,
    ) {
    }

    protected function type(): string
    {
        return 'property.created';
    }

    protected function payload(): array
    {
        return $this->buildPayload(
            type: $this->type(),
            title: 'New property added',
            message: "{$this->property->title} was added to the property inventory.",
            actionUrl: '/admin/property',
            actionLabel: 'View properties',
            entity: [
                'type' => 'property',
                'id' => $this->property->id,
                'property_code' => $this->property->property_code,
            ],
            actor: $this->userData($this->actor),
            meta: [
                'title' => $this->property->title,
                'listing_type' => optional($this->property->listingType)->name,
                'property_type' => optional($this->property->propertyType)->name,
                'advertise_price' => $this->property->advertise_price,
                'currency' => $this->property->currency,
            ],
        );
    }
}
