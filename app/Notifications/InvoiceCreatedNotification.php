<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;

class InvoiceCreatedNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly User $student,
    ) {}

    protected function type(): string
    {
        return 'invoice.created';
    }

    protected function payload(): array
    {
        $label   = $this->invoiceLabel();
        $amount  = 'NPR ' . number_format((float) ($this->invoice->total_npr ?? 0), 2);

        return $this->buildPayload(
            type:        $this->type(),
            title:       'New invoice created',
            message:     "{$this->student->display_name} generated invoice {$this->invoice->invoice_number} for {$label} — {$amount}. Awaiting payment.",
            actionUrl:   '/admin/invoices',
            actionLabel: 'View invoices',
            entity:      ['type' => 'invoice', 'id' => $this->invoice->id],
            actor:       $this->userData($this->student),
            meta:        [
                'invoice_number' => $this->invoice->invoice_number,
                'total_npr'      => $this->invoice->total_npr,
                'invoice_type'   => $this->invoice->type,
            ],
            severity: 'info',
        );
    }

    private function invoiceLabel(): string
    {
        return match ($this->invoice->type) {
            'course'    => $this->invoice->batch?->course?->course_name
                           ?? $this->invoice->batch?->batch_type
                           ?? 'course batch',
            'mock_test' => $this->invoice->mockTestSubscription?->subscriptions_name ?? 'mock test',
            'exam'      => $this->invoice->examBookingEnrollment?->examBooking?->exam_name ?? 'exam booking',
            default     => 'service',
        };
    }
}
