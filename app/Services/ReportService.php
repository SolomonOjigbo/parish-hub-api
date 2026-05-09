<?php

namespace App\Services;

use App\Models\Offering;
use App\Models\Tithe;
use App\Models\Donation;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Models\Member;
use App\Models\Society;
use Carbon\Carbon;

class ReportService
{
    /**
     * Monthly summary of offerings, tithes, and donations grouped by month.
     */
    public function monthlySummary(Carbon $from, Carbon $to): array
    {
        $monthlyData = [];

        for ($date = $from->copy()->startOfMonth(); $date->lte($to->copy()->endOfMonth()); $date->addMonth()) {
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $offeringsTotal = Offering::whereBetween('collection_date', [$monthStart, $monthEnd])->sum('amount');
            $tithesTotal = Tithe::whereBetween('payment_date', [$monthStart, $monthEnd])->sum('amount');
            $donationsTotal = Donation::whereBetween('donation_date', [$monthStart, $monthEnd])->sum('amount');

            $monthlyData[] = [
                'month' => $date->format('F Y'),
                'year' => $date->year,
                'month_number' => $date->month,
                'offerings' => (float) $offeringsTotal,
                'tithes' => (float) $tithesTotal,
                'donations' => (float) $donationsTotal,
                'total' => (float) ($offeringsTotal + $tithesTotal + $donationsTotal),
            ];
        }

        $grandTotal = array_sum(array_column($monthlyData, 'total'));

        return [
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'monthly_data' => $monthlyData,
            'grand_total' => (float) $grandTotal,
        ];
    }

    /**
     * Year-to-date giving per member.
     */
    public function ytdGivingPerMember(Carbon $from, Carbon $to): array
    {
        $members = Member::with(['family'])->get();

        $memberGiving = $members->map(function ($member) use ($from, $to) {
            $offerings = Offering::where('member_id', $member->id)
                ->whereBetween('collection_date', [$from, $to])
                ->sum('amount');

            $tithes = Tithe::where('member_id', $member->id)
                ->whereBetween('payment_date', [$from, $to])
                ->sum('amount');

            $donations = Donation::where('member_id', $member->id)
                ->whereBetween('donation_date', [$from, $to])
                ->sum('amount');

            $pledges = PledgePayment::whereHas('pledge', function ($query) use ($member) {
                $query->where('member_id', $member->id);
            })->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

            $total = $offerings + $tithes + $donations + $pledges;

            return [
                'member_id' => $member->id,
                'name' => $member->full_name,
                'family_name' => $member->family?->name ?? 'N/A',
                'offerings' => (float) $offerings,
                'tithes' => (float) $tithes,
                'donations' => (float) $donations,
                'pledges' => (float) $pledges,
                'total' => (float) $total,
            ];
        })->sortByDesc('total')->values();

        return [
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'members' => $memberGiving,
        ];
    }

    /**
     * Pledge fulfilment report.
     */
    public function pledgeFulfilment(Carbon $from, Carbon $to): array
    {
        $pledges = Pledge::whereBetween('created_at', [$from, $to])
            ->with(['member'])
            ->get();

        $pledgeData = $pledges->map(function ($pledge) {
            return [
                'id' => $pledge->id,
                'member_name' => $pledge->member?->full_name ?? 'N/A',
                'purpose' => $pledge->purpose,
                'total_amount' => (float) $pledge->total_amount,
                'amount_paid' => (float) $pledge->amount_paid,
                'balance' => (float) $pledge->balance,
                'completion_percentage' => (float) $pledge->completion_percentage,
                'status' => $pledge->status,
                'start_date' => $pledge->start_date?->toIso8601String(),
                'end_date' => $pledge->end_date?->toIso8601String(),
            ];
        });

        return [
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'pledges' => $pledgeData,
        ];
    }

    /**
     * Society dues collection matrix for a given year.
     */
    public function societyDues(int $year): array
    {
        $societies = Society::all();
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $matrix = $societies->map(function ($society) use ($year, $months) {
            $monthlyDues = [];
            $total = 0;

            foreach ($months as $index => $month) {
                $monthNum = $index + 1;
                $monthTotal = $society->dues()
                    ->whereYear('payment_date', $year)
                    ->whereMonth('payment_date', $monthNum)
                    ->sum('amount');

                $monthlyDues[$month] = (float) $monthTotal;
                $total += $monthTotal;
            }

            return [
                'society_id' => $society->id,
                'society_name' => $society->name,
                'monthly_dues' => $monthlyDues,
                'total' => (float) $total,
            ];
        });

        return [
            'year' => $year,
            'societies' => $matrix,
        ];
    }

    /**
     * Donor report grouped by member or anonymous.
     */
    public function donorReport(Carbon $from, Carbon $to): array
    {
        $donations = Donation::whereBetween('donation_date', [$from, $to])
            ->with(['member'])
            ->get();

        $groupedDonors = $donations->groupBy(function ($donation) {
            if ($donation->is_anonymous) {
                return 'Anonymous';
            }
            return $donation->member_id ?? 'Unknown';
        })->map(function ($donations, $key) {
            $total = $donations->sum('amount');
            $firstDonation = $donations->first();

            return [
                'member_id' => $firstDonation->is_anonymous ? null : $firstDonation->member_id,
                'name' => $firstDonation->is_anonymous ? 'Anonymous' : ($firstDonation->member?->full_name ?? $firstDonation->donor_name),
                'total_given' => (float) $total,
                'donation_count' => $donations->count(),
            ];
        })->sortByDesc('total_given')->values();

        return [
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'donors' => $groupedDonors,
        ];
    }

    /**
     * Annual financial statement for Diocesan submission.
     */
    public function annualStatement(int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfYear();
        $to = Carbon::create($year, 12, 31)->endOfYear();

        // Income by category
        $offerings = Offering::whereBetween('collection_date', [$from, $to])->sum('amount');
        $tithes = Tithe::whereBetween('payment_date', [$from, $to])->sum('amount');
        $donations = Donation::whereBetween('donation_date', [$from, $to])->sum('amount');
        $pledgePayments = PledgePayment::whereBetween('payment_date', [$from, $to])->sum('amount');

        $totalIncome = $offerings + $tithes + $donations + $pledgePayments;

        // Weekly offering breakdown
        $weeklyOfferings = Offering::whereBetween('collection_date', [$from, $to])
            ->selectRaw('WEEK(collection_date, 3) as week, SUM(amount) as total')
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->map(function ($item) {
                return [
                    'week' => $item->week,
                    'total' => (float) $item->total,
                ];
            });

        // Top donors
        $topDonors = $this->donorReport($from, $to)['donors']->take(10);

        return [
            'year' => $year,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'income_by_category' => [
                'offerings' => (float) $offerings,
                'tithes' => (float) $tithes,
                'donations' => (float) $donations,
                'pledge_payments' => (float) $pledgePayments,
                'total_income' => (float) $totalIncome,
            ],
            'weekly_offering_breakdown' => $weeklyOfferings,
            'top_donors' => $topDonors,
        ];
    }
}
