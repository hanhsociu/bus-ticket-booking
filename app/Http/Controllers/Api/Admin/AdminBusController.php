<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusRequest;
use App\Models\Bus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Bus::query()
            ->with('busType:id,name,total_seats')
            ->withCount('trips');

        if ($request->filled('q')) {
            $q = $request->query('q');

            $query->where(function ($subQuery) use ($q) {
                $subQuery
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('license_plate', 'like', "%{$q}%");
            });
        }

        if ($request->filled('bus_type_id')) {
            $query->where('bus_type_id', $request->integer('bus_type_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $buses = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách xe thành công.',
            'data' => $buses,
        ]);
    }

    public function store(StoreBusRequest $request): JsonResponse
    {
        $bus = Bus::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo xe thành công.',
            'data' => $bus->load('busType:id,name,total_seats'),
        ], 201);
    }

    public function show(Bus $bus): JsonResponse
    {
        $bus->load([
            'busType:id,name,total_seats,seat_layout',
            'trips:id,bus_id,route_id,code,departure_time,arrival_time,status',
            'trips.route:id,code,from_location,to_location',
        ])->loadCount('trips');

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết xe thành công.',
            'data' => $bus,
        ]);
    }

    public function update(StoreBusRequest $request, Bus $bus): JsonResponse
    {
        $hasRunningTrip = $bus->trips()
            ->whereIn('status', ['scheduled', 'departed'])
            ->exists();

        if ($hasRunningTrip && (int) $request->bus_type_id !== (int) $bus->bus_type_id) {
            return response()->json([
                'success' => false,
                'message' => 'Xe đang có chuyến hoạt động, không thể đổi loại xe.',
            ], 422);
        }

        $bus->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', $bus->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật xe thành công.',
            'data' => $bus->fresh()->load('busType:id,name,total_seats'),
        ]);
    }

    public function destroy(Bus $bus): JsonResponse
    {
        if ($bus->trips()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa xe đã có chuyến. Hãy khóa xe thay vì xóa.',
            ], 422);
        }

        $bus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa xe thành công.',
        ]);
    }
}
