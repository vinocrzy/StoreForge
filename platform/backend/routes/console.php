<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Abandoned cart recovery — detect + send every 15 minutes
Schedule::command('carts:detect-abandoned')->everyFifteenMinutes();
Schedule::command('carts:send-recovery-emails')->everyFifteenMinutes();
