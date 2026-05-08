<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberContactDetail extends Model
{
    protected $fillable = [
        'member_id',
        'primary_phone',
        'whatsapp_number',
        'email',
        'address_line1',
        'address_line2',
        'lga',
        'state',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
