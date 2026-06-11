<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_schedules', function (Blueprint $table) {
            $table->foreignId('route_id')
                ->after('id')
                ->constrained('routes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('bus_id')
                ->after('route_id')
                ->constrained('buses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name')->nullable()->after('bus_id');

            $table->string('frequency', 20)->after('name'); // daily, weekly

            $table->json('days_of_week')->nullable()->after('frequency'); // weekly: 1=Mon ... 7=Sun

            $table->time('departure_time')->after('days_of_week');
            $table->time('arrival_time')->after('departure_time');

            $table->decimal('base_price', 12, 2)->after('arrival_time');

            $table->date('start_date')->after('base_price');
            $table->date('end_date')->nullable()->after('start_date');

            $table->unsignedSmallInteger('generate_days_ahead')->default(14)->after('end_date');

            $table->boolean('is_active')->default(true)->after('generate_days_ahead');

            $table->date('last_generated_until')->nullable()->after('is_active');

            $table->index(['route_id', 'is_active']);
            $table->index(['bus_id', 'is_active']);
            $table->index('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('trip_schedules', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropForeign(['bus_id']);

            $table->dropIndex(['route_id', 'is_active']);
            $table->dropIndex(['bus_id', 'is_active']);
            $table->dropIndex(['frequency']);

            $table->dropColumn([
                'route_id',
                'bus_id',
                'name',
                'frequency',
                'days_of_week',
                'departure_time',
                'arrival_time',
                'base_price',
                'start_date',
                'end_date',
                'generate_days_ahead',
                'is_active',
                'last_generated_until',
            ]);
        });
    }
};
