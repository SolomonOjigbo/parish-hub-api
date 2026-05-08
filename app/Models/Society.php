<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Society extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'colour',
        'icon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Society $society) {
            if (empty($society->slug)) {
                $society->slug = Str::slug($society->name);
            }
        });
    }

    public function societyMembers(): HasMany
    {
        return $this->hasMany(SocietyMember::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(SocietyMeeting::class);
    }

    public function dues(): HasMany
    {
        return $this->hasMany(SocietyDue::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'society_members')
            ->withPivot(['role', 'joined_at', 'is_active'])
            ->withTimestamps();
    }
}
