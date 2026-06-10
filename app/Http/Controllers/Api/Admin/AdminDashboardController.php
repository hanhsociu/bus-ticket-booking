<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Bus;
use App\Models\BusType;
use App\Models\Payment;
use App\Models\Route as BusRoute;
use App\Models\Trip;
use App\Models\TripSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $today = now()->toDateString();

        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu dashboard admin thành công.',
            'data' => [
                'filters' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],

                'summary' => $this->summary($today, $fromDate, $toDate),

                'booking_status' => $this->bookingStatus($fromDate, $toDate),

                'payment_status' => $this->paymentStatus($fromDate, $toDate),

                'system_totals' => $this->systemTotals(),

                'charts' => [
                    'booking_by_day' => $this->bookingByDay($fromDate, $toDate),
                    'revenue_by_day' => $this->revenueByDay($fromDate, $toDate),
                ],

                'top_routes_by_booking' => $this->topRoutesByBooking($fromDate, $toDate),

                'top_routes_by_revenue' => $this->topRoutesByRevenue($fromDate, $toDate),

                'upcoming_trips' => $this->upcomingTrips(),

                'recent_bookings' => $this->recentBookings(),

                'trip_occupancy' => $this->tripOccupancy(),
            ],
        ]);
    }

    private function summary(string $today, string $fromDate, string $toDate): array
    {
        $totalBookings = Booking::query()->count();

        $todayBookings = Booking::query()
            ->whereDate('created_at', $today)
            ->count();

        $rangeBookings = Booking::query()
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->count();

        $confirmedBookings = Booking::query()
            ->where('status', 'confirmed')
            ->count();

        $pendingBookings = Booking::query()
            ->where('status', 'pending_payment')
            ->count();

        $cancelledBookings = Booking::query()
            ->where('status', 'cancelled')
            ->count();

        $expiredBookings = Booking::query()
            ->where('status', 'expired')
            ->count();

        $totalRevenue = Payment::query()
            ->where('status', 'success')
            ->sum('amount');

        $todayRevenue = Payment::query()
            ->where('status', 'success')
            ->whereDate('paid_at', $today)
            ->sum('amount');

        $rangeRevenue = Payment::query()
            ->where('status', 'success')
            ->whereDate('paid_at', '>=', $fromDate)
            ->whereDate('paid_at', '<=', $toDate)
            ->sum('amount');

        return [
            'total_bookings' => $totalBookings,
            'today_bookings' => $todayBookings,
            'range_bookings' => $rangeBookings,

            'confirmed_bookings' => $confirmedBookings,
            'pending_bookings' => $pendingBookings,
            'cancelled_bookings' => $cancelledBookings,
            'expired_bookings' => $expiredBookings,

            'total_revenue' => (float) $totalRevenue,
            'today_revenue' => (float) $todayRevenue,
            'range_revenue' => (float) $rangeRevenue,
        ];
    }

    private function bookingStatus(string $fromDate, string $toDate): array
    {
        return Booking::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => (int) $item->total];
            })
            ->toArray();
    }

    private function paymentStatus(string $fromDate, string $toDate): array
    {
        return Payment::query()
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('SUM(amount) as amount'))
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'total' => (int) $item->total,
                    'amount' => (float) $item->amount,
                ];
            })
            ->values()
            ->toArray();
    }

    private function systemTotals(): array
    {
        return [
            'routes' => BusRoute::query()->count(),
            'active_routes' => BusRoute::query()->where('is_active', true)->count(),

            'bus_types' => BusType::query()->count(),
            'active_bus_types' => BusType::query()->where('is_active', true)->count(),

            'buses' => Bus::query()->count(),
            'active_buses' => Bus::query()->where('is_active', true)->count(),

            'trips' => Trip::query()->count(),
            'scheduled_trips' => Trip::query()->where('status', 'scheduled')->count(),
            'departed_trips' => Trip::query()->where('status', 'departed')->count(),
            'completed_trips' => Trip::query()->where('status', 'completed')->count(),
            'cancelled_trips' => Trip::query()->where('status', 'cancelled')->count(),

            'trip_seats' => TripSeat::query()->count(),
            'available_seats' => TripSeat::query()->where('status', 'available')->count(),
            'reserved_seats' => TripSeat::query()->where('status', 'reserved')->count(),
            'booked_seats' => TripSeat::query()->where('status', 'booked')->count(),
            'blocked_seats' => TripSeat::query()->where('status', 'blocked')->count(),
        ];
    }

    private function bookingByDay(string $fromDate, string $toDate): array
    {
        return Booking::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total' => (int) $item->total,
                ];
            })
            ->toArray();
    }

    private function revenueByDay(string $fromDate, string $toDate): array
    {
        return Payment::query()
            ->selectRaw('DATE(paid_at) as date')
            ->selectRaw('SUM(amount) as revenue')
            ->where('status', 'success')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $fromDate)
            ->whereDate('paid_at', '<=', $toDate)
            ->groupByRaw('DATE(paid_at)')
            ->orderByRaw('DATE(paid_at)')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    private function topRoutesByBooking(string $fromDate, string $toDate): array
    {
        return Booking::query()
            ->join('trips', 'bookings.trip_id', '=', 'trips.id')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->select([
                'routes.id',
                'routes.code',
                'routes.from_location',
                'routes.to_location',
                DB::raw('COUNT(bookings.id) as booking_count'),
            ])
            ->whereDate('bookings.created_at', '>=', $fromDate)
            ->whereDate('bookings.created_at', '<=', $toDate)
            ->groupBy('routes.id', 'routes.code', 'routes.from_location', 'routes.to_location')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'route_id' => $item->id,
                    'code' => $item->code,
                    'from_location' => $item->from_location,
                    'to_location' => $item->to_location,
                    'booking_count' => (int) $item->booking_count,
                ];
            })
            ->toArray();
    }

    private function topRoutesByRevenue(string $fromDate, string $toDate): array
    {
        return Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('trips', 'bookings.trip_id', '=', 'trips.id')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->select([
                'routes.id',
                'routes.code',
                'routes.from_location',
                'routes.to_location',
                DB::raw('SUM(payments.amount) as revenue'),
                DB::raw('COUNT(payments.id) as payment_count'),
            ])
            ->where('payments.status', 'success')
            ->whereDate('payments.paid_at', '>=', $fromDate)
            ->whereDate('payments.paid_at', '<=', $toDate)
            ->groupBy('routes.id', 'routes.code', 'routes.from_location', 'routes.to_location')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'route_id' => $item->id,
                    'code' => $item->code,
                    'from_location' => $item->from_location,
                    'to_location' => $item->to_location,
                    'payment_count' => (int) $item->payment_count,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    private function upcomingTrips(): array
    {
        return Trip::query()
            ->with([
                'route:id,code,from_location,to_location',
                'bus:id,name,license_plate',
            ])
            ->withCount([
                'tripSeats',
                'bookings',
                'tripSeats as booked_seats_count' => function ($query) {
                    $query->where('status', 'booked');
                },
                'tripSeats as reserved_seats_count' => function ($query) {
                    $query->where('status', 'reserved');
                },
                'tripSeats as available_seats_count' => function ($query) {
                    $query->where('status', 'available');
                },
            ])
            ->where('status', 'scheduled')
            ->where('departure_time', '>', now())
            ->orderBy('departure_time')
            ->limit(8)
            ->get()
            ->map(function (Trip $trip) {
                return [
                    'id' => $trip->id,
                    'code' => $trip->code,
                    'departure_time' => $trip->departure_time,
                    'arrival_time' => $trip->arrival_time,
                    'base_price' => (float) $trip->base_price,
                    'route' => $trip->route,
                    'bus' => $trip->bus,
                    'trip_seats_count' => $trip->trip_seats_count,
                    'booked_seats_count' => $trip->booked_seats_count,
                    'reserved_seats_count' => $trip->reserved_seats_count,
                    'available_seats_count' => $trip->available_seats_count,
                    'bookings_count' => $trip->bookings_count,
                ];
            })
            ->toArray();
    }

    private function recentBookings(): array
    {
        return Booking::query()
            ->with([
                'user:id,name,email,phone',
                'trip:id,code,route_id,departure_time,status',
                'trip.route:id,code,from_location,to_location',
                'items:id,booking_id,seat_number,price',
                'payments:id,booking_id,status,amount,paid_at',
            ])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->status,
                    'total_amount' => (float) $booking->total_amount,
                    'created_at' => $booking->created_at,
                    'expired_at' => $booking->expired_at,
                    'confirmed_at' => $booking->confirmed_at,
                    'user' => $booking->user,
                    'trip' => $booking->trip,
                    'items' => $booking->items,
                    'payments' => $booking->payments,
                ];
            })
            ->toArray();
    }

    private function tripOccupancy(): array
    {
        return Trip::query()
            ->with([
                'route:id,code,from_location,to_location',
                'bus:id,name,license_plate',
            ])
            ->withCount([
                'tripSeats',
                'tripSeats as booked_seats_count' => function ($query) {
                    $query->where('status', 'booked');
                },
                'tripSeats as reserved_seats_count' => function ($query) {
                    $query->where('status', 'reserved');
                },
                'tripSeats as available_seats_count' => function ($query) {
                    $query->where('status', 'available');
                },
            ])
            ->whereIn('status', ['scheduled', 'departed'])
            ->orderBy('departure_time')
            ->limit(10)
            ->get()
            ->map(function (Trip $trip) {
                $totalSeats = (int) $trip->trip_seats_count;
                $occupiedSeats = (int) $trip->booked_seats_count + (int) $trip->reserved_seats_count;

                $occupancyRate = $totalSeats > 0
                    ? round(($occupiedSeats / $totalSeats) * 100, 2)
                    : 0;

                return [
                    'trip_id' => $trip->id,
                    'trip_code' => $trip->code,
                    'status' => $trip->status,
                    'departure_time' => $trip->departure_time,
                    'route' => $trip->route,
                    'bus' => $trip->bus,
                    'total_seats' => $totalSeats,
                    'booked_seats' => (int) $trip->booked_seats_count,
                    'reserved_seats' => (int) $trip->reserved_seats_count,
                    'available_seats' => (int) $trip->available_seats_count,
                    'occupied_seats' => $occupiedSeats,
                    'occupancy_rate' => $occupancyRate,
                ];
            })
            ->toArray();
    }
}
