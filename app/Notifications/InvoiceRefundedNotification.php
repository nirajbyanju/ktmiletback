<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;

class InvoiceRefundedNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly User $admin,
    ) {}

    protected function type(): string
    {
        return 'invoice.refunded';
    }

    protected function payload(): array
    {
        $refundAmount = 'NPR ' . number_format((float) ($this->invoice->refunded_amount_npr ?? $this->invoice->total_npr ?? 0), 2);
        $reason       = $this->invoice->refund_reason ?? 'No reason provided';

        return $this->buildPayload(
            type:        $this->type(),
            title:       'Refund processed',
            message:     "A refund of {$refundAmount} has been processed for invoice {$this->invoice->invoice_number}. Reason: {$reason}. Your enrollment has been deactivated.",
            actionUrl:   '/my-account',
            actionLabel: 'View account',
            entity:      ['type' => 'invoice', 'id' => $this->invoice->id],
            actor:       $this->userData($this->admin),
            meta:        [
                'invoice_number'      => $this->invoice->invoice_number,
                'refunded_amount_npr' => $this->invoice->refunded_amount_npr,
                'refund_reason'       => $this->invoice->refund_reason,
            ],
            severity: 'warning',
        );
    }
}
