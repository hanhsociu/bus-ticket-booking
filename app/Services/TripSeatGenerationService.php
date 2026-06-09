<?php

namespace App\Services;

use App\Models\Seat;
use App\Models\Trip;
use App\Models\TripSeat;

class TripSeatGenerationService
{
    public function generateForTrip(Trip $trip): int
    {
        $trip->load('bus.busType');

        $busTypeId = $trip->bus->bus_type_id;

        $seats = Seat::query()
            ->where('bus_type_id', $busTypeId)
            ->where('is_active', true)
            ->orderBy('floor')
            ->orderBy('seat_row')
            ->orderBy('seat_column')
            ->get();

        $createdCount = 0;

        foreach ($seats as $seat) {
            $created = TripSeat::query()->firstOrCreate(
                [
                    'trip_id' => $trip->id,
                    'seat_id' => $seat->id,
                ],
                [
                    'status' => 'available',
                    'locked_until' => null,
                    'booking_id' => null,
                ]
            );

            if ($created->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return $createdCount;
    }
}
