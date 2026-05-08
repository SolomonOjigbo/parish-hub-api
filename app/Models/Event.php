<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'description',
        'start_datetime',
        'end_datetime',
        'location',
        'max_capacity',
        'requires_registration',
        'is_retreat',
        'retreat_fee',
        'accommodation_notes',
        'include_in_bulletin',
        'created_by',
    ];

    protected $appends = ['status'];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'requires_registration' => 'boolean',
            'is_retreat' => 'boolean',
            'include_in_bulletin' => 'boolean',
            'retreat_fee' => 'decimal:2',
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function status(): Attribute
    {
        return Attribute::get(function (): string {
            $now = now();

            if ($this->end_datetime && $this->end_datetime->isPast()) {
                return 'past';
            }

            if ($this->start_datetime->isFuture()) {
                return 'upcoming';
            }

            return 'ongoing';
        });
    }
}
