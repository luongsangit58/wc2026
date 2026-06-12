<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tick the live engine every minute so scores/standings progress in real time.
// Enable with one OS cron line:  * * * * * cd /home/miichi/TMP/wc2026 && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('wc:simulate')->everyMinute()->withoutOverlapping();
