<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\TripSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()
            ->with([
                'user:id,name,email,phone',
                'trip:id,code,route_id,bus_id,departure_time,arrival_time,base_price,status',
                'trip.route:id,code,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,trip_seat_id,seat_number,price',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->integer('trip_id'));
        }

        if ($request->filled('date')) {
            $query->whereHas('trip', function ($tripQuery) use ($request) {
                $tripQuery->whereDate('departure_time', $request->query('date'));
            });
        }

        if ($request->filled('q')) {
            $q = $request->query('q');

            $query->where(function ($subQuery) use ($q) {
                $subQuery
                    ->where('booking_code', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($userQuery) use ($q) {
                        $userQuery
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            });
        }

        $bookings = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách booking admin thành công.',
            'data' => $bookings,
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load([
            'user:id,name,email,phone',
            'trip:id,code,route_id,bus_id,departure_time,arrival_time,base_price,status',
            'trip.route:id,code,from_location,to_location,distance_km,estimated_duration_minutes',
            'trip.bus:id,bus_type_id,name,license_plate',
            'trip.bus.busType:id,name,total_seats',
            'items:id,booking_id,trip_seat_id,seat_number,price',
            'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
            'payments:id,booking_id,payment_code,method,status,amount,transaction_id,gateway_response,paid_at,created_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết booking admin thành công.',
            'data' => $booking,
        ]);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = DB::transaction(function () use ($booking, $request) {
                $booking = Booking::query()
                    ->where('id', $booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'pending_payment') {
                    abort(422, 'Admin chỉ có thể hủy booking đang chờ thanh toán. Booking đã thanh toán cần xử lý hoàn tiền riêng.');
                }

                TripSeat::query()
                    ->where('booking_id', $booking->id)
                    ->where('status', 'reserved')
                    ->update([
                        'status' => 'available',
                        'locked_until' => null,
                        'booking_id' => null,
                    ]);

                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'booking_cancelled_by_admin',
                    'old_status' => 'pending_payment',
                    'new_status' => 'cancelled',
                    'note' => $request->input('reason') ?: 'Admin hủy booking đang chờ thanh toán.',
                    'metadata' => [
                        'cancelled_by' => 'admin',
                        'admin_id' => $request->user()->id,
                    ],
                ]);

                return $booking->fresh([
                    'user:id,name,email,phone',
                    'trip.route:id,code,from_location,to_location',
                    'trip.bus:id,name,license_plate',
                    'items:id,booking_id,trip_seat_id,seat_number,price',
                    'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
                    'payments:id,booking_id,payment_code,method,status,amount,paid_at',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Admin hủy booking thành công.',
                'data' => $booking,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
