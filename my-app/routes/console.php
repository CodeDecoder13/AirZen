<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cadence is user-configurable on the Notifications page (DigestSchedule);
// the scheduler ticks every minute and only fires when the schedule is due.
Schedule::command('digest:send-morning')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => App\Models\DigestSchedule::current()->isDue());
