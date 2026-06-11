<?php

namespace App\Services;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;

class BookingNotificationService
{
    public function sendBookingConfirmedEmail(Booking $booking): void
    {
        $booking->loadMissing([
            'user:id,name,email,phone',
            'trip.route:id,from_location,to_location',
            'trip.bus:id,name,license_plate',
            'items:id,booking_id,seat_number,price',
        ]);

        try {
            Mail::to($booking->user->email)
                ->send(new BookingConfirmedMail($booking));

            Notification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => 'booking_confirmed',
                'title' => 'Xác nhận vé xe '.$booking->booking_code,
                'message' => 'Vé xe của bạn đã được xác nhận. Email xác nhận đã được gửi thành công.',
                'status' => 'sent',
                'sent_at' => now(),
                'read_at' => null,
                'metadata' => [
                    'email' => $booking->user->email,
                    'booking_code' => $booking->booking_code,
                ],
            ]);
        } catch (\Throwable $e) {
            Notification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => 'booking_confirmed_email_failed',
                'title' => 'Xác nhận vé xe '.$booking->booking_code,
                'message' => 'Vé xe đã được xác nhận nhưng gửi email thất bại.',
                'status' => 'failed',
                'sent_at' => null,
                'read_at' => null,
                'metadata' => [
                    'email' => $booking->user->email,
                    'booking_code' => $booking->booking_code,
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function sendRefundApprovedNotification(Booking $booking, ?string $note = null): void
    {
        $booking->loadMissing([
            'user:id,name,email,phone',
            'trip.route:id,from_location,to_location',
            'trip.bus:id,name,license_plate',
        ]);

        Notification::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'type' => 'refund_approved',
            'title' => 'Yêu cầu hoàn vé đã được duyệt',
            'message' => 'Yêu cầu hoàn vé cho mã booking '.$booking->booking_code.' đã được admin duyệt.',
            'status' => 'sent',
            'sent_at' => now(),
            'read_at' => null,
            'metadata' => [
                'booking_code' => $booking->booking_code,
                'booking_status' => $booking->status,
                'note' => $note,
            ],
        ]);
    }

    public function sendRefundRejectedNotification(Booking $booking, string $reason): void
    {
        $booking->loadMissing([
            'user:id,name,email,phone',
            'trip.route:id,from_location,to_location',
            'trip.bus:id,name,license_plate',
        ]);

        Notification::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'type' => 'refund_rejected',
            'title' => 'Yêu cầu hoàn vé bị từ chối',
            'message' => 'Yêu cầu hoàn vé cho mã booking '.$booking->booking_code.' đã bị từ chối.',
            'status' => 'sent',
            'sent_at' => now(),
            'read_at' => null,
            'metadata' => [
                'booking_code' => $booking->booking_code,
                'booking_status' => $booking->status,
                'reason' => $reason,
            ],
        ]);
    }
}
