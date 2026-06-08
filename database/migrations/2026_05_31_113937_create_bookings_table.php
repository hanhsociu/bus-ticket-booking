<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('trip_id')
                ->constrained('trips')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('booking_code')->unique();

            $table->enum('status', [
                'pending_payment',
                'confirmed',
                'cancelled',
                'expired',
                'failed'
            ])->default('pending_payment');

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->dateTime('expired_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('trip_id');
            $table->index('status');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
