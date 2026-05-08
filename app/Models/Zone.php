<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }
}
