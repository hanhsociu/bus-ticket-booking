<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE bookings
            MODIFY COLUMN status ENUM(
                'pending_payment',
                'confirmed',
                'cancelled',
                'expired',
                'failed',
                'refund_requested',
                'refunded'
            ) NOT NULL DEFAULT 'pending_payment'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE bookings
            MODIFY COLUMN status ENUM(
                'pending_payment',
                'confirmed',
                'cancelled',
                'expired',
                'failed'
            ) NOT NULL DEFAULT 'pending_payment'
        ");
    }
};
