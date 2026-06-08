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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('payment_code')->unique();

            $table->enum('method', [
                'cash',
                'bank_transfer',
                'vnpay',
                'momo',
                'zalopay'
            ])->default('bank_transfer');

            $table->enum('status', [
                'pending',
                'success',
                'failed',
                'refunded'
            ])->default('pending');

            $table->decimal('amount', 12, 2);

            $table->string('transaction_id')->nullable();

            $table->json('gateway_response')->nullable();

            $table->dateTime('paid_at')->nullable();

            $table->timestamps();

            $table->index('booking_id');
            $table->index('status');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
