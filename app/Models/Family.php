<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Family extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'head_member_id',
        'zone_id',
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

    protected function castsold(): array
    {
        return [
            'head_member_id' => 'integer',
        ];
    }
    protected function casts(): array
{
    return [
        'head_member_id' => 'string',
        'zone_id' => 'string',
    ];
}

    public function zoneold(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
    public function zone(): BelongsTo
{
    return $this->belongsTo(Zone::class, 'zone_id', 'id');
}

    public function membersold(): HasMany
    {
        return $this->hasMany(Member::class);
    }
    public function members(): HasMany
{
    return $this->hasMany(Member::class, 'family_id', 'id');
}

    public function headMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'head_member_id');
    }
}
