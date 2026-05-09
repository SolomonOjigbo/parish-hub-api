<?php

namespace App\Console\Commands;

use App\Jobs\PledgeOverdueNotificationJob;
use App\Models\Pledge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePledgeStatuses extends Command
{
    protected $signature = 'pledges:update-statuses';
    protected $description = 'Update pledge statuses to overdue if past end date and not fully paid';

    public function handle(): int
    {
        $pledges = Pledge::where('status', 'active')
            ->where('end_date', '<', now())
            ->where('amount_paid', '<', \DB::raw('total_amount'))
            ->get();

        $count = 0;

        foreach ($pledges as $pledge) {
            $pledge->update(['status' => 'overdue']);
            dispatch(new PledgeOverdueNotificationJob($pledge));
            $count++;
        }

        Log::info("Updated {$count} pledges to overdue status");

        $this->info("Updated {$count} pledges to overdue status");

        return Command::SUCCESS;
    }
}
