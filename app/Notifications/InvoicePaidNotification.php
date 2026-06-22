<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;

class InvoicePaidNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly User $admin,
    ) {}

    protected function type(): string
    {
        return 'invoice.paid';
    }

    protected function payload(): array
    {
        $label  = $this->enrollmentLabel();
        $amount = 'NPR ' . number_format((float) ($this->invoice->total_npr ?? 0), 2);

        return $this->buildPayload(
            type:        $this->type(),
            title:       'Payment verified — enrollment activated',
            message:     "Your payment of {$amount} for {$label} has been verified. Your enrollment is now active. Check your dashboard for class details.",
            actionUrl:   '/my-account',
            actionLabel: 'View dashboard',
            entity:      ['type' => 'invoice', 'id' => $this->invoice->id],
            actor:       $this->userData($this->admin),
            meta:        [
                'invoice_number' => $this->invoice->invoice_number,
                'total_npr'      => $this->invoice->total_npr,
                'invoice_type'   => $this->invoice->type,
            ],
            severity: 'success',
        );
    }

    private function enrollmentLabel(): string
    {
        return match ($this->invoice->type) {
            'course'    => $this->invoice->batch?->course?->course_name
                           ?? $this->invoice->batch?->batch_type
                           ?? 'your course',
            'mock_test' => $this->invoice->mockTestSubscription?->subscriptions_name ?? 'mock test subscription',
            'exam'      => $this->invoice->examBookingEnrollment?->examBooking?->exam_name ?? 'exam booking',
            default     => 'your service',
        };
    }
}
