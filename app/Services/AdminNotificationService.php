<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\EnrollmentStatusChangedNotification;
use App\Notifications\InvoiceCancelledNotification;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\InvoicePaidNotification;
use App\Notifications\InvoiceRefundedNotification;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\PaymentScreenshotUploadedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class AdminNotificationService
{
    private const RECIPIENT_ROLES = ['Super Admin', 'Admin'];

    // ── Admin-bound events ────────────────────────────────────────────────────

    /** New user registered → notify all admins */
    public function notifyNewRegistration(User $registeredUser): void
    {
        $this->sendToAdmins(new NewUserRegisteredNotification($registeredUser));
    }

    /** Student created a new invoice → notify all admins */
    public function notifyInvoiceCreated(Invoice $invoice, User $student): void
    {
        $this->sendToAdmins(new InvoiceCreatedNotification($invoice, $student));
    }

    /** Student uploaded a payment screenshot → notify all admins */
    public function notifyScreenshotUploaded(Invoice $invoice, User $student): void
    {
        $this->sendToAdmins(new PaymentScreenshotUploadedNotification($invoice, $student));
    }

    /** Student cancelled an invoice → notify all admins */
    public function notifyInvoiceCancelled(Invoice $invoice, User $student): void
    {
        $this->sendToAdmins(new InvoiceCancelledNotification($invoice, $student));
    }

    // ── Student-bound events ──────────────────────────────────────────────────

    /** Admin marked invoice paid → notify the student */
    public function notifyInvoicePaid(Invoice $invoice, User $admin): void
    {
        $student = $invoice->user;
        if (!$student) return;

        $student->notify(new InvoicePaidNotification($invoice, $admin));
    }

    /** Admin processed a refund → notify the student */
    public function notifyInvoiceRefunded(Invoice $invoice, User $admin): void
    {
        $student = $invoice->user;
        if (!$student) return;

        $student->notify(new InvoiceRefundedNotification($invoice, $admin));
    }

    /** Admin changed enrollment crm_status → notify the student */
    public function notifyEnrollmentStatusChanged(
        Enrollment $enrollment,
        string $oldStatus,
        string $newStatus,
        ?User $admin,
    ): void {
        $student = $enrollment->user;
        if (!$student) return;
        if ($oldStatus === $newStatus) return;

        $student->notify(new EnrollmentStatusChangedNotification($enrollment, $oldStatus, $newStatus, $admin));
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function sendToAdmins(Notification $notification): void
    {
        $recipients = User::query()
            ->select('users.*')
            ->role(self::RECIPIENT_ROLES)
            ->distinct()
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        NotificationFacade::send($recipients, $notification);
    }
}
