<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Committee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'chair_member_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(CommitteeActionItem::class);
    }

    public function chairMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'chair_member_id');
    }
}
