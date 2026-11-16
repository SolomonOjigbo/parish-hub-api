<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CommunicationLog extends Model
{

    protected $keyType = 'string';
    public $incrementing = false;
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
