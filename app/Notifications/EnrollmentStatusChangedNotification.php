<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentStatusChangedNotification extends BaseRealtimeNotification
{
    public function __construct(
        private readonly Enrollment $enrollment,
        private readonly string $oldStatus,
        private readonly string $newStatus,
        private readonly ?User $admin,
    ) {}

    protected function type(): string
    {
        return 'enrollment.status_changed';
    }

    protected function payload(): array
    {
        $courseName = $this->enrollment->batch?->course?->course_name
                   ?? $this->enrollment->batch?->batch_type
                   ?? 'your course';

        $message = $this->buildMessage($courseName);

        return $this->buildPayload(
            type:        $this->type(),
            title:       'Enrollment status updated',
            message:     $message,
            actionUrl:   '/my-account',
            actionLabel: 'View dashboard',
            entity:      ['type' => 'enrollment', 'id' => $this->enrollment->id],
            actor:       $this->userData($this->admin),
            meta:        [
                'old_status'  => $this->oldStatus,
                'new_status'  => $this->newStatus,
                'course_name' => $courseName,
                'batch_type'  => $this->enrollment->batch?->batch_type,
            ],
            severity: $this->severityFor($this->newStatus),
        );
    }

    private function buildMessage(string $courseName): string
    {
        return match ($this->newStatus) {
            'active'    => "Your enrollment for {$courseName} is now active. Welcome to class!",
            'completed' => "Congratulations! Your {$courseName} enrollment has been marked as completed.",
            'dropped'   => "Your enrollment for {$courseName} has been marked as dropped. Contact admin for support.",
            'on_hold'   => "Your enrollment for {$courseName} is temporarily on hold. Please contact admin.",
            'inactive'  => "Your enrollment for {$courseName} has been deactivated.",
            default     => "Your enrollment status for {$courseName} has been updated to '{$this->newStatus}'.",
        };
    }

    private function severityFor(string $status): string
    {
        return match ($status) {
            'active', 'completed' => 'success',
            'dropped', 'inactive' => 'warning',
            default               => 'info',
        };
    }
}
