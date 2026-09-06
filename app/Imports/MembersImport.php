<?php

namespace App\Imports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Roster import. Expected headings (snake_case, header row required):
 * first_name, last_name, primary_phone (required);
 * other_name, baptismal_name, gender, marital_status, date_of_birth,
 * occupation, whatsapp_number, email, address_line1, lga, status,
 * date_joined (optional).
 */
class MembersImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped = 0;

    private const GENDERS = ['male', 'female'];
    private const MARITAL = ['single', 'married', 'widowed', 'divorced', 'religious'];
    private const STATUSES = ['active', 'inactive', 'transferred', 'deceased'];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            $phone = trim((string) ($row['primary_phone'] ?? ''));

            if ($firstName === '' || $lastName === '' || $phone === '') {
                $this->skipped++;
                continue;
            }

            DB::transaction(function () use ($row, $firstName, $lastName, $phone): void {
                $member = Member::create([
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'other_name'     => trim((string) ($row['other_name'] ?? '')) ?: null,
                    'baptismal_name' => trim((string) ($row['baptismal_name'] ?? '')) ?: null,
                    'gender'         => in_array($row['gender'] ?? null, self::GENDERS, true) ? $row['gender'] : 'male',
                    'marital_status' => in_array($row['marital_status'] ?? null, self::MARITAL, true) ? $row['marital_status'] : 'single',
                    'date_of_birth'  => $this->toDate($row['date_of_birth'] ?? null),
                    'occupation'     => trim((string) ($row['occupation'] ?? '')) ?: null,
                    'status'         => in_array($row['status'] ?? null, self::STATUSES, true) ? $row['status'] : 'active',
                    'date_joined'    => $this->toDate($row['date_joined'] ?? null) ?? now()->toDateString(),
                ]);

                $member->contactDetail()->create([
                    'primary_phone'   => $phone,
                    'whatsapp_number' => trim((string) ($row['whatsapp_number'] ?? '')) ?: null,
                    'email'           => trim((string) ($row['email'] ?? '')) ?: null,
                    'address_line1'   => trim((string) ($row['address_line1'] ?? '')) ?: null,
                    'lga'             => trim((string) ($row['lga'] ?? '')) ?: null,
                ]);
            });

            $this->imported++;
        }
    }

    private function toDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            // Excel serial date
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
