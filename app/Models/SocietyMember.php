<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocietyMember extends Model
{
    protected $fillable = [
        'society_id',
        'member_id',
        'role',
        'joined_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
