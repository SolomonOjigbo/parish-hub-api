<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MembersExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $members)
    {
    }

    public function collection(): Collection
    {
        return $this->members;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Membership No',
            'Full Name',
            'Gender',
            'Phone',
            'Email',
            'Society',
            'Zone',
            'Status',
            'Date Joined',
        ];
    }

    /**
     * @param  Member  $member
     * @return array<int, string|null>
     */
    public function map($member): array
    {
        return [
            $member->membership_number,
            $member->full_name,
            ucfirst((string) $member->gender),
            $member->contactDetail?->primary_phone,
            $member->contactDetail?->email,
            $member->societies->pluck('name')->join(', '),
            $member->zone?->name,
            ucfirst((string) $member->status),
            $member->date_joined?->format('Y-m-d'),
        ];
    }
}
