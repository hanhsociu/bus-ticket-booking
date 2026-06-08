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
        Schema::create('booking_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('action');

            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();

            $table->text('note')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('booking_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_histories');
    }
};
