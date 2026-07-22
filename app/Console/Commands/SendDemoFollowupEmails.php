<?php

namespace App\Console\Commands;

use App\Models\DemoRequest;
use App\Models\MessageTemplate;
use App\Services\TemplateMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDemoFollowupEmails extends Command
{
    protected $signature = 'demos:send-followups';

    protected $description = 'Send the demo follow-up email (Attended / Missed / How-was-your-demo) 1 hour after each demo.';

    /** Nepal is UTC+5:45, no DST. scheduled_at is stored as Nepal wall-clock. */
    private const NEPAL_TZ = 'Asia/Kathmandu';

    public function handle(TemplateMailer $mailer): int
    {
        $demos = DemoRequest::query()
            ->where('status', 'approved')
            ->whereNull('archived_at')
            ->whereNotNull('scheduled_at')
            ->whereNull('outcome_email_sent_at')
            ->whereNotNull('email')
            ->with('attendances:id,demo_request_id,attended_on,status')
            ->get();

        $sent = 0;

        foreach ($demos as $demo) {
            // scheduled_at is a Nepal wall-clock time tagged as UTC — reinterpret it in
            // Nepal so "1 hour after" lands 1 hour after the real class time.
            $classTime = Carbon::parse($demo->scheduled_at->format('Y-m-d H:i:s'), self::NEPAL_TZ);

            if (now()->lt($classTime->copy()->addHour())) {
                continue; // less than an hour since the session — not due yet
            }

            $status = optional($demo->attendances->sortByDesc('attended_on')->first())->status;

            $key = match ($status) {
                'present' => 'demo_attended',
                'absent' => 'demo_missed',
                default => 'demo_followup',
            };

            // Skip (and retry on a later run) if the admin has disabled this template,
            // so we never mark a demo done without actually attempting a send.
            $template = MessageTemplate::byKey($key);
            if (! $template || ! $template->is_enabled || $template->category !== 'email_auto') {
                continue;
            }

            $mailer->send($key, $demo->email, [
                'StudentName' => $demo->name,
                'TestName' => $demo->course_name ?? 'IELTS / PTE',
                'RecordedDemoUrl' => rtrim((string) config('app.frontend_url', 'https://www.ktmtestpreparation.com'), '/').'/demo',
            ], ['user_id' => $demo->user_id, 'related' => ['demo_outcome', $demo->id]]);

            $demo->update(['outcome_email_sent_at' => now()]);
            $sent++;
        }

        $this->info("Demo follow-up emails processed: {$sent}");

        return self::SUCCESS;
    }
}
