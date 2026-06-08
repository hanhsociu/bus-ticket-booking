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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('type', [
                'email',
                'sms',
                'system'
            ])->default('email');

            $table->string('title');
            $table->text('message');

            $table->enum('status', [
                'pending',
                'sent',
                'failed'
            ])->default('pending');

            $table->dateTime('sent_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('booking_id');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
