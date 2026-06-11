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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $booking = DB::transaction(function () use ($data, $request) {
                $trip = Trip::query()
                    ->where('id', $data['trip_id'])
                    ->where('status', 'scheduled')
                    ->where('departure_time', '>', now())
                    ->first();

                if (!$trip) {
                    abort(422, 'Chuyến xe không hợp lệ hoặc đã khởi hành.');
                }

                $tripSeatIds = $data['trip_seat_ids'];

                /*
                 * lockForUpdate rất quan trọng.
                 * Nó khóa các dòng ghế đang được xử lý trong transaction.
                 * Nhờ đó tránh 2 người cùng đặt một ghế cùng lúc.
                 */
                $tripSeats = TripSeat::query()
                    ->where('trip_id', $trip->id)
                    ->whereIn('id', $tripSeatIds)
                    ->with('seat')
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
                    'user_id' => $request->user()->id,
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
                        'user_id' => $request->user()->id,
                        'trip_id' => $trip->id,
                        'trip_seat_ids' => $tripSeatIds,
                        'total_amount' => $totalAmount,
                    ],
                ]);

                return $booking->load([
                    'user:id,name,email,phone',
                    'trip:id,code,route_id,bus_id,departure_time,arrival_time,base_price,status',
                    'trip.route:id,code,from_location,to_location',
                    'trip.bus:id,name,license_plate',
                    'items:id,booking_id,trip_seat_id,seat_number,price,checked_in_at,checked_in_by',
                    'items.checkedInBy:id,name,email',
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
            ], $this->getExceptionStatusCode($e));
        }
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        if ((int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem booking này.',
            ], 403);
        }

        $booking->load([
            'user:id,name,email,phone',
            'trip.route:id,code,from_location,to_location',
            'trip.bus:id,name,license_plate',
            'items:id,booking_id,trip_seat_id,seat_number,price,checked_in_at,checked_in_by',
            'items.checkedInBy:id,name,email',
            'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
            'payments:id,booking_id,payment_code,method,status,amount,paid_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết booking thành công.',
            'data' => $booking,
        ]);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'trip.route:id,code,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,trip_seat_id,seat_number,price,checked_in_at,checked_in_by',
                'items.checkedInBy:id,name,email',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách vé của tôi thành công.',
            'data' => $bookings,
        ]);
    }

    public function requestRefund(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ((int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền yêu cầu hoàn vé booking này.',
            ], 403);
        }

        try {
            $booking = DB::transaction(function () use ($booking, $request) {
                $booking = Booking::query()
                    ->where('id', $booking->id)
                    ->with(['trip'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $booking->user_id !== (int) $request->user()->id) {
                    abort(403, 'Bạn không có quyền yêu cầu hoàn vé booking này.');
                }

                if ($booking->status !== 'confirmed') {
                    abort(422, 'Chỉ booking đã thanh toán thành công mới được yêu cầu hoàn vé.');
                }

                if (!$booking->trip) {
                    abort(422, 'Booking không có thông tin chuyến xe.');
                }

                if ($booking->trip->status !== 'scheduled') {
                    abort(422, 'Chuyến xe không còn ở trạng thái scheduled, không thể yêu cầu hoàn vé.');
                }

                if ($booking->trip->departure_time <= now()) {
                    abort(422, 'Chuyến xe đã khởi hành hoặc đã quá giờ, không thể yêu cầu hoàn vé.');
                }

                $successPaymentExists = $booking->payments()
                    ->where('status', 'success')
                    ->exists();

                if (!$successPaymentExists) {
                    abort(422, 'Không tìm thấy thanh toán thành công cho booking này.');
                }

                $hasCheckedInItem = $booking->items()
                    ->whereNotNull('checked_in_at')
                    ->exists();

                if ($hasCheckedInItem) {
                    abort(422, 'Vé đã được check-in, không thể yêu cầu hoàn vé.');
                }

                $booking->update([
                    'status' => 'refund_requested',
                ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'refund_requested',
                    'old_status' => 'confirmed',
                    'new_status' => 'refund_requested',
                    'note' => $request->input('reason'),
                    'metadata' => [
                        'requested_by' => 'customer',
                        'user_id' => $request->user()->id,
                    ],
                ]);

                return $booking->fresh([
                    'user:id,name,email,phone',
                    'trip.route:id,code,from_location,to_location',
                    'trip.bus:id,name,license_plate',
                    'items:id,booking_id,trip_seat_id,seat_number,price,checked_in_at,checked_in_by',
                    'items.checkedInBy:id,name,email',
                    'payments:id,booking_id,payment_code,method,status,amount,paid_at',
                    'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Gửi yêu cầu hoàn vé thành công. Vui lòng chờ admin xử lý.',
                'data' => $booking,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $this->getExceptionStatusCode($e));
        }
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        if ((int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền hủy booking này.',
            ], 403);
        }

        try {
            $booking = DB::transaction(function () use ($booking, $request) {
                $booking = Booking::query()
                    ->where('id', $booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $booking->user_id !== (int) $request->user()->id) {
                    abort(403, 'Bạn không có quyền hủy booking này.');
                }

                if ($booking->status !== 'pending_payment') {
                    abort(422, 'Chỉ booking đang chờ thanh toán mới được hủy.');
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
                    'action' => 'booking_cancelled',
                    'old_status' => 'pending_payment',
                    'new_status' => 'cancelled',
                    'note' => 'Khách hàng hủy booking trước khi thanh toán.',
                    'metadata' => [
                        'user_id' => $request->user()->id,
                    ],
                ]);

                return $booking->fresh([
                    'user:id,name,email,phone',
                    'trip.route:id,code,from_location,to_location',
                    'trip.bus:id,name,license_plate',
                    'items:id,booking_id,trip_seat_id,seat_number,price,checked_in_at,checked_in_by',
                    'items.checkedInBy:id,name,email',
                    'payments:id,booking_id,payment_code,method,status,amount,paid_at',
                    'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Hủy booking thành công.',
                'data' => $booking,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $this->getExceptionStatusCode($e));
        }
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    private function getExceptionStatusCode(\Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return 422;
    }
}
