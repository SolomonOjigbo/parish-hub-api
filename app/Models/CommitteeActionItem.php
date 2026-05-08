<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeActionItem extends Model
{
    protected $fillable = [
        'committee_id',
        'title',
        'description',
        'assigned_to_member_id',
        'due_date',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assigned_to_member_id');
    }
}
