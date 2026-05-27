<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use App\Models\MockTestSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoiceService
{
    // ── Course batch invoice ──────────────────────────────────────────────────

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
                    'notes'          => $data['notes'] ?? $invoice->notes,
                ]);

                return $invoice->fresh(['batch.course:id,course_name', 'user:id,name,first_name,last_name,email,phone']);
            }

            return Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id'        => $user->id,
                'batch_id'       => $batch->id,
                ...$amounts,
                'status'         => Invoice::STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? 'bank_qr',
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(3)->toDateString(),
                'notes'          => $data['notes'] ?? null,
            ])->load(['batch.course:id,course_name', 'user:id,name,first_name,last_name,email,phone']);
        });
    }

    // ── Mock test subscription invoice ────────────────────────────────────────

    public function createForMockTest(MockTestSubscription $subscription, User $user, array $data = []): Invoice
    {
        return DB::transaction(function () use ($subscription, $user, $data) {
            $existing = Invoice::where('user_id', $user->id)
                ->where('mock_test_subscription_id', $subscription->id)
                ->where('status', Invoice::STATUS_UNPAID)
                ->latest('id')
                ->first();

            $subtotal = (float) ($subscription->price ?? 0);
            $discount = (float) ($subscription->discount ?? 0);
            $total    = max(0, $subtotal - $discount);

            $amounts = [
                'subtotal_npr' => $subtotal,
                'discount_npr' => $discount,
                'tax_npr'      => 0,
                'total_npr'    => $total,
            ];

            if ($existing) {
                $existing->update([
                    ...$amounts,
                    'payment_method' => $data['payment_method'] ?? $existing->payment_method ?? 'bank_qr',
                    'notes'          => $data['notes'] ?? $existing->notes,
                ]);
                return $existing->fresh(['mockTestSubscription', 'user:id,name,first_name,last_name,email,phone']);
            }

            return Invoice::create([
                'invoice_number'            => $this->nextInvoiceNumber(),
                'user_id'                   => $user->id,
                'mock_test_subscription_id' => $subscription->id,
                ...$amounts,
                'status'                    => Invoice::STATUS_UNPAID,
                'payment_method'            => $data['payment_method'] ?? 'bank_qr',
                'invoice_date'              => now()->toDateString(),
                'due_date'                  => now()->addDays(3)->toDateString(),
                'notes'                     => $data['notes'] ?? null,
            ])->load(['mockTestSubscription', 'user:id,name,first_name,last_name,email,phone']);
        });
    }

    // ── Exam booking enrollment invoice ──────────────────────────────────────

    public function createForExamBookingEnrollment(ExamBookingEnrollment $enrollment, User $user, array $data = []): Invoice
    {
        return DB::transaction(function () use ($enrollment, $user, $data) {
            $existing = Invoice::where('user_id', $user->id)
                ->where('exam_booking_enrollment_id', $enrollment->id)
                ->where('status', Invoice::STATUS_UNPAID)
                ->latest('id')
                ->first();

            $plan     = $enrollment->examBooking;
            $subtotal = (float) ($plan->price ?? 0);
            $discount = (float) ($plan->discount ?? 0);
            $total    = max(0, $subtotal - $discount);

            $amounts = [
                'subtotal_npr' => $subtotal,
                'discount_npr' => $discount,
                'tax_npr'      => 0,
                'total_npr'    => $total,
            ];

            if ($existing) {
                $existing->update([
                    ...$amounts,
                    'payment_method' => $data['payment_method'] ?? $existing->payment_method ?? 'bank_qr',
                    'notes'          => $data['notes'] ?? $existing->notes,
                ]);
                return $existing->fresh(['examBookingEnrollment.examBooking', 'user:id,name,first_name,last_name,email,phone']);
            }

            return Invoice::create([
                'invoice_number'             => $this->nextInvoiceNumber(),
                'user_id'                    => $user->id,
                'exam_booking_enrollment_id' => $enrollment->id,
                ...$amounts,
                'status'         => Invoice::STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? 'bank_qr',
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(3)->toDateString(),
                'notes'          => $data['notes'] ?? null,
            ])->load(['examBookingEnrollment.examBooking', 'user:id,name,first_name,last_name,email,phone']);
        });
    }

    // ── Mark paid ─────────────────────────────────────────────────────────────

    public function markPaid(Invoice $invoice, User $admin, ?string $notes = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $admin, $notes) {
            $invoice->update([
                'status'      => Invoice::STATUS_PAID,
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'notes'       => $notes ?: $invoice->notes,
            ]);

            $invoice->loadMissing(['batch.course', 'user', 'mockTestSubscription', 'examBookingEnrollment.examBooking']);

            // Activate the right enrollment based on invoice type
            match ($invoice->type) {
                Invoice::TYPE_COURSE    => $this->activateCourseEnrollment($invoice),
                Invoice::TYPE_MOCK_TEST => $this->activateMockTestEnrollment($invoice),
                Invoice::TYPE_EXAM      => $this->activateExamBookingEnrollment($invoice),
                default                 => null,
            };

            $this->sendPaymentConfirmationEmail($invoice);

            return $invoice->fresh(['batch.course', 'user', 'enrollment', 'mockTestEnrollment', 'examBookingEnrollment.examBooking']);
        });
    }

    // ── Enrollment activations ────────────────────────────────────────────────

    private function activateCourseEnrollment(Invoice $invoice): void
    {
        Enrollment::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'user_id'        => $invoice->user_id,
                'student_name'   => $invoice->user?->display_name ?? $invoice->user?->email ?? 'Student',
                'batch_id'       => $invoice->batch_id,
                'enrollment_date' => now()->toDateString(),
                'amount_paid'    => $invoice->total_npr,
                'status'         => 'active',
            ]
        );
    }

    private function activateMockTestEnrollment(Invoice $invoice): void
    {
        $sub = $invoice->mockTestSubscription;
        if (!$sub) return;

        MockTestEnrollment::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'subscription_id'    => $sub->id,
                'user_id'            => $invoice->user_id,
                'enrollment_date'    => now()->toDateString(),
                'subscription_start' => now()->toDateString(),
                'subscription_end'   => now()->addDays($sub->duration ?? 30)->toDateString(),
            ]
        );
    }

    private function activateExamBookingEnrollment(Invoice $invoice): void
    {
        $enrollment = $invoice->examBookingEnrollment;
        if (!$enrollment) return;

        $enrollment->update(['status' => 'booking_in_process']);
    }

    // ── Pricing helpers ───────────────────────────────────────────────────────

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

    private function amountsForBatch(Batch $batch): array
    {
        $subtotal = (float) ($batch->price_npr ?? 0);
        $discount = $this->discountAmount($batch, $subtotal);
        $tax      = 0.0;

        return [
            'subtotal_npr' => $subtotal,
            'discount_npr' => $discount,
            'tax_npr'      => $tax,
            'total_npr'    => max(0, $subtotal - $discount + $tax),
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

    private function sendPaymentConfirmationEmail(Invoice $invoice): void
    {
        if (!$invoice->user?->email) {
            return;
        }

        $subject = match ($invoice->type) {
            Invoice::TYPE_COURSE    => 'Enrollment confirmed: ' . ($invoice->batch?->course?->course_name ?? 'Course'),
            Invoice::TYPE_MOCK_TEST => 'Mock test subscription activated: ' . ($invoice->mockTestSubscription?->course ?? 'Mock Test'),
            Invoice::TYPE_EXAM      => 'Exam booking payment confirmed',
            default                 => 'Payment confirmed',
        };

        $body = match ($invoice->type) {
            Invoice::TYPE_COURSE => sprintf(
                "Your enrollment for %s is confirmed.\n\nBatch: %s\nStart date: %s\nInvoice: %s",
                $invoice->batch?->course?->course_name ?? 'your course',
                $invoice->batch?->batch_type ?? '',
                $invoice->batch?->start_date ?? '',
                $invoice->invoice_number
            ),
            Invoice::TYPE_MOCK_TEST => sprintf(
                "Your mock test subscription for %s has been activated.\n\nPackage: %s\nInvoice: %s",
                $invoice->mockTestSubscription?->course ?? '',
                $invoice->mockTestSubscription?->package ?? '',
                $invoice->invoice_number
            ),
            Invoice::TYPE_EXAM => sprintf(
                "Your exam booking payment has been confirmed.\n\nTest: %s\nPlan: %s\nInvoice: %s",
                $invoice->examBookingEnrollment?->examBooking?->exam_type ?? '',
                $invoice->examBookingEnrollment?->examBooking?->exam_name ?? '',
                $invoice->invoice_number
            ),
            default => "Invoice {$invoice->invoice_number} has been paid.",
        };

        Mail::raw($body, fn ($m) => $m->to($invoice->user->email)->subject($subject));
    }
}
