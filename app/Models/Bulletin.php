<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    protected $fillable = [
        'sunday_date',
        'title',
        'content',
        'generated_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sunday_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
