<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinancialReportExport implements FromArray, WithHeadings, WithTitle
{
    protected string $type;
    protected array $data;

    public function __construct(string $type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function array(): array
    {
        return match ($this->type) {
            'monthly_summary' => $this->formatMonthlySummary(),
            'ytd_giving' => $this->formatYtdGiving(),
            'pledge_fulfilment' => $this->formatPledgeFulfilment(),
            'society_dues' => $this->formatSocietyDues(),
            'donor_report' => $this->formatDonorReport(),
            'annual_statement' => $this->formatAnnualStatement(),
            default => [],
        };
    }

    public function headings(): array
    {
        return match ($this->type) {
            'monthly_summary' => ['Month', 'Year', 'Offerings', 'Tithes', 'Donations', 'Total'],
            'ytd_giving' => ['Member ID', 'Name', 'Family Name', 'Offerings', 'Tithes', 'Donations', 'Pledges', 'Total'],
            'pledge_fulfilment' => ['ID', 'Member Name', 'Purpose', 'Total Amount', 'Amount Paid', 'Balance', 'Completion %', 'Status', 'Start Date', 'End Date'],
            'society_dues' => ['Society ID', 'Society Name', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'Total'],
            'donor_report' => ['Member ID', 'Name', 'Total Given', 'Donation Count'],
            'annual_statement' => ['Category', 'Amount'],
            default => [],
        };
    }

    public function title(): string
    {
        return match ($this->type) {
            'monthly_summary' => 'Monthly Summary',
            'ytd_giving' => 'Year-to-Date Giving',
            'pledge_fulfilment' => 'Pledge Fulfilment',
            'society_dues' => 'Society Dues',
            'donor_report' => 'Donor Report',
            'annual_statement' => 'Annual Statement',
            default => 'Financial Report',
        };
    }

    protected function formatMonthlySummary(): array
    {
        return collect($this->data['monthly_data'])->map(function ($item) {
            return [
                $item['month'],
                $item['year'],
                $item['offerings'],
                $item['tithes'],
                $item['donations'],
                $item['total'],
            ];
        })->toArray();
    }

    protected function formatYtdGiving(): array
    {
        return collect($this->data['members'])->map(function ($item) {
            return [
                $item['member_id'],
                $item['name'],
                $item['family_name'],
                $item['offerings'],
                $item['tithes'],
                $item['donations'],
                $item['pledges'],
                $item['total'],
            ];
        })->toArray();
    }

    protected function formatPledgeFulfilment(): array
    {
        return collect($this->data['pledges'])->map(function ($item) {
            return [
                $item['id'],
                $item['member_name'],
                $item['purpose'],
                $item['total_amount'],
                $item['amount_paid'],
                $item['balance'],
                $item['completion_percentage'],
                $item['status'],
                $item['start_date'] ?? 'N/A',
                $item['end_date'] ?? 'N/A',
            ];
        })->toArray();
    }

    protected function formatSocietyDues(): array
    {
        return collect($this->data['societies'])->map(function ($item) {
            return [
                $item['society_id'],
                $item['society_name'],
                $item['monthly_dues']['January'],
                $item['monthly_dues']['February'],
                $item['monthly_dues']['March'],
                $item['monthly_dues']['April'],
                $item['monthly_dues']['May'],
                $item['monthly_dues']['June'],
                $item['monthly_dues']['July'],
                $item['monthly_dues']['August'],
                $item['monthly_dues']['September'],
                $item['monthly_dues']['October'],
                $item['monthly_dues']['November'],
                $item['monthly_dues']['December'],
                $item['total'],
            ];
        })->toArray();
    }

    protected function formatDonorReport(): array
    {
        return collect($this->data['donors'])->map(function ($item) {
            return [
                $item['member_id'],
                $item['name'],
                $item['total_given'],
                $item['donation_count'],
            ];
        })->toArray();
    }

    protected function formatAnnualStatement(): array
    {
        $income = $this->data['income_by_category'];
        return [
            ['Offerings', $income['offerings']],
            ['Tithes', $income['tithes']],
            ['Donations', $income['donations']],
            ['Pledge Payments', $income['pledge_payments']],
            ['Total Income', $income['total_income']],
        ];
    }
}
