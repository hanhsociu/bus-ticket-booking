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
            ->withCount([
                'tripSeats',
                'tripSeats as available_seats_count' => function ($query) {
                    $query->where('status', 'available');
                },
                'tripSeats as reserved_seats_count' => function ($query) {
                    $query->where('status', 'reserved');
                },
                'tripSeats as booked_seats_count' => function ($query) {
                    $query->where('status', 'booked');
                },
                'tripSeats as blocked_seats_count' => function ($query) {
                    $query->where('status', 'blocked');
                },
            ])
            ->where('status', 'scheduled')
            ->where('departure_time', '>', now());

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->integer('route_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('departure_time', $request->query('date'));
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
        if (!$this->isTripOpenForSale($trip)) {
            return response()->json([
                'success' => false,
                'message' => 'Chuyến xe không còn mở bán hoặc đã khởi hành.',
            ], 422);
        }

        $trip->load([
            'route:id,code,from_location,to_location',
            'bus:id,bus_type_id,name,license_plate',
            'bus.busType:id,name,total_seats',
        ])->loadCount([
            'tripSeats',
            'tripSeats as available_seats_count' => function ($query) {
                $query->where('status', 'available');
            },
            'tripSeats as reserved_seats_count' => function ($query) {
                $query->where('status', 'reserved');
            },
            'tripSeats as booked_seats_count' => function ($query) {
                $query->where('status', 'booked');
            },
            'tripSeats as blocked_seats_count' => function ($query) {
                $query->where('status', 'blocked');
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết chuyến xe thành công.',
            'data' => $trip,
        ]);
    }

    public function seats(Trip $trip): JsonResponse
    {
        if (!$this->isTripOpenForSale($trip)) {
            return response()->json([
                'success' => false,
                'message' => 'Chuyến xe không còn mở bán hoặc đã khởi hành, không thể xem ghế để đặt vé.',
            ], 422);
        }

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
                    'is_available' => $tripSeat->status === 'available',
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách ghế của chuyến xe thành công.',
            'data' => [
                'trip_id' => $trip->id,
                'trip_code' => $trip->code,
                'departure_time' => $trip->departure_time,
                'arrival_time' => $trip->arrival_time,
                'status' => $trip->status,
                'seat_summary' => [
                    'total' => $seats->count(),
                    'available' => $seats->where('status', 'available')->count(),
                    'reserved' => $seats->where('status', 'reserved')->count(),
                    'booked' => $seats->where('status', 'booked')->count(),
                    'blocked' => $seats->where('status', 'blocked')->count(),
                ],
                'seats' => $seats,
            ],
        ]);
    }

    private function isTripOpenForSale(Trip $trip): bool
    {
        return $trip->status === 'scheduled'
            && $trip->departure_time > now();
    }
}
