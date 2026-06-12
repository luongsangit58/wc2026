<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pull REAL results from openfootball every 5 minutes so scores/standings/
// scorers stay current during the tournament. Enable with one OS cron line:
//   * * * * * cd /home/<user>/wc2026.sangtrang.com && php artisan schedule:run >> /dev/null 2>&1
// (wc:simulate is a separate, optional demo engine — not scheduled.)
Schedule::command('wc:refresh')->everyFiveMinutes()->withoutOverlapping();
