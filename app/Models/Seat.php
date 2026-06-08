<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    protected $fillable = [
        'bus_type_id',
        'seat_number',
        'seat_row',
        'seat_column',
        'floor',
        'seat_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function busType(): BelongsTo
    {
        return $this->belongsTo(BusType::class);
    }

    public function tripSeats(): HasMany
    {
        return $this->hasMany(TripSeat::class);
    }
}
