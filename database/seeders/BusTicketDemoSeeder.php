<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\BusType;
use App\Models\Route;
use App\Models\Seat;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BusTicketDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@bus.local',
            'phone' => '0900000000',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Nguyen Van A',
            'email' => 'customer@bus.local',
            'phone' => '0911111111',
            'password' => Hash::make('12345678'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $route = Route::create([
            'code' => 'HN-HP',
            'from_location' => 'Hà Nội',
            'to_location' => 'Hải Phòng',
            'distance_km' => 120,
            'estimated_duration_minutes' => 150,
            'is_active' => true,
        ]);

        $busType = BusType::create([
            'name' => 'Xe giường nằm 40 chỗ',
            'description' => 'Xe giường nằm 2 tầng, phù hợp tuyến liên tỉnh.',
            'total_seats' => 40,
            'seat_layout' => [
                'floors' => 2,
                'rows' => 10,
                'columns' => 2,
            ],
            'is_active' => true,
        ]);

        for ($floor = 1; $floor <= 2; $floor++) {
            for ($row = 1; $row <= 10; $row++) {
                for ($column = 1; $column <= 2; $column++) {
                    $prefix = $floor === 1 ? 'A' : 'B';

                    Seat::create([
                        'bus_type_id' => $busType->id,
                        'seat_number' => $prefix . str_pad((string) (($row - 1) * 2 + $column), 2, '0', STR_PAD_LEFT),
                        'seat_row' => $row,
                        'seat_column' => $column,
                        'floor' => $floor,
                        'seat_type' => 'sleeper',
                        'is_active' => true,
                    ]);
                }
            }
        }

        $bus = Bus::create([
            'bus_type_id' => $busType->id,
            'name' => 'Xe Hà Nội - Hải Phòng 01',
            'license_plate' => '29B-12345',
            'is_active' => true,
        ]);

        $trip = Trip::create([
            'route_id' => $route->id,
            'bus_id' => $bus->id,
            'code' => 'TRIP-HNHP-0800',
            'departure_time' => now()->addDay()->setTime(8, 0),
            'arrival_time' => now()->addDay()->setTime(10, 30),
            'base_price' => 150000,
            'status' => 'scheduled',
        ]);

        $seats = Seat::where('bus_type_id', $busType->id)->get();

        foreach ($seats as $seat) {
            TripSeat::create([
                'trip_id' => $trip->id,
                'seat_id' => $seat->id,
                'status' => 'available',
                'locked_until' => null,
                'booking_id' => null,
            ]);
        }
    }
}
