<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sends each demo's follow-up email ~1 hour after the session (requires the
// server cron `php artisan schedule:run` to be running — see FOR-NIRAJ.md).
Schedule::command('demos:send-followups')->everyFifteenMinutes()->withoutOverlapping();

// Emails the attendance certificate 3 days after a course ends, to students who
// kept 80%+ attendance (also needs the server cron — see FOR-NIRAJ.md).
Schedule::command('certificates:send')->dailyAt('03:30')->withoutOverlapping();
