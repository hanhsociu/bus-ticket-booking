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

        /**
         * Nếu booking đã có payment pending và đã lưu checkoutUrl,
         * trả lại link cũ để tránh tạo trùng orderCode trên PayOS.
         */
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

        /**
         * PayOS orderCode nên là số.
         * Dùng payment id để webhook/return tìm lại payment dễ dàng.
         */
        $orderCode = (int) $payment->id;

        /**
         * PayOS description nên ngắn, không dấu, không ký tự đặc biệt.
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
            /**
             * Nếu PayOS báo orderCode đã tồn tại nhưng local chưa lưu checkoutUrl,
             * ta báo rõ để dev biết tạo booking mới hoặc clean payment cũ.
             */
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
            /**
             * Nếu return báo thành công thì xác nhận luôn.
             * Thực tế webhook vẫn là nguồn đáng tin hơn, nhưng return giúp local/dev test dễ hơn.
             */
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

            $gatewayResponse = $this->objectToArray($verifiedData);

            if ($code !== '00') {
                $failedPayment = $this->bookingPaymentService->markPaymentFailed(
                    payment: $payment,
                    gatewayResponse: $gatewayResponse,
                    note: 'PayOS webhook báo thanh toán không thành công.'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook PayOS đã ghi nhận thanh toán thất bại.',
                    'data' => $failedPayment,
                ]);
            }

            $confirmedPayment = $this->bookingPaymentService->confirmPayment(
                payment: $payment,
                paidAmount: (int) $amount,
                gatewayResponse: $gatewayResponse,
                action: 'payment_success_webhook',
                note: 'PayOS webhook báo thanh toán thành công, hệ thống xác nhận vé.'
            );

            return response()->json([
                'success' => true,
                'message' => 'Webhook PayOS xử lý thanh toán thành công.',
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
                    'message' => 'Giả lập thanh toán thành công trong môi trường local.',
                ],
                action: 'payment_success_fake',
                note: 'Giả lập thanh toán thành công trong môi trường local.'
            );

            return response()->json([
                'success' => true,
                'message' => 'Giả lập thanh toán thành công.',
                'data' => $confirmedPayment,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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

    private function objectToArray(mixed $data): array
    {
        return json_decode(json_encode($data), true) ?? [];
    }
}
