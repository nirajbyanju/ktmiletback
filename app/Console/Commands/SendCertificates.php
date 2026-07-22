<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Services\CertificateGenerator;
use App\Services\TemplateMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SendCertificates extends Command
{
    protected $signature = 'certificates:send
        {--dry : List who would receive a certificate without sending anything}
        {--enrollment= : Force-send for one enrollment id (ignores the 3-day wait, still needs 80% or manual eligibility)}';

    protected $description = 'Email the attendance certificate 3 days after a course ends to students with 80%+ attendance.';

    /** A course must have finished at least this many days ago before we send. */
    private const DAYS_AFTER_COMPLETION = 3;

    /** Only courses ending on/after this recorded launch date earn an automatic certificate. */
    private const LAUNCH_DATE_SETTING = 'certificate_automation_start_date';

    public function handle(TemplateMailer $mailer, CertificateGenerator $generator): int
    {
        $template = MessageTemplate::byKey('certificate_ready');
        if (! $this->option('dry') && (! $template || ! $template->is_enabled || $template->category !== 'email_auto')) {
            $this->warn('The "certificate_ready" email template is disabled — nothing sent. Enable it in the admin communications page.');

            return self::SUCCESS;
        }

        // Establish the launch date on the very first real run so students who
        // already finished long ago are never emailed a back-dated certificate.
        $launchDate = Setting::get(self::LAUNCH_DATE_SETTING);
        if (! $launchDate && ! $this->option('dry')) {
            $launchDate = now()->toDateString();
            Setting::set(self::LAUNCH_DATE_SETTING, $launchDate);
            $this->info("First run — recorded {$launchDate} as the certificate start date. Only courses ending on/after today will receive automatic certificates.");
        }

        $sent = 0;

        foreach ($this->eligibleEnrollments($launchDate) as $enrollment) {
            $user = $enrollment->user;
            $data = $generator->data($enrollment);

            if ($this->option('dry')) {
                $this->line(sprintf('#%d  %s  ·  %s  ·  attendance %s', $enrollment->id, $data['name'], $data['course'], $this->attendanceLabel($enrollment)));

                continue;
            }

            try {
                $pdf = $generator->pdf($enrollment);
            } catch (\Throwable $e) {
                $this->error("Enrollment #{$enrollment->id}: could not generate certificate — {$e->getMessage()}");

                continue;
            }

            $filename = 'KTM-Certificate-'.Str::slug($data['name']).'.pdf';

            $mailer->sendToUser('certificate_ready', $user, [
                'CourseName' => $data['course'],
                'AttendancePercent' => $this->attendanceLabel($enrollment),
            ], [
                'related' => ['certificate', $enrollment->id],
                'attachments' => [[
                    'data' => $pdf,
                    'name' => $filename,
                    'mime' => 'application/pdf',
                ]],
            ]);

            $enrollment->forceFill([
                'certificate_sent_at' => now(),
                'certificate_eligible' => true,
            ])->save();

            $sent++;
        }

        $this->info($this->option('dry') ? 'Eligible certificates listed above.' : "Certificates sent: {$sent}");

        return self::SUCCESS;
    }

    /** @return Collection<int, Enrollment> */
    private function eligibleEnrollments(?string $launchDate): Collection
    {
        $query = Enrollment::query()
            ->whereNull('archived_at')
            ->whereNull('certificate_sent_at')
            ->whereNotNull('user_id')
            ->whereNotNull('end_date')
            ->with(['user', 'batch.course']);

        if ($id = $this->option('enrollment')) {
            // Manual force-send for one student: skip the timing/launch windows.
            $query->whereKey($id);
        } else {
            $query->whereDate('end_date', '<=', now()->startOfDay()->subDays(self::DAYS_AFTER_COMPLETION));

            // Never email students whose course ended before this feature went live.
            if ($launchDate) {
                $query->whereDate('end_date', '>=', $launchDate);
            } else {
                return collect(); // launch date not set yet (dry run before first real run)
            }
        }

        // Re-check eligibility in PHP so the manual flag / 80% rule live in one place.
        return $query->get()->filter->isCertificateEligible()->values();
    }

    private function attendanceLabel(Enrollment $enrollment): string
    {
        return $enrollment->attendance_percentage !== null
            ? rtrim(rtrim(number_format((float) $enrollment->attendance_percentage, 1), '0'), '.').'%'
            : '100%';
    }
}
