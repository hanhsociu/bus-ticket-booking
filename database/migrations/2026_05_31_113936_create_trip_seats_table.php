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
        Schema::create('trip_seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trip_id')
                ->constrained('trips')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('seat_id')
                ->constrained('seats')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('status', [
                'available',
                'reserved',
                'booked',
                'blocked'
            ])->default('available');

            $table->dateTime('locked_until')->nullable();

            $table->unsignedBigInteger('booking_id')->nullable();

            $table->timestamps();

            $table->unique(['trip_id', 'seat_id']);

            $table->index(['trip_id', 'status']);
            $table->index('locked_until');
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_seats');
    }
};
