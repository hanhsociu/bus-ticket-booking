<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTripScheduleRequest;
use App\Http\Requests\Admin\UpdateTripScheduleRequest;
use App\Models\TripSchedule;
use App\Services\TripScheduleGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTripScheduleController extends Controller
{
    public function __construct(
        private readonly TripScheduleGenerationService $tripScheduleGenerationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = TripSchedule::query()
            ->with([
                'route:id,code,from_location,to_location,is_active',
                'bus:id,name,license_plate,bus_type_id,is_active',
            ])
            ->withCount('trips');

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->integer('route_id'));
        }

        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->integer('bus_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->query('frequency'));
        }

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhereHas('route', function ($routeQuery) use ($q) {
                        $routeQuery
                            ->where('code', 'like', "%{$q}%")
                            ->orWhere('from_location', 'like', "%{$q}%")
                            ->orWhere('to_location', 'like', "%{$q}%");
                    })
                    ->orWhereHas('bus', function ($busQuery) use ($q) {
                        $busQuery
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('license_plate', 'like', "%{$q}%");
                    });
            });
        }

        $schedules = $query->latest()->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách lịch chạy mẫu thành công.',
            'data' => $schedules,
        ]);
    }

    public function store(StoreTripScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $schedule = TripSchedule::create([
            ...$data,
            'days_of_week' => $data['frequency'] === 'weekly' ? ($data['days_of_week'] ?? []) : null,
            'generate_days_ahead' => $data['generate_days_ahead'] ?? 14,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $schedule->load([
            'route:id,code,from_location,to_location',
            'bus:id,name,license_plate',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo lịch chạy mẫu thành công.',
            'data' => $schedule,
        ], 201);
    }

    public function show(TripSchedule $tripSchedule): JsonResponse
    {
        $tripSchedule->load([
            'route:id,code,from_location,to_location,is_active',
            'bus:id,name,license_plate,bus_type_id,is_active',
        ])->loadCount('trips');

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết lịch chạy mẫu thành công.',
            'data' => $tripSchedule,
        ]);
    }

    public function update(UpdateTripScheduleRequest $request, TripSchedule $tripSchedule): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['frequency']) && $data['frequency'] === 'daily') {
            $data['days_of_week'] = null;
        }

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $tripSchedule->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật lịch chạy mẫu thành công.',
            'data' => $tripSchedule->fresh([
                'route:id,code,from_location,to_location',
                'bus:id,name,license_plate',
            ]),
        ]);
    }

    public function destroy(TripSchedule $tripSchedule): JsonResponse
    {
        if ($tripSchedule->trips()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa lịch đã có chuyến sinh ra. Hãy đặt is_active = false để tắt lịch.',
            ], 422);
        }

        $tripSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa lịch chạy mẫu thành công.',
        ]);
    }

    public function generate(Request $request, TripSchedule $tripSchedule): JsonResponse
    {
        $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $today = now()->startOfDay();
        $days = $request->integer('days') ?: $tripSchedule->generate_days_ahead;
        $toDate = $today->copy()->addDays($days);

        $report = $this->tripScheduleGenerationService->generateForSchedule(
            schedule: $tripSchedule,
            fromDate: $today,
            toDate: $toDate,
            dryRun: $request->boolean('dry_run')
        );

        return response()->json([
            'success' => true,
            'message' => $request->boolean('dry_run')
                ? 'Dry-run: xem trước kết quả sinh chuyến (không ghi DB).'
                : 'Sinh chuyến từ lịch mẫu thành công.',
            'data' => $report,
        ]);
    }
}
