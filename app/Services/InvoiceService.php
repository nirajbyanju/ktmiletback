<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use App\Models\MockTestSubscription;
use App\Models\OfferClaim;
use App\Models\Setting;
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
            $afterDiscount = max(0.0, $amounts['subtotal_npr'] - $amounts['discount_npr']);
            $amounts['tax_npr'] = $this->vatBreakdown($afterDiscount, 'course')['tax_npr'];
            $amounts['total_npr'] = $afterDiscount; // total stays the same (VAT already inside)
        }

        return DB::transaction(function () use ($batch, $user, $data, $amounts, $claim) {
            $invoice = Invoice::where('user_id', $user->id)
                ->where('batch_id', $batch->id)
                ->where('status', Invoice::STATUS_UNPAID)
                // Skip invoices whose booking was archived — create a fresh one
                ->whereDoesntHave('enrollment', fn ($q) => $q->whereNotNull('archived_at'))
                ->latest('id')
                ->first();

            if ($invoice) {
                $invoice->update([
                    ...$amounts,
                    'offer_claim_id' => $claim?->id ?? $invoice->offer_claim_id,
                    'payment_method' => $data['payment_method'] ?? $invoice->payment_method ?? 'bank_qr',
                    'notes' => $data['notes'] ?? $invoice->notes,
                ]);
            } else {
                $invoice = Invoice::create([
                    'invoice_number' => $this->nextInvoiceNumber(),
                    'user_id' => $user->id,
                    'batch_id' => $batch->id,
                    'offer_claim_id' => $claim?->id,
                    ...$amounts,
                    'status' => Invoice::STATUS_UNPAID,
                    'payment_method' => $data['payment_method'] ?? 'bank_qr',
                    'invoice_date' => now()->toDateString(),
                    'due_date' => now()->addDays(3)->toDateString(),
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            // Create a pending enrollment record so admin can see unpaid students immediately.
            // activateCourseEnrollment() will upgrade this record (updateOrCreate by invoice_id) when paid.
            $leader = Enrollment::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'user_id' => $user->id,
                    'student_name' => $user->display_name ?? $user->name ?? $user->email ?? 'Student',
                    'batch_id' => $batch->id,
                    'status' => 'inactive',
                    'crm_status' => 'prospect',
                    'payment_status' => 'action_required',
                    'amount_paid' => 0,
                    'preferred_schedule' => $data['preferred_schedule'] ?? null,
                ]
            );

            // Friends Private Group: sync member records under the leader.
            if (array_key_exists('group_members', $data) && is_array($data['group_members'])) {
                $this->syncGroupMembers($leader, $batch, $data['group_members']);
            }

            return $invoice->fresh(['batch.course:id,course_name', 'user:id,name,first_name,last_name,email,phone']);
        });
    }

    /**
     * Friends Private Group: each member becomes a CRM-visible enrollment
     * (Fee Waived — the leader's payment covers the group) linked to the
     * leader. Members with a matching account are linked; the rest receive
     * an email invitation to register with that address.
     */
    public function syncGroupMembers(Enrollment $leader, Batch $batch, array $members): void
    {
        // Pre-payment sync: replace the previous member list entirely
        Enrollment::where('parent_enrollment_id', $leader->id)->forceDelete();

        $mailer = app(TemplateMailer::class);

        foreach (array_slice($members, 0, 10) as $m) {
            $name = trim((string) ($m['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $email = isset($m['email']) && $m['email'] ? strtolower(trim($m['email'])) : null;
            $memberUser = $email ? User::where('email', $email)->first() : null;

            $member = Enrollment::create([
                'parent_enrollment_id' => $leader->id,
                'user_id' => $memberUser?->id,
                'student_name' => $name,
                'email' => $email,
                'phone' => $m['phone'] ?? null,
                'batch_id' => $batch->id,
                'status' => 'inactive',
                'crm_status' => 'prospect',
                'payment_status' => 'fee_waived',
                'amount_paid' => 0,
                'notes' => 'Group member of enrollment #'.$leader->id,
            ]);

            if ($email) {
                $mailer->send('group_member_added', $email, [
                    'StudentName' => $name,
                    'FirstName' => explode(' ', $name)[0],
                    'LeaderName' => $leader->student_name,
                    'CourseName' => $batch->course?->course_name ?? 'your course',
                    'MemberEmail' => $email,
                ], ['user_id' => $memberUser?->id, 'related' => ['group_member', $member->id]]);
            }
        }
    }

    /**
     * Re-point an existing UNPAID course invoice at a different batch,
     * recomputing the amounts (used when a student changes batch before paying).
     */
    public function retargetBatchInvoice(Invoice $invoice, Batch $batch): Invoice
    {
        $invoice->update([
            'batch_id' => $batch->id,
            ...$this->amountsForBatch($batch),
        ]);

        return $invoice->fresh(['batch.course:id,course_name']);
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
            $total = max(0, $subtotal - $discount);

            $amounts = [
                'subtotal_npr' => $subtotal,
                'discount_npr' => $discount,
                'tax_npr' => $this->vatBreakdown($total, 'mock_test')['tax_npr'],
                'total_npr' => $total,
            ];

            if ($existing) {
                $existing->update([
                    ...$amounts,
                    'offer_claim_id' => $claim?->id ?? $existing->offer_claim_id,
                    'payment_method' => $data['payment_method'] ?? $existing->payment_method ?? 'bank_qr',
                    'notes' => $data['notes'] ?? $existing->notes,
                ]);
                $this->ensurePendingMockEnrollment($existing, $user, $subscription);

                return $existing->fresh(['mockTestSubscription', 'user:id,name,first_name,last_name,email,phone']);
            }

            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id' => $user->id,
                'mock_test_subscription_id' => $subscription->id,
                'offer_claim_id' => $claim?->id,
                ...$amounts,
                'status' => Invoice::STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? 'bank_qr',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(3)->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->ensurePendingMockEnrollment($invoice, $user, $subscription);

            return $invoice->load(['mockTestSubscription', 'user:id,name,first_name,last_name,email,phone']);
        });
    }

    /**
     * Pending mock-test enrollment so admins see subscribers before payment.
     * activateMockTestEnrollment() upgrades it (sets dates) when paid.
     */
    private function ensurePendingMockEnrollment(Invoice $invoice, User $user, MockTestSubscription $subscription): void
    {
        MockTestEnrollment::firstOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'enrollment_date' => now()->toDateString(),
            ]
        );
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

            $plan = $enrollment->examBooking;
            $subtotal = (float) ($plan->price ?? 0);
            $discount = (float) ($plan->discount ?? 0) + $offerDiscount;
            $total = max(0, $subtotal - $discount);

            $amounts = [
                'subtotal_npr' => $subtotal,
                'discount_npr' => $discount,
                'tax_npr' => $this->vatBreakdown($total, 'exam_booking')['tax_npr'],
                'total_npr' => $total,
            ];

            if ($existing) {
                $existing->update([
                    ...$amounts,
                    'offer_claim_id' => $claim?->id ?? $existing->offer_claim_id,
                    'payment_method' => $data['payment_method'] ?? $existing->payment_method ?? 'bank_qr',
                    'notes' => $data['notes'] ?? $existing->notes,
                ]);

                // Ensure status reflects that payment is awaited
                if (in_array($enrollment->status, ['new_request', 'document_pending'])) {
                    $enrollment->update(['status' => 'payment_pending']);
                }

                return $existing->fresh(['examBookingEnrollment.examBooking', 'user:id,name,first_name,last_name,email,phone']);
            }

            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id' => $user->id,
                'exam_booking_enrollment_id' => $enrollment->id,
                'offer_claim_id' => $claim?->id,
                ...$amounts,
                'status' => Invoice::STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? 'bank_qr',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(3)->toDateString(),
                'notes' => $data['notes'] ?? null,
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
                'status' => Invoice::STATUS_REFUNDED,
                'crm_payment_status' => 'refund_completed',
                'refunded_amount_npr' => $refundAmount,
                'refund_reason' => $reason,
                'refunded_at' => now(),
                'refunded_by' => $admin->id,
            ]);

            $invoice->loadMissing(['batch', 'user', 'enrollment', 'mockTestEnrollment', 'examBookingEnrollment']);

            // 2. Deactivate enrollment based on invoice type
            match ($invoice->type) {
                Invoice::TYPE_COURSE => $this->deactivateCourseEnrollment($invoice),
                Invoice::TYPE_MOCK_TEST => $this->deactivateMockTestEnrollment($invoice),
                Invoice::TYPE_EXAM => $this->deactivateExamBookingEnrollment($invoice),
                default => null,
            };

            app(TemplateMailer::class)->sendToUser('refund_approved', $invoice->user, [
                'ItemName' => $invoice->batch?->course?->course_name
                    ? trim($invoice->batch->course->course_name.' — '.($invoice->batch->batch_type ?? ''))
                    : 'your booking',
                'RefundAmount' => 'NPR '.number_format($refundAmount),
                'RefundReason' => $reason,
            ], ['related' => ['invoice_refunded', $invoice->id]]);

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
        if (! $invoice->enrollment) {
            return;
        }

        $invoice->enrollment->update([
            'status' => 'inactive',
            'crm_status' => 'dropped',
            'payment_status' => 'refund_completed',
        ]);
    }

    private function deactivateMockTestEnrollment(Invoice $invoice): void
    {
        // Soft-delete the mock test enrollment to revoke access
        $invoice->mockTestEnrollment?->delete();
    }

    private function deactivateExamBookingEnrollment(Invoice $invoice): void
    {
        if (! $invoice->examBookingEnrollment) {
            return;
        }

        $invoice->examBookingEnrollment->update(['status' => 'cancelled']);
    }

    // ── Mark paid ─────────────────────────────────────────────────────────────

    public function markPaid(Invoice $invoice, User $admin, ?string $notes = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $admin, $notes) {
            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'crm_payment_status' => 'confirmed',
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'notes' => $notes ?: $invoice->notes,
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
                Invoice::TYPE_COURSE => $this->activateCourseEnrollment($invoice),
                Invoice::TYPE_MOCK_TEST => $this->activateMockTestEnrollment($invoice),
                Invoice::TYPE_EXAM => $this->activateExamBookingEnrollment($invoice),
                default => null,
            };

            $this->sendTemplateConfirmationEmails($invoice);

            // Refer-a-friend: if this payer was referred, reward their referrer.
            app(ReferralService::class)->rewardReferrerIfQualifying($invoice);

            return $invoice->fresh(['batch.course', 'user', 'mockTestSubscription', 'enrollment', 'mockTestEnrollment', 'examBookingEnrollment.examBooking']);
        });
    }

    // ── Enrollment activations ────────────────────────────────────────────────

    private function activateCourseEnrollment(Invoice $invoice): void
    {
        $teacherName = $invoice->batch?->teacher?->name ?? null;
        $courseId = $invoice->batch?->course_id;

        // Each student gets their OWN course period. Elite Private / Friends
        // Private Group batches carry explicit dates (created per request) —
        // copy those; otherwise start today and add the package duration.
        $batch = $invoice->batch;
        $course = $batch?->course;
        $startDate = $batch?->start_date ?: now()->toDateString();
        $endDate = $batch?->end_date;

        if (! $endDate) {
            // Package durations differ (e.g. Elite Private is 4 weeks, others 6):
            // the package's duration_weeks is authoritative; "N weeks" in the
            // batch schedule notes is the fallback, then course duration.
            $weeks = $batch?->package?->duration_weeks;
            if (! $weeks && $batch?->schedule_notes && preg_match('/(\d+)\s*(?:weeks?|wks?)/i', $batch->schedule_notes, $m)) {
                $weeks = (int) $m[1];
            }

            if ($weeks) {
                $endDate = now()->parse($startDate)->addWeeks($weeks)->toDateString();
            } elseif ($course?->duration) {
                $endDate = match ($course->duration_type) {
                    'weeks', 'week' => now()->parse($startDate)->addWeeks((int) $course->duration)->toDateString(),
                    'months', 'month' => now()->parse($startDate)->addMonths((int) $course->duration)->toDateString(),
                    'days', 'day' => now()->parse($startDate)->addDays((int) $course->duration)->toDateString(),
                    default => null,
                };
            }
        }

        // Safety guard: skip if the student already has a non-expired, active enrollment
        // in the same course (e.g. admin manually marks a duplicate invoice as paid).
        if ($courseId) {
            $conflict = Enrollment::where('user_id', $invoice->user_id)
                ->where('invoice_id', '!=', $invoice->id)
                ->whereNull('archived_at')
                ->whereNotIn('crm_status', ['completed', 'dropped'])
                ->whereHas('batch', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId)
                        ->where(function ($q2) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', now()->toDateString());
                        });
                })
                ->exists();

            if ($conflict) {
                // Do not create a second active enrollment; leave a note on the invoice.
                $invoice->update(['notes' => trim(($invoice->notes ?? '')."\n[System] Enrollment skipped: student already has an active enrollment in this course.")]);

                return;
            }
        }

        $leader = Enrollment::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'user_id' => $invoice->user_id,
                'student_name' => $invoice->user?->display_name ?? $invoice->user?->email ?? 'Student',
                'batch_id' => $invoice->batch_id,
                'enrollment_date' => now()->toDateString(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'amount_paid' => $invoice->total_npr,
                'status' => 'active',
                'crm_status' => 'active',
                'payment_status' => 'confirmed',
                'teacher' => $teacherName,
            ]
        );

        // Group booking: members activate together with the leader
        Enrollment::where('parent_enrollment_id', $leader->id)->update([
            'status' => 'active',
            'crm_status' => 'active',
            'enrollment_date' => now()->toDateString(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'teacher' => $teacherName,
        ]);
    }

    private function activateMockTestEnrollment(Invoice $invoice): void
    {
        $sub = $invoice->mockTestSubscription;
        if (! $sub) {
            return;
        }

        MockTestEnrollment::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'subscription_id' => $sub->id,
                'user_id' => $invoice->user_id,
                'enrollment_date' => now()->toDateString(),
                'subscription_start' => now()->toDateString(),
                'subscription_end' => now()->addDays($sub->duration ?? 30)->toDateString(),
            ]
        );
    }

    private function activateExamBookingEnrollment(Invoice $invoice): void
    {
        $enrollment = $invoice->examBookingEnrollment;
        if (! $enrollment) {
            return;
        }

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

            if (! $claim || $claim->isUsed() || ! $claim->offer?->isClaimable()) {
                return ['claim' => null, 'discount' => 0.0];
            }

            return [
                'claim' => $claim,
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
            ->orderByRaw('
                CASE
                    WHEN offer_claims.source_type = ? AND offer_claims.source_id = ? THEN 1
                    WHEN offer_claims.source_type = ? AND offer_claims.source_id IS NULL THEN 2
                    ELSE 3
                END
            ', [$type, $subjectId, $type])
            ->first();

        if (! $claim) {
            return ['claim' => null, 'discount' => 0.0];
        }

        return [
            'claim' => $claim,
            'discount' => (float) $claim->offer->claim_discount_amount,
        ];
    }

    // ── Pricing helpers ───────────────────────────────────────────────────────

    private function discountAmount(Batch $batch, float $subtotal): float
    {
        if (! $this->offerIsActive($batch) || $subtotal <= 0) {
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
     * VAT breakdown per admin Billing settings. Totals are VAT-INCLUSIVE:
     * Total = price − discount; taxable = Total × 100/(100+rate); VAT = remainder.
     */
    private function vatBreakdown(float $totalInclusive, string $productKey): array
    {
        $apply = Setting::getBool("vat_apply_{$productKey}", $productKey !== 'exam_booking');
        $rate = Setting::getFloat('vat_rate', 13);

        if (! $apply || $rate <= 0 || $totalInclusive <= 0) {
            return ['tax_npr' => 0.0];
        }

        $taxable = round($totalInclusive * 100 / (100 + $rate), 2);

        return ['tax_npr' => round($totalInclusive - $taxable, 2)];
    }

    private function amountsForBatch(Batch $batch): array
    {
        // Price lives on the package (per course type); batch price is legacy fallback
        $subtotal = (float) ($batch->package?->price_npr ?? $batch->price_npr ?? 0);
        $discount = $this->discountAmount($batch, $subtotal);
        $afterDiscount = max(0.0, $subtotal - $discount);

        return [
            'subtotal_npr' => $subtotal,
            'discount_npr' => $discount,
            'tax_npr' => $this->vatBreakdown($afterDiscount, 'course')['tax_npr'],
            'total_npr' => $afterDiscount, // VAT already included — do NOT add again
        ];
    }

    private function offerIsActive(Batch $batch): bool
    {
        if (! $batch->offer_label || ! $batch->discount_type || ! $batch->discount_value) {
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
            $number = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }

    /** Branded template emails on payment verification (course + mock test). */
    private function sendTemplateConfirmationEmails(Invoice $invoice): void
    {
        $mailer = app(TemplateMailer::class);
        $user = $invoice->user;

        if ($invoice->type === Invoice::TYPE_COURSE) {
            $enrollment = $invoice->enrollment()->first();
            $batch = $invoice->batch;
            $zoomId = null;
            if ($batch?->class_link && preg_match('/zoom\.us\/j\/(\d+)/i', $batch->class_link, $m)) {
                $zoomId = trim(chunk_split($m[1], 3, ' '));
            }

            $mailer->sendToUser('payment_verified_course', $user, [
                'PaymentAmount' => 'NPR '.number_format((float) $invoice->total_npr),
                'CourseName' => $batch?->course?->course_name ?? 'your course',
                'PlanName' => $batch?->batch_type ?? '',
                'StartDate' => optional($enrollment?->start_date)->format('j M Y'),
                'EndDate' => optional($enrollment?->end_date)->format('j M Y'),
                'ClassDays' => $batch?->schedule_notes,
                'ClassTime' => $batch?->class_time ? substr($batch->class_time, 0, 5).' (NPT)' : null,
                'TeacherName' => $batch?->teacher?->name,
                'ZoomMeetingID' => $zoomId,
                ...$this->receiptData($invoice, trim(($batch?->course?->course_name ?? 'Course').' — '.($batch?->batch_type ?? ''), ' —')),
            ], ['related' => ['invoice_paid', $invoice->id]]);

            return;
        }

        if ($invoice->type === Invoice::TYPE_MOCK_TEST) {
            $mockEnrollment = $invoice->mockTestEnrollment;
            $mailer->sendToUser('mock_subscription_confirmed', $user, [
                'PlanName' => $invoice->mockTestSubscription?->subscriptions_name ?? 'Mock Test',
                'PaymentAmount' => 'NPR '.number_format((float) $invoice->total_npr),
                'SubscriptionStart' => optional($mockEnrollment?->subscription_start)?->format('j M Y') ?? ($mockEnrollment->subscription_start ?? null),
                'SubscriptionEnd' => optional($mockEnrollment?->subscription_end)?->format('j M Y') ?? ($mockEnrollment->subscription_end ?? null),
                ...$this->receiptData($invoice, 'Mock Test Subscription — '.($invoice->mockTestSubscription?->subscriptions_name ?? 'Mock Test')),
            ], ['related' => ['invoice_paid', $invoice->id]]);

            return;
        }

        if ($invoice->type === Invoice::TYPE_EXAM) {
            $exam = $invoice->examBookingEnrollment?->examBooking;
            $testName = trim(($exam?->exam_name ?? '').' '.($exam?->exam_type ?? '')) ?: 'your exam';
            $mailer->sendToUser('exam_payment_confirmed', $user, [
                'TestName' => $testName,
                ...$this->receiptData($invoice, 'Exam Booking Support — '.$testName),
            ], ['related' => ['invoice_paid', $invoice->id]]);

            return;
        }

        $this->sendPaymentConfirmationEmail($invoice);
    }

    /**
     * Receipt placeholders for the payment-confirmed emails. Empty values
     * (e.g. no discount) make TemplateMailer drop that line entirely.
     */
    private function receiptData(Invoice $invoice, string $itemName): array
    {
        $money = fn ($v) => 'NPR '.number_format((float) $v, 2);
        $discount = (float) $invoice->discount_npr;
        $vat = (float) $invoice->tax_npr;

        return [
            'InvoiceNumber' => $invoice->invoice_number,
            'InvoiceDate' => optional($invoice->invoice_date)->format('j M Y') ?? (string) $invoice->invoice_date,
            'ItemName' => $itemName,
            'Subtotal' => $money($invoice->subtotal_npr),
            'Discount' => $discount > 0 ? '- '.$money($discount) : '',
            'VatAmount' => $vat > 0 ? $money($vat) : '',
            'TotalPaid' => $money($invoice->total_npr),
            'PaymentMethod' => $invoice->payment_method === 'bank_qr'
                ? 'Siddhartha Bank QR / Bank Transfer'
                : ucwords(str_replace('_', ' ', (string) $invoice->payment_method)),
        ];
    }

    private function sendPaymentConfirmationEmail(Invoice $invoice): void
    {
        if (! $invoice->user?->email) {
            return;
        }

        $subject = match ($invoice->type) {
            Invoice::TYPE_COURSE => 'Enrollment confirmed: '.($invoice->batch?->course?->course_name ?? 'Course'),
            Invoice::TYPE_MOCK_TEST => 'Mock test subscription activated: '.($invoice->mockTestSubscription?->course ?? 'Mock Test'),
            Invoice::TYPE_EXAM => 'Exam booking payment confirmed',
            default => 'Payment confirmed',
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
