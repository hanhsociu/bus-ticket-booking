<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bus extends Model
{
    protected $fillable = [
        'bus_type_id',
        'name',
        'license_plate',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function busType(): BelongsTo
    {
        return $this->belongsTo(BusType::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
