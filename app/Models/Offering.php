<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offering extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collection_date',
        'member_id',
        'envelope_number',
        'amount',
        'payment_method',
        'transfer_reference',
        'is_anonymous',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
