<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocietyMeeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'society_id',
        'title',
        'meeting_date',
        'meeting_time',
        'venue',
        'agenda',
        'minutes_path',
        'minutes_status',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class);
    }
}
