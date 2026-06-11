<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Trip;
use App\Models\TripSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTripOperationController extends Controller
{
    public function depart(Request $request, Trip $trip): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $trip = DB::transaction(function () use ($trip, $request) {
                $trip = Trip::query()
                    ->where('id', $trip->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($trip->status !== 'scheduled') {
                    abort(422, 'Chỉ chuyến đang scheduled mới được cho khởi hành.');
                }

                /*
                 * Khi chuyến khởi hành:
                 * - Booking pending_payment không còn giá trị nữa => expired
                 * - Ghế reserved của booking pending_payment được gỡ booking_id
                 * - Ghế available/reserved còn lại chuyển blocked để không bán nữa
                 */
                $pendingBookings = Booking::query()
                    ->where('trip_id', $trip->id)
                    ->where('status', 'pending_payment')
                    ->lockForUpdate()
                    ->get();

                foreach ($pendingBookings as $booking) {
                    TripSeat::query()
                        ->where('booking_id', $booking->id)
                        ->where('status', 'reserved')
                        ->update([
                            'status' => 'available',
                            'locked_until' => null,
                            'booking_id' => null,
                        ]);

                    $booking->update([
                        'status' => 'expired',
                        'cancelled_at' => now(),
                    ]);

                    BookingHistory::create([
                        'booking_id' => $booking->id,
                        'action' => 'booking_expired_by_trip_departed',
                        'old_status' => 'pending_payment',
                        'new_status' => 'expired',
                        'note' => 'Booking hết hiệu lực do chuyến xe đã khởi hành.',
                        'metadata' => [
                            'trip_id' => $trip->id,
                            'admin_id' => $request->user()->id,
                        ],
                    ]);
                }

                TripSeat::query()
                    ->where('trip_id', $trip->id)
                    ->whereIn('status', ['available', 'reserved'])
                    ->update([
                        'status' => 'blocked',
                        'locked_until' => null,
                        'booking_id' => null,
                    ]);

                $trip->update([
                    'status' => 'departed',
                ]);

                return $trip->fresh([
                    'route:id,code,from_location,to_location',
                    'bus:id,name,license_plate',
                ])->loadCount([
                    'tripSeats',
                    'bookings',
                    'tripSeats as booked_seats_count' => function ($query) {
                        $query->where('status', 'booked');
                    },
                    'tripSeats as blocked_seats_count' => function ($query) {
                        $query->where('status', 'blocked');
                    },
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Chuyến xe đã khởi hành.',
                'data' => $trip,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function complete(Request $request, Trip $trip): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $trip = DB::transaction(function () use ($trip) {
                $trip = Trip::query()
                    ->where('id', $trip->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($trip->status !== 'departed') {
                    abort(422, 'Chỉ chuyến đang departed mới được hoàn thành.');
                }

                $trip->update([
                    'status' => 'completed',
                ]);

                return $trip->fresh([
                    'route:id,code,from_location,to_location',
                    'bus:id,name,license_plate',
                ])->loadCount([
                    'tripSeats',
                    'bookings',
                    'tripSeats as booked_seats_count' => function ($query) {
                        $query->where('status', 'booked');
                    },
                    'tripSeats as blocked_seats_count' => function ($query) {
                        $query->where('status', 'blocked');
                    },
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Chuyến xe đã hoàn thành.',
                'data' => $trip,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function passengers(Trip $trip): JsonResponse
    {
        $trip->load([
            'route:id,code,from_location,to_location',
            'bus:id,name,license_plate',
        ])->loadCount([
            'tripSeats',
            'bookings',
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

        $passengers = Booking::query()
            ->where('trip_id', $trip->id)
            ->where('status', 'confirmed')
            ->with([
                'user:id,name,email,phone',
                'items:id,booking_id,trip_seat_id,seat_number,price',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
            ])
            ->latest()
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'customer' => $booking->user,
                    'seats' => $booking->items->pluck('seat_number')->values(),
                    'total_amount' => (float) $booking->total_amount,
                    'status' => $booking->status,
                    'confirmed_at' => $booking->confirmed_at,
                    'payments' => $booking->payments,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách hành khách của chuyến thành công.',
            'data' => [
                'trip' => [
                    'id' => $trip->id,
                    'code' => $trip->code,
                    'status' => $trip->status,
                    'departure_time' => $trip->departure_time,
                    'arrival_time' => $trip->arrival_time,
                    'route' => $trip->route,
                    'bus' => $trip->bus,
                    'seat_summary' => [
                        'total' => $trip->trip_seats_count,
                        'available' => $trip->available_seats_count,
                        'reserved' => $trip->reserved_seats_count,
                        'booked' => $trip->booked_seats_count,
                        'blocked' => $trip->blocked_seats_count,
                    ],
                    'booking_count' => $trip->bookings_count,
                ],
                'passengers' => $passengers,
            ],
        ]);
    }
}
