<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('trip_schedule_id')
                ->nullable()
                ->after('bus_id')
                ->constrained('trip_schedules')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('trip_type', 20)
                ->default('special')
                ->after('status'); // special | routine

            $table->index(['trip_schedule_id', 'departure_time']);
            $table->index('trip_type');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['trip_schedule_id']);
            $table->dropColumn(['trip_schedule_id', 'trip_type']);
        });
    }
};
