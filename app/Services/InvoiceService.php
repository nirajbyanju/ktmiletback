<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoiceService
{
    public function createForBatch(Batch $batch, User $user, array $data = []): Invoice
    {
        $amounts = $this->amountsForBatch($batch);

        return DB::transaction(function () use ($batch, $user, $data, $amounts) {
            $invoice = Invoice::where('user_id', $user->id)
                ->where('batch_id', $batch->id)
                ->where('status', Invoice::STATUS_UNPAID)
                ->latest('id')
                ->first();

            if ($invoice) {
                $invoice->update([
                    ...$amounts,
                    'payment_method' => $data['payment_method'] ?? $invoice->payment_method ?? 'bank_qr',
                    'notes' => $data['notes'] ?? $invoice->notes,
                ]);

                return $invoice->fresh(['batch.course:id,name', 'user:id,name,first_name,last_name,email,phone']);
            }

            return Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id' => $user->id,
                'batch_id' => $batch->id,
                ...$amounts,
                'status' => Invoice::STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? 'bank_qr',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(3)->toDateString(),
                'notes' => $data['notes'] ?? null,
            ])->load(['batch.course:id,name', 'user:id,name,first_name,last_name,email,phone']);
        });
    }

    public function markPaid(Invoice $invoice, User $admin, ?string $notes = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $admin, $notes) {
            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'notes' => $notes ?: $invoice->notes,
            ]);

            $invoice->loadMissing(['batch.course', 'user']);

            Enrollment::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'user_id' => $invoice->user_id,
                    'student_name' => $invoice->user?->display_name ?? $invoice->user?->email ?? 'Student',
                    'batch_id' => $invoice->batch_id,
                    'enrollment_date' => now()->toDateString(),
                    'amount_paid' => $invoice->total_npr,
                    'status' => 'active',
                ]
            );

            $this->sendEnrollmentEmail($invoice);

            return $invoice->fresh(['batch.course', 'user', 'enrollment']);
        });
    }

    private function discountAmount(Batch $batch, float $subtotal): float
    {
        if (!$this->offerIsActive($batch) || $subtotal <= 0) {
            return 0.0;
        }

        $value = (float) ($batch->discount_value ?? 0);

        if ($value <= 0) {
            return 0.0;
        }

        return $batch->discount_type === 'percent'
            ? round($subtotal * min($value, 100) / 100, 2)
            : min($subtotal, $value);
    }

    /**
     * Invoice totals are always generated from saved batch data so the frontend
     * cannot override price, discount, or tax amounts.
     */
    private function amountsForBatch(Batch $batch): array
    {
        $subtotal = (float) ($batch->price_npr ?? 0);
        $discount = $this->discountAmount($batch, $subtotal);
        $tax = 0.0;

        return [
            'subtotal_npr' => $subtotal,
            'discount_npr' => $discount,
            'tax_npr' => $tax,
            'total_npr' => max(0, $subtotal - $discount + $tax),
        ];
    }

    private function offerIsActive(Batch $batch): bool
    {
        if (!$batch->offer_label || !$batch->discount_type || !$batch->discount_value) {
            return false;
        }

        $today = now()->toDateString();

        if ($batch->offer_starts_at && $batch->offer_starts_at->toDateString() > $today) {
            return false;
        }

        if ($batch->offer_ends_at && $batch->offer_ends_at->toDateString() < $today) {
            return false;
        }

        return true;
    }

    private function nextInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }

    private function sendEnrollmentEmail(Invoice $invoice): void
    {
        if (!$invoice->user?->email) {
            return;
        }

        $batch = $invoice->batch;
        $courseName = $batch?->course?->name ?? 'your course';
        $classTime = $batch?->class_time ? substr((string) $batch->class_time, 0, 5) : 'to be confirmed';
        $classLink = $batch?->class_link ?: 'The admin team will share the class link shortly.';

        Mail::raw(
            "Your enrollment for {$courseName} is confirmed.\n\nBatch: {$batch?->batch_type}\nStart date: {$batch?->start_date}\nClass time: {$classTime}\nClass link: {$classLink}\n\nInvoice: {$invoice->invoice_number}",
            fn ($message) => $message
                ->to($invoice->user->email)
                ->subject("Enrollment confirmed: {$courseName}")
        );
    }
}
