<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pledge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'member_id',
        'purpose',
        'description',
        'total_amount',
        'amount_paid',
        'payment_frequency',
        'start_date',
        'end_date',
        'status',
        'recorded_by',
    ];

    protected $appends = ['balance', 'completion_percentage'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PledgePayment::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected function balance(): Attribute
    {
        return Attribute::get(function (): float {
            return (float) bcsub($this->total_amount, $this->amount_paid, 2);
        });
    }

    protected function completionPercentage(): Attribute
    {
        return Attribute::get(function (): float {
            if ((float) $this->total_amount === 0.0) {
                return 0.0;
            }

            return round(((float) $this->amount_paid / (float) $this->total_amount) * 100, 1);
        });
    }
}
