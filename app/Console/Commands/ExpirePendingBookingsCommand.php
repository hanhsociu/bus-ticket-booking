<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\TripSeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePendingBookingsCommand extends Command
{
    protected $signature = 'bookings:expire-pending';

    protected $description = 'Expire pending payment bookings and release reserved seats';

    public function handle(): int
    {
        $expiredBookings = Booking::query()
            ->where('status', 'pending_payment')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('Không có booking quá hạn.');

            return self::SUCCESS;
        }

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                $booking = Booking::query()
                    ->where('id', $booking->id)
                    ->lockForUpdate()
                    ->first();

                if (! $booking || $booking->status !== 'pending_payment') {
                    return;
                }

                TripSeat::query()
                    ->where('booking_id', $booking->id)
                    ->where('status', 'reserved')
                    ->update([
                        'status' => 'available',
                        'locked_until' => null,
                        'booking_id' => null,
                    ]);

                $booking->update([
                    'status' => 'expired',
                    'cancelled_at' => now(),
                ]);

                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'action' => 'booking_expired',
                    'old_status' => 'pending_payment',
                    'new_status' => 'expired',
                    'note' => 'Booking quá hạn thanh toán, hệ thống tự động nhả ghế.',
                    'metadata' => [
                        'expired_at' => $booking->expired_at,
                    ],
                ]);
            });
        }

        $this->info('Đã xử lý '.$expiredBookings->count().' booking quá hạn.');

        return self::SUCCESS;
    }
}
