<?php

namespace App\Imports;

use App\Models\Offering;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OfferingsImport implements ToCollection, WithHeadingRow
{
    protected int $userId;
    protected array $errors = [];
    protected int $successCount = 0;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $validator = Validator::make($row->toArray(), [
                'collection_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'in:cash,bank_transfer,pos,cheque'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $row->toArray(),
                    'errors' => $validator->errors()->toArray(),
                ];
                continue;
            }

            Offering::create([
                'collection_date' => $row['collection_date'],
                'member_id' => $row['member_id'] ?? null,
                'envelope_number' => $row['envelope_number'] ?? null,
                'amount' => $row['amount'],
                'payment_method' => $row['payment_method'],
                'transfer_reference' => $row['transfer_reference'] ?? null,
                'is_anonymous' => $row['is_anonymous'] ?? false,
                'recorded_by' => $this->userId,
                'notes' => $row['notes'] ?? null,
            ]);

            $this->successCount++;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}
