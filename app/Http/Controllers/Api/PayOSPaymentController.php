<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PayOS\PayOS;

class PayOSPaymentController extends Controller
{
    public function __construct(
        private readonly BookingPaymentService $bookingPaymentService
    ) {}

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

        if ((int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thanh toán booking này.',
            ], 403);
        }

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

        if ($payment && !empty($payment->gateway_response['checkoutUrl'])) {
            return response()->json([
                'success' => true,
                'message' => 'Booking đã có link thanh toán PayOS. Trả về link hiện có.',
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'payment_id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'amount' => (int) $payment->amount,
                    'checkout_url' => $payment->gateway_response['checkoutUrl'] ?? null,
                    'qr_code' => $payment->gateway_response['qrCode'] ?? null,
                    'payment_link_id' => $payment->gateway_response['paymentLinkId'] ?? null,
                    'expired_at' => $booking->expired_at,
                ],
            ]);
        }

        if (!$payment) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'payment_code' => $this->generatePaymentCode(),
                'method' => 'bank_transfer',
                'status' => 'pending',
                'amount' => $amount,
            ]);
        }

        $orderCode = (int) $payment->id;
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

            $paymentLinkData = $this->objectToArray($paymentLink);

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
        $orderCode = $request->integer('orderCode');
        $code = $request->query('code');
        $status = strtoupper((string) $request->query('status'));

        if (!$orderCode) {
            return response()->json([
                'success' => true,
                'message' => 'PayOS đã chuyển hướng về hệ thống nhưng chưa có orderCode.',
                'data' => $request->query(),
            ]);
        }

        $payment = Payment::query()
            ->where('id', $orderCode)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy payment tương ứng với orderCode.',
                'data' => $request->query(),
            ], 404);
        }

        try {
            if ($code === '00' || $status === 'PAID') {
                $confirmedPayment = $this->bookingPaymentService->confirmPayment(
                    payment: $payment,
                    paidAmount: (int) $payment->amount,
                    gatewayResponse: [
                        'source' => 'payos_return',
                        'query' => $request->query(),
                    ],
                    action: 'payment_success_return',
                    note: 'PayOS return báo thanh toán thành công, hệ thống xác nhận vé.'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán PayOS thành công qua return URL.',
                    'data' => $confirmedPayment,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'PayOS đã chuyển hướng về hệ thống. Trạng thái chính thức sẽ được xử lý qua webhook.',
                'data' => $request->query(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không xử lý được PayOS return.',
                'error' => $e->getMessage(),
                'data' => $request->query(),
            ], 422);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Người dùng đã hủy thanh toán PayOS. Booking vẫn được giữ cho đến khi hết hạn.',
            'data' => $request->query(),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $verifiedData = $this->payOS()->webhooks->verify($request->all());
            $data = $this->objectToArray($verifiedData);

            $orderCode = (int) ($data['orderCode'] ?? 0);
            $amount = (int) ($data['amount'] ?? 0);
            $code = (string) ($data['code'] ?? '');

            if (!$orderCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook không có orderCode.',
                ], 400);
            }

            $payment = Payment::query()
                ->where('id', $orderCode)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => true,
                    'message' => 'Không tìm thấy payment, bỏ qua webhook.',
                ]);
            }

            if ($amount > 0 && $amount !== (int) $payment->amount) {
                $this->bookingPaymentService->markPaymentFailed(
                    payment: $payment,
                    gatewayResponse: [
                        'source' => 'payos_webhook',
                        'payload' => $request->all(),
                        'verified_data' => $data,
                        'reason' => 'amount_mismatch',
                    ],
                    note: 'PayOS webhook amount không khớp.'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Amount không khớp, đã đánh dấu payment failed.',
                ]);
            }

            if ($code !== '00') {
                $failedPayment = $this->bookingPaymentService->markPaymentFailed(
                    payment: $payment,
                    gatewayResponse: [
                        'source' => 'payos_webhook',
                        'payload' => $request->all(),
                        'verified_data' => $data,
                    ],
                    note: 'PayOS webhook báo thanh toán thất bại.'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'PayOS webhook báo thanh toán thất bại.',
                    'data' => $failedPayment,
                ]);
            }

            $confirmedPayment = $this->bookingPaymentService->confirmPayment(
                payment: $payment,
                paidAmount: $amount,
                gatewayResponse: [
                    'source' => 'payos_webhook',
                    'payload' => $request->all(),
                    'verified_data' => $data,
                ],
                action: 'payment_success_webhook',
                note: 'PayOS webhook báo thanh toán thành công, hệ thống xác nhận vé.'
            );

            return response()->json([
                'success' => true,
                'message' => 'Xử lý PayOS webhook thành công.',
                'data' => $confirmedPayment,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook PayOS không hợp lệ hoặc xử lý thất bại.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function fakeSuccess(Payment $payment): JsonResponse
    {
        try {
            $confirmedPayment = $this->bookingPaymentService->confirmPayment(
                payment: $payment,
                paidAmount: (int) $payment->amount,
                gatewayResponse: [
                    'source' => 'fake_success',
                    'note' => 'Admin giả lập thanh toán thành công trong môi trường dev.',
                ],
                action: 'payment_success_fake',
                note: 'Admin giả lập thanh toán thành công, hệ thống xác nhận vé.'
            );

            return response()->json([
                'success' => true,
                'message' => 'Fake thanh toán thành công.',
                'data' => $confirmedPayment,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể fake thanh toán thành công.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    private function generatePaymentCode(): string
    {
        do {
            $code = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Payment::where('payment_code', $code)->exists());

        return $code;
    }

    private function objectToArray(mixed $value): array
    {
        return json_decode(json_encode($value), true) ?? [];
    }
}
