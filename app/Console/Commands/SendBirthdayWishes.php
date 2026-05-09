<?php

namespace App\Console\Commands;

use App\Jobs\BirthdayNotificationJob;
use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBirthdayWishes extends Command
{
    protected $signature = 'members:send-birthday-wishes';
    protected $description = 'Send birthday wishes to members whose birthday is today';

    public function handle(): int
    {
        $members = Member::where('is_active', true)
            ->whereNotNull('email')
            ->whereRaw('DAY(date_of_birth) = DAY(NOW())')
            ->whereRaw('MONTH(date_of_birth) = MONTH(NOW())')
            ->get();

        $count = 0;

        foreach ($members as $member) {
            dispatch(new BirthdayNotificationJob($member));
            $count++;
        }

        Log::info("Sent birthday wishes to {$count} members");

        $this->info("Sent birthday wishes to {$count} members");

        return Command::SUCCESS;
    }
}
