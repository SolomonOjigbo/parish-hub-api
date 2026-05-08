<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PledgePayment extends Model
{
    protected $fillable = [
        'pledge_id',
        'amount',
        'payment_date',
        'payment_method',
        'transfer_reference',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function booted(): void
    {
        static::saved(function (PledgePayment $payment) {
            $pledge = $payment->pledge;

            $totalPaid = $pledge->payments()->sum('amount');
            $pledge->amount_paid = $totalPaid;

            if ((float) $totalPaid >= (float) $pledge->total_amount) {
                $pledge->status = 'completed';
            }

            $pledge->save();
        });
    }
}
