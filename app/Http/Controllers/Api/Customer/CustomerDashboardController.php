<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalBookings = Booking::query()
            ->where('user_id', $user->id)
            ->count();

        $confirmedBookings = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->count();

        $pendingBookings = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending_payment')
            ->count();

        $cancelledBookings = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        $expiredBookings = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', 'expired')
            ->count();

        $totalPaidAmount = Payment::query()
            ->where('status', 'success')
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->sum('amount');

        $upcomingBookings = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->whereHas('trip', function ($query) {
                $query->where('departure_time', '>', now());
            })
            ->with([
                'trip:id,code,route_id,bus_id,departure_time,arrival_time,base_price,status',
                'trip.route:id,code,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,trip_seat_id,seat_number,price',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
            ])
            ->orderBy(
                Trip::select('departure_time')
                    ->whereColumn('trips.id', 'bookings.trip_id')
            )
            ->limit(5)
            ->get();

        $pendingPaymentBookings = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending_payment')
            ->with([
                'trip:id,code,route_id,bus_id,departure_time,arrival_time,base_price,status',
                'trip.route:id,code,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,trip_seat_id,seat_number,price',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
            ])
            ->latest()
            ->limit(5)
            ->get();

        $recentBookings = Booking::query()
            ->where('user_id', $user->id)
            ->with([
                'trip:id,code,route_id,bus_id,departure_time,arrival_time,base_price,status',
                'trip.route:id,code,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,trip_seat_id,seat_number,price',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
            ])
            ->latest()
            ->limit(8)
            ->get();

        $recentNotifications = Notification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu dashboard khách hàng thành công.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'summary' => [
                    'total_bookings' => $totalBookings,
                    'confirmed_bookings' => $confirmedBookings,
                    'pending_bookings' => $pendingBookings,
                    'cancelled_bookings' => $cancelledBookings,
                    'expired_bookings' => $expiredBookings,
                    'total_paid_amount' => (float) $totalPaidAmount,
                ],
                'upcoming_bookings' => $upcomingBookings,
                'pending_payment_bookings' => $pendingPaymentBookings,
                'recent_bookings' => $recentBookings,
                'recent_notifications' => $recentNotifications,
            ],
        ]);
    }
}
