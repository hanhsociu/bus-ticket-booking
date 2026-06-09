<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Payment;
use App\Models\TripSeat;
use Illuminate\Support\Facades\DB;

class BookingPaymentService
{
    public function confirmPayment(
        Payment $payment,
        ?int $paidAmount = null,
        array $gatewayResponse = [],
        string $action = 'payment_success',
        string $note = 'Thanh toán thành công, hệ thống xác nhận vé.'
    ): Payment {
        return DB::transaction(function () use ($payment, $paidAmount, $gatewayResponse, $action, $note) {
            $payment = Payment::query()
                ->where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $booking = Booking::query()
                ->where('id', $payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            /**
             * Idempotency:
             * Nếu webhook/return bị gọi lại nhiều lần thì không xử lý trùng.
             */
            if ($payment->status === 'success' && $booking->status === 'confirmed') {
                return $payment->load(['booking.items']);
            }

            if ($booking->status !== 'pending_payment') {
                throw new \RuntimeException('Booking không còn ở trạng thái chờ thanh toán.');
            }

            if ($booking->expired_at && $booking->expired_at->isPast()) {
                throw new \RuntimeException('Booking đã quá hạn thanh toán.');
            }

            if ($paidAmount !== null && (int) $payment->amount !== (int) $paidAmount) {
                throw new \RuntimeException('Số tiền thanh toán không khớp.');
            }

            $reservedSeatCount = TripSeat::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'reserved')
                ->count();

            if ($reservedSeatCount <= 0) {
                throw new \RuntimeException('Không tìm thấy ghế đang được giữ cho booking này.');
            }

            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
                'gateway_response' => !empty($gatewayResponse)
                    ? $gatewayResponse
                    : $payment->gateway_response,
            ]);

            TripSeat::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'reserved')
                ->update([
                    'status' => 'booked',
                    'locked_until' => null,
                ]);

            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            BookingHistory::create([
                'booking_id' => $booking->id,
                'action' => $action,
                'old_status' => 'pending_payment',
                'new_status' => 'confirmed',
                'note' => $note,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'amount' => $payment->amount,
                    'gateway_response' => $gatewayResponse,
                ],
            ]);

            return $payment->fresh(['booking.items']);
        });
    }

    public function markPaymentFailed(
        Payment $payment,
        array $gatewayResponse = [],
        string $note = 'Thanh toán thất bại.'
    ): Payment {
        return DB::transaction(function () use ($payment, $gatewayResponse, $note) {
            $payment = Payment::query()
                ->where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $booking = Booking::query()
                ->where('id', $payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'success' || $booking->status === 'confirmed') {
                return $payment->load(['booking.items']);
            }

            $payment->update([
                'status' => 'failed',
                'gateway_response' => !empty($gatewayResponse)
                    ? $gatewayResponse
                    : $payment->gateway_response,
            ]);

            BookingHistory::create([
                'booking_id' => $booking->id,
                'action' => 'payment_failed',
                'old_status' => $booking->status,
                'new_status' => $booking->status,
                'note' => $note,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'gateway_response' => $gatewayResponse,
                ],
            ]);

            return $payment->fresh(['booking.items']);
        });
    }
}
