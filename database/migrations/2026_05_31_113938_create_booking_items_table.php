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
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('trip_seat_id')
                ->constrained('trip_seats')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('seat_number');

            $table->decimal('price', 12, 2);

            $table->timestamps();

            $table->unique(['booking_id', 'trip_seat_id']);

            $table->index('booking_id');
            $table->index('trip_seat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
