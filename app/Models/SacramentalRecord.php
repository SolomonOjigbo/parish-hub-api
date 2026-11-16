<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SacramentalRecord extends Model
{

    protected $keyType = 'string';
    public $incrementing = false;
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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

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
