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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('route_id')
                ->constrained('routes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('bus_id')
                ->constrained('buses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('code')->unique();

            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');

            $table->decimal('base_price', 12, 2);

            $table->enum('status', [
                'scheduled',
                'departed',
                'completed',
                'cancelled'
            ])->default('scheduled');

            $table->timestamps();

            $table->index(['route_id', 'departure_time']);
            $table->index(['bus_id', 'departure_time']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
