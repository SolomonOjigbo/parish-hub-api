<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MemberContactDetail extends Model
{

    protected $keyType = 'string';
    public $incrementing = false;
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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
