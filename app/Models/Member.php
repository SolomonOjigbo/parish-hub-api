<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'membership_number',
        'first_name',
        'last_name',
        'other_name',
        'baptismal_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'occupation',
        'photo_path',
        'family_id',
        'is_family_head',
        'zone_id',
        'status',
        'date_joined',
        'notes',
    ];

    protected $appends = ['full_name', 'age', 'photo_url'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_joined' => 'date',
            'is_family_head' => 'boolean',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function contactDetail(): HasOne
    {
        return $this->hasOne(MemberContactDetail::class);
    }

    public function sacramentalRecords(): HasMany
    {
        return $this->hasMany(SacramentalRecord::class);
    }

    public function societyMembers(): HasMany
    {
        return $this->hasMany(SocietyMember::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function eventAttendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function societies(): BelongsToMany
    {
        return $this->belongsToMany(Society::class, 'society_members')
            ->withPivot(['role', 'joined_at', 'is_active'])
            ->withTimestamps();
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = array_filter([
                $this->first_name,
                $this->other_name,
                $this->last_name,
            ]);

            return implode(' ', $parts);
        });
    }

    protected function age(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (!$this->date_of_birth) {
                return null;
            }

            return $this->date_of_birth->age;
        });
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->photo_path) {
                return null;
            }

            return Storage::url($this->photo_path);
        });
    }
}
