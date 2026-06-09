<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Payment;
use App\Models\TripSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PayOS\PayOS;

class PayOSPaymentController extends Controller
{
    private function payOS(): PayOS
    {
        return new PayOS(
            clientId: config('services.payos.client_id'),
            apiKey: config('services.payos.api_key'),
            checksumKey: config('services.payos.checksum_key')
        );
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
        ]);

        $booking = Booking::query()
            ->with(['items', 'trip.route'])
            ->where('id', $data['booking_id'])
            ->firstOrFail();

        if ($booking->status !== 'pending_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Booking không còn ở trạng thái chờ thanh toán.',
            ], 422);
        }

        if ($booking->expired_at && $booking->expired_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking đã quá hạn thanh toán. Vui lòng đặt lại ghế.',
            ], 422);
        }

        $amount = (int) $booking->total_amount;

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Số tiền thanh toán không hợp lệ.',
            ], 422);
        }

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$payment) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'payment_code' => $this->generatePaymentCode(),
                'method' => 'bank_transfer',
                'status' => 'pending',
                'amount' => $amount,
            ]);
        }

        /*
         * PayOS orderCode nên là số.
         * Dùng payment id để khi webhook về ta tìm lại payment dễ dàng.
         */
        $orderCode = (int) $payment->id;

        /*
         * PayOS description nên ngắn, không dấu, không ký tự đặc biệt.
         * Để an toàn ta dùng BUS + booking_id.
         */
        $description = 'BUS' . $booking->id;

        $paymentData = [
            'orderCode' => $orderCode,
            'amount' => $amount,
            'description' => $description,
            'items' => [
                [
                    'name' => 'Ve xe ' . $booking->booking_code,
                    'quantity' => $booking->items->count(),
                    'price' => $amount,
                ],
            ],
            'returnUrl' => config('services.payos.return_url') . '?booking_id=' . $booking->id,
            'cancelUrl' => config('services.payos.cancel_url') . '?booking_id=' . $booking->id,
        ];

        try {
            $paymentLink = $this->payOS()->paymentRequests->create($paymentData);

            $paymentLinkData = json_decode(json_encode($paymentLink), true);

            $payment->update([
                'gateway_response' => $paymentLinkData,
                'transaction_id' => $paymentLink->paymentLinkId ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo link thanh toán PayOS thành công.',
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'payment_id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'amount' => $amount,
                    'checkout_url' => $paymentLink->checkoutUrl ?? null,
                    'qr_code' => $paymentLink->qrCode ?? null,
                    'payment_link_id' => $paymentLink->paymentLinkId ?? null,
                    'expired_at' => $booking->expired_at,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tạo được link thanh toán PayOS.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function return(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'PayOS đã chuyển hướng về hệ thống. Trạng thái chính thức sẽ được xử lý qua webhook.',
            'data' => $request->query(),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Người dùng đã hủy thanh toán PayOS.',
            'data' => $request->query(),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            /*
             * PayOS webhook phải verify trước khi tin dữ liệu.
             * Theo SDK, verify() sẽ trả về WebhookData object nếu hợp lệ.
             */
            $verifiedData = $this->payOS()->webhooks->verify($request->all());

            $orderCode = $verifiedData->orderCode ?? null;
            $amount = $verifiedData->amount ?? null;
            $code = $verifiedData->code ?? null;

            if (!$orderCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook thiếu orderCode.',
                ], 400);
            }

            $payment = Payment::query()
                ->where('id', $orderCode)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy payment tương ứng.',
                ], 404);
            }

            DB::transaction(function () use ($payment, $amount, $code, $verifiedData) {
                $payment = Payment::query()
                    ->where('id', $payment->id)
                    ->lockForUpdate()
                    ->first();

                $booking = Booking::query()
                    ->where('id', $payment->booking_id)
                    ->lockForUpdate()
                    ->first();

                if (!$booking) {
                    abort(404, 'Booking không tồn tại.');
                }

                /*
                 * Idempotency:
                 * Nếu webhook gửi lại nhiều lần thì không xử lý trùng.
                 */
                if ($payment->status === 'success' && $booking->status === 'confirmed') {
                    return;
                }

                if ((int) $payment->amount !== (int) $amount) {
                    abort(422, 'Số tiền thanh toán không khớp.');
                }

                if ($booking->status !== 'pending_payment') {
                    abort(422, 'Booking không còn ở trạng thái chờ thanh toán.');
                }

                if ($booking->expired_at && $booking->expired_at->isPast()) {
                    abort(422, 'Booking đã quá hạn thanh toán.');
                }

                /*
                 * PayOS thường trả code "00" cho giao dịch thành công.
                 */
                if ($code !== '00') {
                    $payment->update([
                        'status' => 'failed',
                        'gateway_response' => json_decode(json_encode($verifiedData), true),
                    ]);

                    BookingHistory::create([
                        'booking_id' => $booking->id,
                        'action' => 'payment_failed',
                        'old_status' => $booking->status,
                        'new_status' => $booking->status,
                        'note' => 'Thanh toán PayOS không thành công.',
                        'metadata' => [
                            'payment_id' => $payment->id,
                            'payos_code' => $code,
                        ],
                    ]);

                    return;
                }

                $payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'gateway_response' => json_decode(json_encode($verifiedData), true),
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
                    'action' => 'payment_success',
                    'old_status' => 'pending_payment',
                    'new_status' => 'confirmed',
                    'note' => 'Thanh toán PayOS thành công, hệ thống xác nhận vé.',
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'amount' => $amount,
                        'payos_order_code' => $payment->id,
                    ],
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Webhook PayOS xử lý thành công.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook PayOS không hợp lệ hoặc xử lý thất bại.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    private function generatePaymentCode(): string
    {
        do {
            $code = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Payment::where('payment_code', $code)->exists());

        return $code;
    }
}
