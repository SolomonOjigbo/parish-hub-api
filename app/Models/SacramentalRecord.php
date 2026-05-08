<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacramentalRecord extends Model
{
    protected $fillable = [
        'member_id',
        'type',
        'date',
        'church',
        'minister',
        'spouse_name',
        'certificate_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
