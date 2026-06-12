<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tick the live engine every minute so scores/standings track the REAL clock
// (intended for the actual 2026 tournament). Enable with one OS cron line:
//   * * * * * cd /home/<user>/wc2026.sangtrang.com && php artisan schedule:run >> /dev/null 2>&1
// NOTE: this real-time tick clears any accelerated --live-demo. Don't run the OS
// scheduler and a manual `wc:simulate --live-demo` loop at the same time.
Schedule::command('wc:simulate')->everyMinute()->withoutOverlapping();
