<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Trip::query()
            ->with([
                'route:id,code,from_location,to_location',
                'bus:id,bus_type_id,name,license_plate',
                'bus.busType:id,name,total_seats',
            ])
            ->where('status', 'scheduled')
            ->where('departure_time', '>=', now());

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('departure_time', $request->date);
        }

        $trips = $query
            ->orderBy('departure_time')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách chuyến xe thành công.',
            'data' => $trips,
        ]);
    }

    public function show(Trip $trip): JsonResponse
    {
        $trip->load([
            'route:id,code,from_location,to_location',
            'bus:id,bus_type_id,name,license_plate',
            'bus.busType:id,name,total_seats',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết chuyến xe thành công.',
            'data' => $trip,
        ]);
    }

    public function seats(Trip $trip): JsonResponse
    {
        $seats = $trip->tripSeats()
            ->with('seat:id,seat_number,seat_row,seat_column,floor,seat_type')
            ->orderBy('id')
            ->get()
            ->map(function ($tripSeat) {
                return [
                    'trip_seat_id' => $tripSeat->id,
                    'seat_id' => $tripSeat->seat_id,
                    'seat_number' => $tripSeat->seat->seat_number,
                    'seat_row' => $tripSeat->seat->seat_row,
                    'seat_column' => $tripSeat->seat->seat_column,
                    'floor' => $tripSeat->seat->floor,
                    'seat_type' => $tripSeat->seat->seat_type,
                    'status' => $tripSeat->status,
                    'locked_until' => $tripSeat->locked_until,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ghế của chuyến xe thành công.',
            'data' => [
                'trip_id' => $trip->id,
                'trip_code' => $trip->code,
                'seats' => $seats,
            ],
        ]);
    }
}

