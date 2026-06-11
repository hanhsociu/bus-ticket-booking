<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTripRequest;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Services\TripSeatGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminTripController extends Controller
{
    public function __construct(
        private readonly TripSeatGenerationService $tripSeatGenerationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Trip::query()
            ->with([
                'route:id,code,from_location,to_location',
                'bus:id,bus_type_id,name,license_plate',
                'bus.busType:id,name,total_seats',
            ]);

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->integer('route_id'));
        }

        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->integer('bus_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('departure_time', $request->query('date'));
        }

        $trips = $query
            ->withCount([
                'tripSeats',
                'bookings',
            ])
            ->latest('departure_time')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách chuyến xe admin thành công.',
            'data' => $trips,
        ]);
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $trip = DB::transaction(function () use ($data) {
                $this->ensureBusIsAvailable(
                    busId: (int) $data['bus_id'],
                    departureTime: $data['departure_time'],
                    arrivalTime: $data['arrival_time']
                );

                $trip = Trip::create([
                    'route_id' => $data['route_id'],
                    'bus_id' => $data['bus_id'],
                    'code' => $this->generateTripCode(
                        routeId: (int) $data['route_id'],
                        departureTime: $data['departure_time']
                    ),
                    'departure_time' => $data['departure_time'],
                    'arrival_time' => $data['arrival_time'],
                    'base_price' => $data['base_price'],
                    'status' => 'scheduled',
                    'trip_type' => 'special',
                ]);

                $generatedSeatCount = $this->tripSeatGenerationService->generateForTrip($trip);

                return $trip->load([
                    'route:id,code,from_location,to_location',
                    'bus:id,bus_type_id,name,license_plate',
                    'bus.busType:id,name,total_seats',
                ])->loadCount('tripSeats')->setAttribute('generated_seat_count', $generatedSeatCount);
            });

            return response()->json([
                'success' => true,
                'message' => 'Tạo chuyến xe thành công.',
                'data' => $trip,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Trip $trip): JsonResponse
    {
        $trip->load([
            'route:id,code,from_location,to_location,distance_km,estimated_duration_minutes',
            'bus:id,bus_type_id,name,license_plate',
            'bus.busType:id,name,total_seats,seat_layout',
            'tripSeats.seat:id,seat_number,seat_row,seat_column,floor,seat_type',
            'bookings:id,user_id,trip_id,booking_code,status,total_amount,expired_at,confirmed_at,cancelled_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết chuyến xe admin thành công.',
            'data' => $trip,
        ]);
    }

    public function cancel(Trip $trip): JsonResponse
    {
        try {
            $trip = DB::transaction(function () use ($trip) {
                $trip = Trip::query()
                    ->where('id', $trip->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($trip->status !== 'scheduled') {
                    abort(422, 'Chỉ có thể hủy chuyến đang ở trạng thái scheduled.');
                }

                $confirmedBookingExists = Booking::query()
                    ->where('trip_id', $trip->id)
                    ->where('status', 'confirmed')
                    ->exists();

                if ($confirmedBookingExists) {
                    abort(422, 'Không thể hủy chuyến đã có booking confirmed.');
                }

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
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);

                    BookingHistory::create([
                        'booking_id' => $booking->id,
                        'action' => 'booking_cancelled_by_trip_cancelled',
                        'old_status' => 'pending_payment',
                        'new_status' => 'cancelled',
                        'note' => 'Booking bị hủy do admin hủy chuyến xe.',
                        'metadata' => [
                            'trip_id' => $trip->id,
                            'cancelled_by' => 'admin',
                        ],
                    ]);
                }

                TripSeat::query()
                    ->where('trip_id', $trip->id)
                    ->where('status', 'available')
                    ->update([
                        'status' => 'blocked',
                    ]);

                $trip->update([
                    'status' => 'cancelled',
                ]);

                return $trip->fresh([
                    'route:id,code,from_location,to_location',
                    'bus:id,name,license_plate',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Hủy chuyến xe thành công.',
                'data' => $trip,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function ensureBusIsAvailable(
        int $busId,
        string $departureTime,
        string $arrivalTime
    ): void {
        $overlapExists = Trip::query()
            ->where('bus_id', $busId)
            ->whereIn('status', [
                'scheduled',
                'departed',
            ])
            ->where(function ($query) use ($departureTime, $arrivalTime) {
                $query
                    ->where('departure_time', '<', $arrivalTime)
                    ->where('arrival_time', '>', $departureTime);
            })
            ->exists();

        if ($overlapExists) {
            abort(422, 'Xe đã có chuyến khác trong khoảng thời gian này.');
        }
    }

    private function generateTripCode(int $routeId, string $departureTime): string
    {
        $prefix = 'TRIP-'.now()->format('Ymd').'-R'.$routeId;

        do {
            $code = $prefix.'-'.strtoupper(Str::random(5));
        } while (Trip::where('code', $code)->exists());

        return $code;
    }
}
