<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingConfirmedNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $bookingId
    ) {}

    public function handle(BookingNotificationService $bookingNotificationService): void
    {
        $booking = Booking::query()
            ->with([
                'user:id,name,email,phone',
                'trip.route:id,from_location,to_location',
                'trip.bus:id,name,license_plate',
                'items:id,booking_id,seat_number,price',
                'notifications:id,booking_id,type,status',
            ])
            ->find($this->bookingId);

        if (! $booking) {
            return;
        }

        if ($booking->status !== 'confirmed') {
            return;
        }

        $alreadySent = $booking->notifications()
            ->where('type', 'booking_confirmed')
            ->where('status', 'sent')
            ->exists();

        if ($alreadySent) {
            return;
        }

        $bookingNotificationService->sendBookingConfirmedEmail($booking);
    }
}
