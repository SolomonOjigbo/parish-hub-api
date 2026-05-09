<?php

namespace App\Observers;

use App\Models\Member;
use Illuminate\Support\Facades\DB;

class MemberObserver
{
    /**
     * Auto-generate the membership_number on creation:
     * Format: SFC-{YEAR}-{4-digit-padded-sequence}
     */
    public function creating(Member $member): void
    {
        if (!empty($member->membership_number)) {
            return;
        }

        DB::transaction(function () use ($member): void {
            $year   = now()->year;
            $prefix = 'SFC-' . $year . '-';

            $last = Member::withTrashed()
                ->where('membership_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->max('membership_number');

            $next = $last ? ((int) substr($last, -4)) + 1 : 1;

            $member->membership_number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }
}
