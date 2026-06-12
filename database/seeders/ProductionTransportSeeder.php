<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\BusType;
use App\Models\Route as BusRoute;
use App\Models\Seat;
use App\Models\TripSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionTransportSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $routes = $this->seedRoutes();
            $busTypes = $this->seedBusTypes();

            $this->seedSeats($busTypes['limousine22'], 'L', 22);
            $this->seedSleeperSeats($busTypes['sleeper34'], 17);
            $this->seedSleeperSeats($busTypes['sleeper40'], 20);
            $this->seedSeats($busTypes['cabin24'], 'C', 24);

            $busesByRoute = $this->seedBuses($busTypes);

            $this->seedTripSchedules($routes, $busesByRoute);
        });

        $this->command?->info('Production transport data seeded successfully.');
        $this->command?->info('Next: php artisan trips:generate-from-schedules --days=30');
    }

    private function seedRoutes(): array
    {
        $data = [
            'HNHP' => [
                'code' => 'HNHP',
                'name' => 'Hà Nội → Hải Phòng',
                'from' => 'Hà Nội',
                'to' => 'Hải Phòng',
                'distance_km' => 120,
                'duration_minutes' => 150,
                'booking_cutoff_minutes' => 30,
                'description' => 'Tuyến gần, phù hợp xe limousine và giường nằm ngắn tuyến.',
            ],
            'HNNB' => [
                'code' => 'HNNB',
                'name' => 'Hà Nội → Ninh Bình',
                'from' => 'Hà Nội',
                'to' => 'Ninh Bình',
                'distance_km' => 95,
                'duration_minutes' => 120,
                'booking_cutoff_minutes' => 30,
                'description' => 'Tuyến gần, phục vụ khách du lịch và đi lại trong ngày.',
            ],
            'HNTH' => [
                'code' => 'HNTH',
                'name' => 'Hà Nội → Thanh Hóa',
                'from' => 'Hà Nội',
                'to' => 'Thanh Hóa',
                'distance_km' => 160,
                'duration_minutes' => 210,
                'booking_cutoff_minutes' => 60,
                'description' => 'Tuyến trung bình, có chuyến ngày và chuyến đêm.',
            ],
            'HNV' => [
                'code' => 'HNV',
                'name' => 'Hà Nội → Vinh',
                'from' => 'Hà Nội',
                'to' => 'Vinh',
                'distance_km' => 300,
                'duration_minutes' => 390,
                'booking_cutoff_minutes' => 120,
                'description' => 'Tuyến xa, ưu tiên giường nằm và cabin.',
            ],
            'HNDN' => [
                'code' => 'HNDN',
                'name' => 'Hà Nội → Đà Nẵng',
                'from' => 'Hà Nội',
                'to' => 'Đà Nẵng',
                'distance_km' => 760,
                'duration_minutes' => 870,
                'booking_cutoff_minutes' => 180,
                'description' => 'Tuyến rất xa, cần đóng bán sớm để nhà xe kiểm soát vận hành.',
            ],
        ];

        $routes = [];

        foreach ($data as $key => $item) {
            $routes[$key] = $this->upsertModel(
                BusRoute::class,
                [
                    'code' => $item['code'],
                    'route_code' => $item['code'],
                    'name' => $item['name'],
                ],
                [
                    'code' => $item['code'],
                    'route_code' => $item['code'],
                    'name' => $item['name'],
                    'from_city' => $item['from'],
                    'to_city' => $item['to'],
                    'from_location' => $item['from'],
                    'to_location' => $item['to'],
                    'departure_location' => $item['from'],
                    'arrival_location' => $item['to'],
                    'origin' => $item['from'],
                    'destination' => $item['to'],
                    'distance_km' => $item['distance_km'],
                    'distance' => $item['distance_km'],
                    'estimated_duration_minutes' => $item['duration_minutes'],
                    'duration_minutes' => $item['duration_minutes'],
                    'estimated_duration' => $item['duration_minutes'],
                    'booking_cutoff_minutes' => $item['booking_cutoff_minutes'],
                    'description' => $item['description'],
                    'status' => 'active',
                    'is_active' => true,
                ]
            );
        }

        return $routes;
    }

    private function seedBusTypes(): array
    {
        $types = [
            'limousine22' => [
                'code' => 'LIMOUSINE_22',
                'name' => 'Limousine 22 chỗ',
                'seat_count' => 22,
                'description' => 'Xe limousine ghế ngồi cao cấp, phù hợp tuyến gần và trung bình.',
            ],
            'sleeper34' => [
                'code' => 'SLEEPER_34',
                'name' => 'Giường nằm 34 chỗ',
                'seat_count' => 34,
                'description' => 'Xe giường nằm 2 tầng, phù hợp tuyến trung bình và xa.',
            ],
            'sleeper40' => [
                'code' => 'SLEEPER_40',
                'name' => 'Giường nằm 40 chỗ',
                'seat_count' => 40,
                'description' => 'Xe giường nằm phổ thông, tối ưu số lượng khách.',
            ],
            'cabin24' => [
                'code' => 'CABIN_VIP_24',
                'name' => 'Cabin VIP 24 chỗ',
                'seat_count' => 24,
                'description' => 'Xe cabin VIP riêng tư, phù hợp tuyến xa và rất xa.',
            ],
        ];

        $busTypes = [];

        foreach ($types as $key => $item) {
            $busTypes[$key] = $this->upsertModel(
                BusType::class,
                [
                    'code' => $item['code'],
                    'bus_type_code' => $item['code'],
                    'name' => $item['name'],
                ],
                [
                    'code' => $item['code'],
                    'bus_type_code' => $item['code'],
                    'name' => $item['name'],
                    'seat_count' => $item['seat_count'],
                    'total_seats' => $item['seat_count'],
                    'number_of_seats' => $item['seat_count'],
                    'description' => $item['description'],
                    'status' => 'active',
                    'is_active' => true,
                ]
            );
        }

        return $busTypes;
    }

    private function seedSeats(BusType $busType, string $prefix, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $seatNumber = $prefix . str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            $this->upsertModel(
                Seat::class,
                [
                    'bus_type_id' => $busType->id,
                    'seat_number' => $seatNumber,
                    'code' => $this->safeCode($busType) . '-' . $seatNumber,
                ],
                [
                    'bus_type_id' => $busType->id,
                    'seat_number' => $seatNumber,
                    'number' => $seatNumber,
                    'code' => $this->safeCode($busType) . '-' . $seatNumber,
                    'name' => $seatNumber,
                    'seat_type' => $this->seatTypeValue('standard'),
                    'floor' => 1,
                    'row_number' => $i,
                    'column_number' => 1,
                    'position' => $seatNumber,
                    'status' => 'active',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedSleeperSeats(BusType $busType, int $perFloor): void
    {
        foreach (['A' => 1, 'B' => 2] as $prefix => $floor) {
            for ($i = 1; $i <= $perFloor; $i++) {
                $seatNumber = $prefix . str_pad((string) $i, 2, '0', STR_PAD_LEFT);

                $this->upsertModel(
                    Seat::class,
                    [
                        'bus_type_id' => $busType->id,
                        'seat_number' => $seatNumber,
                        'code' => $this->safeCode($busType) . '-' . $seatNumber,
                    ],
                    [
                        'bus_type_id' => $busType->id,
                        'seat_number' => $seatNumber,
                        'number' => $seatNumber,
                        'code' => $this->safeCode($busType) . '-' . $seatNumber,
                        'name' => $seatNumber,
                        'seat_type' => $this->seatTypeValue('sleeper'),
                        'floor' => $floor,
                        'row_number' => $i,
                        'column_number' => $floor,
                        'position' => $seatNumber,
                        'status' => 'active',
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedBuses(array $busTypes): array
    {
        $data = [
            'HNHP' => [
                ['BUS-HNHP-01', '29B-101.01', 'Xe Hà Nội - Hải Phòng 01', $busTypes['limousine22']],
                ['BUS-HNHP-02', '29B-101.02', 'Xe Hà Nội - Hải Phòng 02', $busTypes['sleeper34']],
                ['BUS-HNHP-03', '29B-101.03', 'Xe Hà Nội - Hải Phòng 03', $busTypes['sleeper40']],
            ],
            'HNNB' => [
                ['BUS-HNNB-01', '29B-102.01', 'Xe Hà Nội - Ninh Bình 01', $busTypes['limousine22']],
                ['BUS-HNNB-02', '29B-102.02', 'Xe Hà Nội - Ninh Bình 02', $busTypes['sleeper34']],
                ['BUS-HNNB-03', '29B-102.03', 'Xe Hà Nội - Ninh Bình 03', $busTypes['sleeper40']],
            ],
            'HNTH' => [
                ['BUS-HNTH-01', '29B-103.01', 'Xe Hà Nội - Thanh Hóa 01', $busTypes['sleeper34']],
                ['BUS-HNTH-02', '29B-103.02', 'Xe Hà Nội - Thanh Hóa 02', $busTypes['sleeper40']],
                ['BUS-HNTH-03', '29B-103.03', 'Xe Hà Nội - Thanh Hóa 03', $busTypes['limousine22']],
            ],
            'HNV' => [
                ['BUS-HNV-01', '29B-104.01', 'Xe Hà Nội - Vinh 01', $busTypes['sleeper40']],
                ['BUS-HNV-02', '29B-104.02', 'Xe Hà Nội - Vinh 02', $busTypes['cabin24']],
                ['BUS-HNV-03', '29B-104.03', 'Xe Hà Nội - Vinh 03', $busTypes['sleeper34']],
            ],
            'HNDN' => [
                ['BUS-HNDN-01', '29B-105.01', 'Xe Hà Nội - Đà Nẵng 01', $busTypes['sleeper40']],
                ['BUS-HNDN-02', '29B-105.02', 'Xe Hà Nội - Đà Nẵng 02', $busTypes['cabin24']],
                ['BUS-HNDN-03', '29B-105.03', 'Xe Hà Nội - Đà Nẵng 03', $busTypes['sleeper40']],
                ['BUS-HNDN-04', '29B-105.04', 'Xe Hà Nội - Đà Nẵng 04', $busTypes['sleeper34']],
            ],
        ];

        $result = [];

        foreach ($data as $routeKey => $items) {
            foreach ($items as [$code, $plate, $name, $busType]) {
                $result[$routeKey][] = $this->upsertModel(
                    Bus::class,
                    [
                        'code' => $code,
                        'bus_code' => $code,
                        'license_plate' => $plate,
                    ],
                    [
                        'code' => $code,
                        'bus_code' => $code,
                        'name' => $name,
                        'license_plate' => $plate,
                        'plate_number' => $plate,
                        'bus_type_id' => $busType->id,
                        'description' => $name . ' - ' . ($busType->name ?? 'Xe khách'),
                        'status' => 'active',
                        'is_active' => true,
                    ]
                );
            }
        }

        return $result;
    }

    private function seedTripSchedules(array $routes, array $busesByRoute): void
    {
        $today = Carbon::today()->toDateString();

        $schedules = [
            'HNHP' => [
                ['06:00:00', '08:30:00', 150000],
                ['09:00:00', '11:30:00', 150000],
                ['14:00:00', '16:30:00', 160000],
                ['18:00:00', '20:30:00', 160000],
            ],
            'HNNB' => [
                ['07:00:00', '09:00:00', 120000],
                ['10:00:00', '12:00:00', 120000],
                ['15:00:00', '17:00:00', 130000],
            ],
            'HNTH' => [
                ['06:30:00', '10:00:00', 180000],
                ['12:30:00', '16:00:00', 190000],
                ['21:00:00', '00:30:00', 220000],
            ],
            'HNV' => [
                ['07:00:00', '13:30:00', 280000],
                ['14:00:00', '20:30:00', 300000],
                ['22:00:00', '04:30:00', 320000],
            ],
            'HNDN' => [
                ['08:00:00', '22:30:00', 550000],
                ['15:00:00', '05:30:00', 580000],
                ['20:00:00', '10:30:00', 600000],
                ['22:30:00', '13:00:00', 620000],
            ],
        ];

        foreach ($schedules as $routeKey => $items) {
            $route = $routes[$routeKey];
            $buses = $busesByRoute[$routeKey];

            foreach ($items as $index => [$departureTime, $arrivalTime, $price]) {
                $bus = $buses[$index % count($buses)];

                $scheduleName = sprintf(
                    '%s - %s hằng ngày',
                    $this->routeDisplayName($route),
                    substr($departureTime, 0, 5)
                );

                $this->upsertModel(
                    TripSchedule::class,
                    [
                        'name' => $scheduleName,
                        'route_id' => $route->id,
                        'bus_id' => $bus->id,
                        'departure_time' => $departureTime,
                    ],
                    [
                        'name' => $scheduleName,
                        'route_id' => $route->id,
                        'bus_id' => $bus->id,
                        'frequency' => 'daily',
                        'days_of_week' => null,
                        'departure_time' => $departureTime,
                        'arrival_time' => $arrivalTime,
                        'base_price' => $price,
                        'start_date' => $today,
                        'end_date' => null,
                        'generate_days_ahead' => 30,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function upsertModel(string $modelClass, array $lookupCandidates, array $values): Model
    {
        /** @var Model $model */
        $model = new $modelClass();
        $table = $model->getTable();

        $lookup = $this->filterExistingColumns($table, $lookupCandidates);

        if (empty($lookup)) {
            throw new \RuntimeException("No valid lookup columns for table {$table}");
        }

        $query = $modelClass::query();

        foreach ($lookup as $column => $value) {
            $query->where($column, $value);
        }

        /** @var Model|null $record */
        $record = $query->first();

        $data = $this->filterExistingColumns($table, $values);

        if ($record) {
            $record->forceFill($data);
            $record->save();

            return $record;
        }

        $record = new $modelClass();
        $record->forceFill(array_merge($lookup, $data));
        $record->save();

        return $record;
    }

    private function filterExistingColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn($value, string $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function safeCode(BusType $busType): string
    {
        return $busType->code
            ?? $busType->bus_type_code
            ?? 'BT' . $busType->id;
    }

    private function seatTypeValue(string $preferred): string
    {
        if (! Schema::hasColumn('seats', 'seat_type')) {
            return $preferred;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM seats LIKE 'seat_type'");
        $columnData = (array) $column;

        $type = $columnData['Type'] ?? $columnData['type'] ?? '';

        preg_match_all("/'([^']+)'/", $type, $matches);

        $allowed = $matches[1] ?? [];

        if (empty($allowed)) {
            return $preferred;
        }

        $candidates = $preferred === 'sleeper'
            ? [
                'sleeper',
                'bed',
                'giuong',
                'giuong_nam',
                'giường',
                'giường nằm',
                'upper',
                'lower',
                'normal',
                'standard',
                'seat',
                'vip',
            ]
            : [
                'standard',
                'seat',
                'normal',
                'ghe',
                'ghe_ngoi',
                'ghế',
                'ghế ngồi',
                'limousine',
                'vip',
            ];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return $allowed[0];
    }

    private function routeDisplayName(BusRoute $route): string
    {
        return $route->name
            ?? (($route->from_city ?? $route->departure_location ?? $route->origin ?? 'Điểm đi')
                . ' → '
                . ($route->to_city ?? $route->arrival_location ?? $route->destination ?? 'Điểm đến'));
    }
}
