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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bus_type_id')
                ->constrained('bus_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('license_plate')->unique();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('bus_type_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
