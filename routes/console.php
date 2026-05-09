<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\UpdatePledgeStatuses;
use App\Console\Commands\SendBirthdayWishes;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pledges:update-statuses')->daily();
Schedule::command('members:send-birthday-wishes')->dailyAt('06:00');
