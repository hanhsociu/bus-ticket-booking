<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Payment;
use App\Models\TripSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()
            ->whereIn('status', ['refund_requested', 'refunded'])
            ->with([
                'user:id,name,email,phone',
                'trip:id,code,route_id,bus_id,departure_time,arrival_time,status',
                'trip.route:id,code,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,trip_seat_id,seat_number,price',
                'payments:id,booking_id,payment_code,method,status,amount,paid_at',
                'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
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

        $refunds = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách yêu cầu hoàn vé thành công.',
            'data' => $refunds,
        ]);
    }

    public function approve(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = DB::transaction(function () use ($request, $booking) {
                $booking = Booking::query()
                    ->where('id', $booking->id)
                    ->with('trip')
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'refund_requested') {
                    abort(422, 'Chỉ có thể duyệt booking đang ở trạng thái refund_requested.');
                }

                $payment = Payment::query()
                    ->where('booking_id', $booking->id)
                    ->where('status', 'success')
                    ->lockForUpdate()
                    ->latest()
                    ->first();

                if (!$payment) {
                    abort(422, 'Không tìm thấy payment success để hoàn tiền.');
                }

                $oldStatus = $booking->status;

                $payment->update([
                    'status' => 'refunded',
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        [
                            'refund' => [
                                'source' => 'manual_admin',
                                'admin_id' => $request->user()->id,
                                'note' => $request->input('note'),
                                'refunded_at' => now()->toDateTimeString(),
                            ],
                        ]
                    ),
                ]);

                /*
                 * Vì chuyến chưa khởi hành mới cho refund,
                 * ta mở lại ghế để khách khác có thể đặt.
                 */
                TripSeat::query()
                    ->where('booking_id', $booking->id)
                    ->where('status', 'booked')
                    ->update([
                        'status' => 'available',
                        'locked_until' => null,
                        'booking_id' => null,
                    ]);

                $booking->update([
                    'status' => 'refunded',
                    'cancelled_at' => now(),
                ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'refund_approved',
                    'old_status' => $oldStatus,
                    'new_status' => 'refunded',
                    'note' => $request->input('note') ?: 'Admin duyệt hoàn vé.',
                    'metadata' => [
                        'approved_by' => 'admin',
                        'admin_id' => $request->user()->id,
                        'payment_id' => $payment->id,
                        'payment_code' => $payment->payment_code,
                        'amount' => $payment->amount,
                    ],
                ]);

                return $booking->fresh([
                    'user:id,name,email,phone',
                    'trip.route:id,code,from_location,to_location',
                    'trip.bus:id,name,license_plate',
                    'items:id,booking_id,trip_seat_id,seat_number,price',
                    'payments:id,booking_id,payment_code,method,status,amount,paid_at,gateway_response',
                    'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Duyệt hoàn vé thành công.',
                'data' => $booking,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $booking = DB::transaction(function () use ($request, $booking) {
                $booking = Booking::query()
                    ->where('id', $booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'refund_requested') {
                    abort(422, 'Chỉ có thể từ chối booking đang ở trạng thái refund_requested.');
                }

                $booking->update([
                    'status' => 'confirmed',
                ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'refund_rejected',
                    'old_status' => 'refund_requested',
                    'new_status' => 'confirmed',
                    'note' => $request->input('reason'),
                    'metadata' => [
                        'rejected_by' => 'admin',
                        'admin_id' => $request->user()->id,
                    ],
                ]);

                return $booking->fresh([
                    'user:id,name,email,phone',
                    'trip.route:id,code,from_location,to_location',
                    'trip.bus:id,name,license_plate',
                    'items:id,booking_id,trip_seat_id,seat_number,price',
                    'payments:id,booking_id,payment_code,method,status,amount,paid_at',
                    'histories:id,booking_id,action,old_status,new_status,note,metadata,created_at',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Từ chối hoàn vé thành công. Booking quay lại trạng thái confirmed.',
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
