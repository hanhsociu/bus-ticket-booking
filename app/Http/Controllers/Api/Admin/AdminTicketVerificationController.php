<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\BookingItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTicketVerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $booking = Booking::query()
            ->where('booking_code', $data['code'])
            ->with([
                'user:id,name,email,phone',
                'trip:id,code,route_id,bus_id,departure_time,arrival_time,status,base_price',
                'trip.route:id,code,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,trip_seat_id,seat_number,price,checked_in_at,checked_in_by',
                'items.checkedInBy:id,name,email',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
                'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
            ])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy vé với mã booking này.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kiểm tra vé thành công.',
            'data' => [
                'booking' => $this->formatTicket($booking),
                'verification' => $this->verificationResult($booking),
            ],
        ]);
    }

    public function checkInByCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = DB::transaction(function () use ($data, $request) {
                $booking = Booking::query()
                    ->where('booking_code', $data['code'])
                    ->with([
                        'user',
                        'trip.route',
                        'trip.bus',
                        'items.checkedInBy',
                        'payments',
                    ])
                    ->lockForUpdate()
                    ->first();

                if (!$booking) {
                    abort(404, 'Không tìm thấy vé với mã booking này.');
                }

                $this->ensureTicketCanCheckIn($booking);

                $uncheckedItems = $booking->items
                    ->whereNull('checked_in_at');

                if ($uncheckedItems->isEmpty()) {
                    abort(422, 'Vé này đã được check-in toàn bộ trước đó.');
                }

                BookingItem::query()
                    ->where('booking_id', $booking->id)
                    ->whereNull('checked_in_at')
                    ->update([
                        'checked_in_at' => now(),
                        'checked_in_by' => $request->user()->id,
                    ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'ticket_checked_in_by_code',
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'note' => $data['note'] ?? 'Admin check-in vé bằng mã booking.',
                    'metadata' => [
                        'booking_code' => $booking->booking_code,
                        'checked_in_by' => $request->user()->id,
                        'checked_in_item_count' => $uncheckedItems->count(),
                    ],
                ]);

                return $booking->fresh([
                    'user:id,name,email,phone',
                    'trip:id,code,route_id,bus_id,departure_time,arrival_time,status,base_price',
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
                'message' => 'Check-in vé bằng mã booking thành công.',
                'data' => [
                    'booking' => $this->formatTicket($booking),
                    'verification' => $this->verificationResult($booking),
                ],
            ]);
        } catch (\Throwable $e) {
            $statusCode = (int) $e->getCode();

            if (!in_array($statusCode, [404, 422], true)) {
                $statusCode = 422;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    private function ensureTicketCanCheckIn(Booking $booking): void
    {
        if ($booking->status !== 'confirmed') {
            abort(422, 'Chỉ vé đã thanh toán thành công mới được check-in.');
        }

        if (!$booking->trip) {
            abort(422, 'Vé không có thông tin chuyến xe.');
        }

        if (in_array($booking->trip->status, ['cancelled', 'completed'], true)) {
            abort(422, 'Chuyến xe đã hủy hoặc đã hoàn thành, không thể check-in.');
        }

        if (!in_array($booking->trip->status, ['scheduled', 'departed'], true)) {
            abort(422, 'Trạng thái chuyến xe không cho phép check-in.');
        }

        $hasSuccessfulPayment = $booking->payments
            ->where('status', 'success')
            ->isNotEmpty();

        if (!$hasSuccessfulPayment) {
            abort(422, 'Vé chưa có thanh toán thành công.');
        }
    }

    private function verificationResult(Booking $booking): array
    {
        $isConfirmed = $booking->status === 'confirmed';

        $hasSuccessfulPayment = $booking->payments
            ->where('status', 'success')
            ->isNotEmpty();

        $totalSeatCount = $booking->items->count();

        $checkedInCount = $booking->items
            ->whereNotNull('checked_in_at')
            ->count();

        $isFullyCheckedIn = $totalSeatCount > 0 && $checkedInCount === $totalSeatCount;

        $canCheckIn = $isConfirmed
            && $hasSuccessfulPayment
            && $booking->trip
            && in_array($booking->trip->status, ['scheduled', 'departed'], true)
            && !$isFullyCheckedIn;

        $reason = null;

        if ($booking->status !== 'confirmed') {
            $reason = 'Vé không ở trạng thái confirmed.';
        } elseif (!$hasSuccessfulPayment) {
            $reason = 'Vé chưa có thanh toán thành công.';
        } elseif (!$booking->trip) {
            $reason = 'Không có thông tin chuyến xe.';
        } elseif (!in_array($booking->trip->status, ['scheduled', 'departed'], true)) {
            $reason = 'Trạng thái chuyến xe không cho phép check-in.';
        } elseif ($isFullyCheckedIn) {
            $reason = 'Vé đã được check-in toàn bộ.';
        }

        return [
            'is_valid_ticket' => $isConfirmed && $hasSuccessfulPayment,
            'can_check_in' => $canCheckIn,
            'reason' => $reason,
            'total_seat_count' => $totalSeatCount,
            'checked_in_count' => $checkedInCount,
            'is_fully_checked_in' => $isFullyCheckedIn,
        ];
    }

    private function formatTicket(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'status' => $booking->status,
            'total_amount' => (float) $booking->total_amount,
            'created_at' => $booking->created_at,
            'confirmed_at' => $booking->confirmed_at,
            'customer' => $booking->user,
            'trip' => $booking->trip,
            'seats' => $booking->items->map(function ($item) {
                return [
                    'booking_item_id' => $item->id,
                    'seat_number' => $item->seat_number,
                    'price' => (float) $item->price,
                    'checked_in_at' => $item->checked_in_at,
                    'checked_in_by' => $item->checkedInBy,
                    'is_checked_in' => $item->checked_in_at !== null,
                ];
            })->values(),
            'payments' => $booking->payments,
            'histories' => $booking->histories,
        ];
    }
}
