<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'heard_from',
        'visited_on',
        'followed_up_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
            'followed_up_at' => 'datetime',
        ];
    }
}
