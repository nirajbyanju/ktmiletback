<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;

class InvoiceCancelledNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly User $student,
    ) {}

    protected function type(): string
    {
        return 'invoice.cancelled';
    }

    protected function payload(): array
    {
        $amount = 'NPR ' . number_format((float) ($this->invoice->total_npr ?? 0), 2);

        return $this->buildPayload(
            type:        $this->type(),
            title:       'Invoice cancelled',
            message:     "{$this->student->display_name} cancelled invoice {$this->invoice->invoice_number} ({$amount}).",
            actionUrl:   '/admin/invoices',
            actionLabel: 'View invoices',
            entity:      ['type' => 'invoice', 'id' => $this->invoice->id],
            actor:       $this->userData($this->student),
            meta:        [
                'invoice_number' => $this->invoice->invoice_number,
                'total_npr'      => $this->invoice->total_npr,
            ],
            severity: 'info',
        );
    }
}
