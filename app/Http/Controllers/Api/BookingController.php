<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\BookingItem;
use App\Models\Trip;
use App\Models\TripSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $booking = DB::transaction(function () use ($data) {
                $trip = Trip::query()
                    ->where('id', $data['trip_id'])
                    ->where('status', 'scheduled')
                    ->where('departure_time', '>', now())
                    ->first();

                if (!$trip) {
                    abort(422, 'Chuyến xe không hợp lệ hoặc đã khởi hành.');
                }

                $tripSeatIds = $data['trip_seat_ids'];

                /**
                 * lockForUpdate rất quan trọng.
                 * Nó khóa các dòng ghế đang được xử lý trong transaction.
                 * Nhờ đó tránh 2 người cùng đặt một ghế cùng lúc.
                 */
                $tripSeats = TripSeat::query()
                    ->where('trip_id', $trip->id)
                    ->whereIn('id', $tripSeatIds)
                    ->lockForUpdate()
                    ->get();

                if ($tripSeats->count() !== count($tripSeatIds)) {
                    abort(422, 'Một hoặc nhiều ghế không thuộc chuyến xe này.');
                }

                $unavailableSeat = $tripSeats->first(function (TripSeat $tripSeat) {
                    return $tripSeat->status !== 'available';
                });

                if ($unavailableSeat) {
                    abort(422, 'Ghế ' . $unavailableSeat->seat->seat_number . ' không còn trống.');
                }

                $totalAmount = $trip->base_price * $tripSeats->count();

                $booking = Booking::create([
                    'user_id' => $data['user_id'],
                    'trip_id' => $trip->id,
                    'booking_code' => $this->generateBookingCode(),
                    'status' => 'pending_payment',
                    'total_amount' => $totalAmount,
                    'expired_at' => now()->addMinutes(10),
                ]);

                foreach ($tripSeats as $tripSeat) {
                    $seat = $tripSeat->seat;

                    BookingItem::create([
                        'booking_id' => $booking->id,
                        'trip_seat_id' => $tripSeat->id,
                        'seat_number' => $seat->seat_number,
                        'price' => $trip->base_price,
                    ]);

                    $tripSeat->update([
                        'status' => 'reserved',
                        'locked_until' => $booking->expired_at,
                        'booking_id' => $booking->id,
                    ]);
                }

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'booking_created',
                    'old_status' => null,
                    'new_status' => 'pending_payment',
                    'note' => 'Khách hàng tạo booking và giữ ghế trong 10 phút.',
                    'metadata' => [
                        'trip_id' => $trip->id,
                        'trip_seat_ids' => $tripSeatIds,
                        'total_amount' => $totalAmount,
                    ],
                ]);

                return $booking->load([
                    'user:id,name,email,phone',
                    'trip:id,code,route_id,bus_id,departure_time,arrival_time,base_price,status',
                    'items:id,booking_id,trip_seat_id,seat_number,price',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Giữ ghế thành công. Vui lòng thanh toán trong 10 phút.',
                'data' => $booking,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load([
            'user:id,name,email,phone',
            'trip.route:id,code,from_location,to_location',
            'trip.bus:id,name,license_plate',
            'items:id,booking_id,trip_seat_id,seat_number,price',
            'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
            'payments:id,booking_id,payment_code,method,status,amount,paid_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết booking thành công.',
            'data' => $booking,
        ]);
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
