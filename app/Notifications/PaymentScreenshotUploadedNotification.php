<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;

class PaymentScreenshotUploadedNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly User $student,
    ) {}

    protected function type(): string
    {
        return 'invoice.screenshot_uploaded';
    }

    protected function payload(): array
    {
        $amount = 'NPR ' . number_format((float) ($this->invoice->total_npr ?? 0), 2);

        return $this->buildPayload(
            type:        $this->type(),
            title:       'Payment screenshot uploaded',
            message:     "{$this->student->display_name} uploaded a payment screenshot for invoice {$this->invoice->invoice_number} ({$amount}). Please verify and mark as paid.",
            actionUrl:   '/admin/invoices',
            actionLabel: 'Verify payment',
            entity:      ['type' => 'invoice', 'id' => $this->invoice->id],
            actor:       $this->userData($this->student),
            meta:        [
                'invoice_number' => $this->invoice->invoice_number,
                'total_npr'      => $this->invoice->total_npr,
            ],
            severity: 'warning',
        );
    }
}
