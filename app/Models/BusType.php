<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'total_seats',
        'seat_layout',
        'is_active',
    ];

    protected $casts = [
        'seat_layout' => 'array',
        'is_active' => 'boolean',
    ];

    public function buses(): HasMany
    {
        return $this->hasMany(Bus::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function tripsExists(): bool
    {
        return $this->buses()
            ->whereHas('trips')
            ->exists();
    }
}
