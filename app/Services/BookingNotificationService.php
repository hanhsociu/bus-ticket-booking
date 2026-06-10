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
                'type' => 'email',
                'title' => 'Xác nhận vé xe ' . $booking->booking_code,
                'message' => 'Email xác nhận vé đã được gửi thành công.',
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => [
                    'email' => $booking->user->email,
                    'booking_code' => $booking->booking_code,
                ],
            ]);
        } catch (\Throwable $e) {
            Notification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => 'email',
                'title' => 'Xác nhận vé xe ' . $booking->booking_code,
                'message' => 'Gửi email xác nhận vé thất bại.',
                'status' => 'failed',
                'sent_at' => null,
                'metadata' => [
                    'email' => $booking->user->email,
                    'booking_code' => $booking->booking_code,
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }
}
