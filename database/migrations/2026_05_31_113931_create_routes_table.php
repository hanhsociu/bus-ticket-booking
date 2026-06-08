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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('from_location');
            $table->string('to_location');

            $table->unsignedInteger('distance_km')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['from_location', 'to_location']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
