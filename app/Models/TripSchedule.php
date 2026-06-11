<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripSchedule extends Model
{
    protected $fillable = [
        'route_id',
        'bus_id',
        'name',
        'frequency',
        'days_of_week',
        'departure_time',
        'arrival_time',
        'base_price',
        'start_date',
        'end_date',
        'generate_days_ahead',
        'is_active',
        'last_generated_until',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_generated_until' => 'date',
        'base_price' => 'decimal:2',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function displayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $route = $this->relationLoaded('route') ? $this->route : null;

        if ($route) {
            return $route->from_location.' → '.$route->to_location;
        }

        return 'Schedule #'.$this->id;
    }
}
