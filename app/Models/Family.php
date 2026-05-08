<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'head_member_id',
        'zone_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'head_member_id' => 'integer',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function headMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'head_member_id');
    }
}
