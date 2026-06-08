<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    protected $fillable = [
        'code',
        'from_location',
        'to_location',
        'distance_km',
        'estimated_duration_minutes',
        'is_active',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
