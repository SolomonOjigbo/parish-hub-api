<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    protected $fillable = [
        'type',
        'subject',
        'message',
        'recipient_type',
        'recipient_ids',
        'recipient_count',
        'sent_count',
        'failed_count',
        'sent_by',
        'status',
        'provider_response',
        'sent_at',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_ids' => 'array',
            'provider_response' => 'array',
            'sent_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function recipients()
    {
        return Member::whereIn('id', $this->recipient_ids ?? []);
    }
}
