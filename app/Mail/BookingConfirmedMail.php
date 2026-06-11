<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Xác nhận vé xe - '.$this->booking->booking_code)
            ->view('emails.bookings.confirmed')
            ->with([
                'booking' => $this->booking,
            ]);
    }
}
