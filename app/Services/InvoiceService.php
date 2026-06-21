<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use App\Models\MockTestSubscription;
use App\Models\OfferClaim;
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
        ['claim' => $claim, 'discount' => $offerDiscount] = $this->resolveOfferDiscount(
            $user,
            'course',
            $batch->id,
            $data['offer_claim_id'] ?? null
        );

        if ($offerDiscount > 0) {
            $amounts['discount_npr'] = $amounts['discount_npr'] + $offerDiscount;
            $afterDiscount           = max(0.0, $amounts['subtotal_npr'] - $amounts['discount_npr']);
            $amounts['tax_npr']      = round($afterDiscount * 0.13, 2); // recalculate VAT on true after-discount amount
            $amounts['total_npr']    = round($afterDiscount + $amounts['tax_npr'], 2);
        }

        return DB::transaction(function () use ($batch, $user, $data, $amounts, $claim) {
            $invoice = Invoice::where('user_id', $user->id)
                ->where('batch_id', $batch->id)
                ->where('status', Invoice::STATUS_UNPAID)
                ->latest('id')
                ->first();

            if ($invoice) {
                $invoice->update([
                    ...$amounts,
                    'offer_claim_id' => $claim?->id ?? $invoice->offer_claim_id,
                    'payment_method' => $data['payment_method'] ?? $invoice->payment_method ?? 'bank_qr',
                    'notes'          => $data['notes'] ?? $invoice->notes,
                ]);

                return $invoice->fresh(['batch.course:id,course_name', 'user:id,name,first_name,last_name,email,phone']);
            }

            return Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id'        => $user->id,
                'batch_id'       => $batch->id,
                'offer_claim_id' => $claim?->id,
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
        ['claim' => $claim, 'discount' => $offerDiscount] = $this->resolveOfferDiscount(
            $user,
            'mock_test',
            $subscription->id,
            $data['offer_claim_id'] ?? null
        );

        return DB::transaction(function () use ($subscription, $user, $data, $claim, $offerDiscount) {
            $existing = Invoice::where('user_id', $user->id)
                ->where('mock_test_subscription_id', $subscription->id)
                ->where('status', Invoice::STATUS_UNPAID)
                ->latest('id')
                ->first();

            $subtotal = (float) ($subscription->price ?? 0);
            $discount = (float) ($subscription->discount ?? 0) + $offerDiscount;
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
                    'offer_claim_id' => $claim?->id ?? $existing->offer_claim_id,
                    'payment_method' => $data['payment_method'] ?? $existing->payment_method ?? 'bank_qr',
                    'notes'          => $data['notes'] ?? $existing->notes,
                ]);
                return $existing->fresh(['mockTestSubscription', 'user:id,name,first_name,last_name,email,phone']);
            }

            return Invoice::create([
                'invoice_number'            => $this->nextInvoiceNumber(),
                'user_id'                   => $user->id,
                'mock_test_subscription_id' => $subscription->id,
                'offer_claim_id'            => $claim?->id,
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
        ['claim' => $claim, 'discount' => $offerDiscount] = $this->resolveOfferDiscount(
            $user,
            'booking',
            $enrollment->exam_booking_id,
            $data['offer_claim_id'] ?? null
        );

        return DB::transaction(function () use ($enrollment, $user, $data, $claim, $offerDiscount) {
            $existing = Invoice::where('user_id', $user->id)
                ->where('exam_booking_enrollment_id', $enrollment->id)
                ->where('status', Invoice::STATUS_UNPAID)
                ->latest('id')
                ->first();

            $plan     = $enrollment->examBooking;
            $subtotal = (float) ($plan->price ?? 0);
            $discount = (float) ($plan->discount ?? 0) + $offerDiscount;
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
                    'offer_claim_id' => $claim?->id ?? $existing->offer_claim_id,
                    'payment_method' => $data['payment_method'] ?? $existing->payment_method ?? 'bank_qr',
                    'notes'          => $data['notes'] ?? $existing->notes,
                ]);

                // Ensure status reflects that payment is awaited
                if (in_array($enrollment->status, ['new_request', 'document_pending'])) {
                    $enrollment->update(['status' => 'payment_pending']);
                }

                return $existing->fresh(['examBookingEnrollment.examBooking', 'user:id,name,first_name,last_name,email,phone']);
            }

            $invoice = Invoice::create([
                'invoice_number'             => $this->nextInvoiceNumber(),
                'user_id'                    => $user->id,
                'exam_booking_enrollment_id' => $enrollment->id,
                'offer_claim_id'             => $claim?->id,
                ...$amounts,
                'status'         => Invoice::STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? 'bank_qr',
                'invoice_date'   => now()->toDateString(),
                'due_date'       => now()->addDays(3)->toDateString(),
                'notes'          => $data['notes'] ?? null,
            ]);

            // Move enrollment to payment_pending so admin can see user is ready to pay
            if (in_array($enrollment->status, ['new_request', 'document_pending'])) {
                $enrollment->update(['status' => 'payment_pending']);
            }

            return $invoice->load(['examBookingEnrollment.examBooking', 'user:id,name,first_name,last_name,email,phone']);
        });
    }

    // ── Process refund (admin only) ───────────────────────────────────────────

    public function processRefund(Invoice $invoice, User $admin, float $refundAmount, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $admin, $refundAmount, $reason) {
            // 1. Update invoice to refunded state
            $invoice->update([
                'status'              => Invoice::STATUS_REFUNDED,
                'refunded_amount_npr' => $refundAmount,
                'refund_reason'       => $reason,
                'refunded_at'         => now(),
                'refunded_by'         => $admin->id,
            ]);

            $invoice->loadMissing(['batch', 'user', 'enrollment', 'mockTestEnrollment', 'examBookingEnrollment']);

            // 2. Deactivate enrollment based on invoice type
            match ($invoice->type) {
                Invoice::TYPE_COURSE    => $this->deactivateCourseEnrollment($invoice),
                Invoice::TYPE_MOCK_TEST => $this->deactivateMockTestEnrollment($invoice),
                Invoice::TYPE_EXAM      => $this->deactivateExamBookingEnrollment($invoice),
                default                 => null,
            };

            return $invoice->fresh([
                'batch.course:id,course_name',
                'user:id,name,first_name,last_name,email,phone',
                'refundedBy:id,name,first_name,last_name',
            ]);
        });
    }

    // ── Enrollment deactivations (on refund) ──────────────────────────────────

    private function deactivateCourseEnrollment(Invoice $invoice): void
    {
        if (!$invoice->enrollment) return;

        $invoice->enrollment->update([
            'status'         => 'inactive',
            'crm_status'     => 'dropped',
            'payment_status' => 'refunded',
        ]);
    }

    private function deactivateMockTestEnrollment(Invoice $invoice): void
    {
        // Soft-delete the mock test enrollment to revoke access
        $invoice->mockTestEnrollment?->delete();
    }

    private function deactivateExamBookingEnrollment(Invoice $invoice): void
    {
        if (!$invoice->examBookingEnrollment) return;

        $invoice->examBookingEnrollment->update(['status' => 'cancelled']);
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

            // Lock the offer claim so it cannot be reused on another invoice
            if ($invoice->offer_claim_id) {
                OfferClaim::where('id', $invoice->offer_claim_id)
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);
            }

            $invoice->loadMissing(['batch.course', 'batch.teacher', 'user', 'mockTestSubscription', 'examBookingEnrollment.examBooking']);

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
        $teacherName = $invoice->batch?->teacher?->name ?? null;

        Enrollment::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'user_id'         => $invoice->user_id,
                'student_name'    => $invoice->user?->display_name ?? $invoice->user?->email ?? 'Student',
                'batch_id'        => $invoice->batch_id,
                'enrollment_date' => now()->toDateString(),
                'amount_paid'     => $invoice->total_npr,
                'status'          => 'active',
                'crm_status'      => 'active',
                'payment_status'  => 'paid',
                'teacher'         => $teacherName,
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

    // ── Offer claim helpers ───────────────────────────────────────────────────

    /**
     * Auto-find the best applicable offer claim for this user+type+subjectId.
     *
     * Priority order:
     *  1. Specific item match  (applicable_type = $type AND applicable_id = $subjectId)
     *  2. Type-wide match      (applicable_type = $type AND applicable_id IS NULL)
     *  3. Global match         (applicable_type = 'all')
     *
     * If an explicit $offerClaimId is provided, validates ownership/usability instead.
     */
    private function resolveOfferDiscount(User $user, string $type, ?int $subjectId, ?int $offerClaimId = null): array
    {
        if ($offerClaimId) {
            // Manual pick — just validate it
            $claim = OfferClaim::with('offer')
                ->where('id', $offerClaimId)
                ->where('user_id', $user->id)
                ->first();

            if (!$claim || $claim->isUsed() || !$claim->offer?->isClaimable()) {
                return ['claim' => null, 'discount' => 0.0];
            }

            return [
                'claim'    => $claim,
                'discount' => (float) $claim->offer->claim_discount_amount,
            ];
        }

        // Auto-lookup: find the highest-priority applicable claim
        $today = now()->toDateString();

        $claim = OfferClaim::with('offer')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->whereHas('offer', function ($q) use ($type, $subjectId, $today) {
                $q->where('status', 'active')
                  ->where('valid_date', '>=', $today)
                  ->where(function ($q2) use ($type, $subjectId) {
                      // Global offers
                      $q2->where('applicable_type', 'all')
                         // Type-specific (all items of that type)
                         ->orWhere(function ($q3) use ($type) {
                             $q3->where('applicable_type', $type)
                                ->whereNull('applicable_id');
                         })
                         // Exact item match
                         ->orWhere(function ($q3) use ($type, $subjectId) {
                             $q3->where('applicable_type', $type)
                                ->where('applicable_id', $subjectId);
                         });
                  });
            })
            // Most specific first: exact > type-wide > global
            ->orderByRaw("
                CASE
                    WHEN offer_claims.source_type = ? AND offer_claims.source_id = ? THEN 1
                    WHEN offer_claims.source_type = ? AND offer_claims.source_id IS NULL THEN 2
                    ELSE 3
                END
            ", [$type, $subjectId, $type])
            ->first();

        if (!$claim) {
            return ['claim' => null, 'discount' => 0.0];
        }

        return [
            'claim'    => $claim,
            'discount' => (float) $claim->offer->claim_discount_amount,
        ];
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
        $subtotal      = (float) ($batch->price_npr ?? 0);
        $discount      = $this->discountAmount($batch, $subtotal);
        $afterDiscount = max(0.0, $subtotal - $discount);
        $tax           = round($afterDiscount * 0.13, 2); // 13% VAT

        return [
            'subtotal_npr' => $subtotal,
            'discount_npr' => $discount,
            'tax_npr'      => $tax,
            'total_npr'    => round($afterDiscount + $tax, 2),
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
