<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocietyMeeting extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;
    
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
            'meeting_date' => 'date',
        ];
    }

    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class);
    }
}
