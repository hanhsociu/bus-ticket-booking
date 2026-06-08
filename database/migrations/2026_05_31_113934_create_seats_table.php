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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bus_type_id')
                ->constrained('bus_types')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('seat_number');

            $table->unsignedTinyInteger('seat_row')->nullable();
            $table->unsignedTinyInteger('seat_column')->nullable();
            $table->unsignedTinyInteger('floor')->default(1);

            $table->enum('seat_type', [
                'normal',
                'vip',
                'sleeper'
            ])->default('normal');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['bus_type_id', 'seat_number']);

            $table->index('bus_type_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
