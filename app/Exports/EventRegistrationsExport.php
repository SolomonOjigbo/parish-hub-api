<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EventRegistrationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $registrations)
    {
    }

    public function collection(): Collection
    {
        return $this->registrations;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Membership No',
            'Full Name',
            'Phone',
            'Email',
            'Registered At',
            'Payment Status',
            'Amount Paid',
        ];
    }

    /**
     * @param  \App\Models\EventRegistration  $reg
     * @return array<int, string|null>
     */
    public function map($reg): array
    {
        return [
            $reg->member?->membership_number,
            $reg->member?->full_name,
            $reg->member?->contactDetail?->primary_phone,
            $reg->member?->contactDetail?->email,
            $reg->registered_at?->toIso8601String(),
            $reg->payment_status,
            $reg->amount_paid,
        ];
    }
}
