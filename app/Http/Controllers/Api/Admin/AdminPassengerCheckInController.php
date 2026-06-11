<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\BookingItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPassengerCheckInController extends Controller
{
    public function checkInBooking(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = DB::transaction(function () use ($request, $booking) {
                $booking = Booking::query()
                    ->where('id', $booking->id)
                    ->with(['items', 'trip'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureBookingCanCheckIn($booking);

                $uncheckedItems = $booking->items
                    ->whereNull('checked_in_at');

                if ($uncheckedItems->isEmpty()) {
                    abort(422, 'Booking này đã được check-in toàn bộ trước đó.');
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
                    'action' => 'booking_checked_in',
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'note' => $request->input('note') ?: 'Admin check-in toàn bộ booking.',
                    'metadata' => [
                        'checked_in_by' => $request->user()->id,
                        'checked_in_item_count' => $uncheckedItems->count(),
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
                'message' => 'Check-in toàn bộ booking thành công.',
                'data' => $booking,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function checkInItem(Request $request, BookingItem $bookingItem): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $bookingItem = DB::transaction(function () use ($request, $bookingItem) {
                $bookingItem = BookingItem::query()
                    ->where('id', $bookingItem->id)
                    ->with(['booking.trip', 'booking.user'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $booking = $bookingItem->booking;

                $this->ensureBookingCanCheckIn($booking);

                if ($bookingItem->checked_in_at !== null) {
                    abort(422, 'Ghế này đã được check-in trước đó.');
                }

                $bookingItem->update([
                    'checked_in_at' => now(),
                    'checked_in_by' => $request->user()->id,
                ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'booking_item_checked_in',
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'note' => $request->input('note') ?: 'Admin check-in một ghế trong booking.',
                    'metadata' => [
                        'checked_in_by' => $request->user()->id,
                        'booking_item_id' => $bookingItem->id,
                        'seat_number' => $bookingItem->seat_number,
                    ],
                ]);

                return $bookingItem->fresh([
                    'booking.user:id,name,email,phone',
                    'booking.trip.route:id,code,from_location,to_location',
                    'booking.trip.bus:id,name,license_plate',
                    'checkedInBy:id,name,email',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Check-in ghế thành công.',
                'data' => $bookingItem,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function undoCheckInItem(Request $request, BookingItem $bookingItem): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $bookingItem = DB::transaction(function () use ($request, $bookingItem) {
                $bookingItem = BookingItem::query()
                    ->where('id', $bookingItem->id)
                    ->with(['booking.trip'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $booking = $bookingItem->booking;

                $this->ensureBookingCanCheckIn($booking);

                if ($bookingItem->checked_in_at === null) {
                    abort(422, 'Ghế này chưa check-in nên không thể hoàn tác.');
                }

                $oldCheckedInAt = $bookingItem->checked_in_at;
                $oldCheckedInBy = $bookingItem->checked_in_by;

                $bookingItem->update([
                    'checked_in_at' => null,
                    'checked_in_by' => null,
                ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'booking_item_check_in_undone',
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'note' => $request->input('reason'),
                    'metadata' => [
                        'undone_by' => $request->user()->id,
                        'booking_item_id' => $bookingItem->id,
                        'seat_number' => $bookingItem->seat_number,
                        'old_checked_in_at' => $oldCheckedInAt,
                        'old_checked_in_by' => $oldCheckedInBy,
                    ],
                ]);

                return $bookingItem->fresh([
                    'booking.user:id,name,email,phone',
                    'booking.trip.route:id,code,from_location,to_location',
                    'booking.trip.bus:id,name,license_plate',
                    'checkedInBy:id,name,email',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Hoàn tác check-in ghế thành công.',
                'data' => $bookingItem,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function ensureBookingCanCheckIn(Booking $booking): void
    {
        if ($booking->status !== 'confirmed') {
            abort(422, 'Chỉ booking đã thanh toán thành công mới được check-in.');
        }

        if (! $booking->trip) {
            abort(422, 'Booking không có thông tin chuyến xe.');
        }

        if (! in_array($booking->trip->status, ['scheduled', 'departed'], true)) {
            abort(422, 'Chỉ có thể check-in cho chuyến đang scheduled hoặc departed.');
        }
    }
}
