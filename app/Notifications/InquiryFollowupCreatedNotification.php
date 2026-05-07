<?php

namespace App\Notifications;

use App\Models\InqueryFollowup;
use App\Models\User;

class InquiryFollowupCreatedNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly InqueryFollowup $followup,
        private readonly ?User $actor = null,
    ) {
    }

    protected function type(): string
    {
        return 'inquiry.followup.created';
    }

    protected function payload(): array
    {
        $inquiry = $this->followup->inquiry;
        $property = $inquiry?->property;

        return $this->buildPayload(
            type: $this->type(),
            title: 'New inquiry followup',
            message: "A followup was added for inquiry #{$this->followup->inquiry_id}.",
            actionUrl: '/admin/propertyInquery',
            actionLabel: 'View inquiries',
            entity: [
                'type' => 'inquiry_followup',
                'id' => $this->followup->id,
                'inquiry_id' => $this->followup->inquiry_id,
            ],
            actor: $this->userData($this->actor),
            meta: [
                'property_id' => $property?->id,
                'property_title' => $property?->title,
                'customer_name' => $inquiry?->name,
                'followup_status' => optional($this->followup->followupStatus)->name,
                'message' => $this->followup->message,
                'next_followup_date' => optional($this->followup->next_followup_date)->toISOString(),
            ],
        );
    }
}
