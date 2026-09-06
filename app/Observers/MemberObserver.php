<?php

namespace App\Observers;

use App\Models\Member;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class MemberObserver
{
    /**
     * Auto-generate the membership_number on creation:
     * Format: {PREFIX}-{YEAR}-{4-digit-padded-sequence}, e.g. SFC-2026-0041.
     * The prefix is configurable via the `membership_prefix` setting.
     */
    public function creating(Member $member): void
    {
        if (!empty($member->membership_number)) {
            return;
        }

        DB::transaction(function () use ($member): void {
            $year   = now()->year;
            $prefix = Setting::get('membership_prefix', 'SFC') . '-' . $year . '-';

            // Longer suffixes (past 9999) sort before shorter ones by length,
            // so ordering by length first keeps the sequence numeric-safe.
            $last = Member::withTrashed()
                ->where('membership_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByRaw('LENGTH(membership_number) DESC')
                ->orderByDesc('membership_number')
                ->value('membership_number');

            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            $member->membership_number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }
}
