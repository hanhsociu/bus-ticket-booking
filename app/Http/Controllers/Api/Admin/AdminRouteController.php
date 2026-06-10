<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRouteRequest;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRouteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Route::query();

        if ($request->filled('q')) {
            $q = $request->query('q');

            $query->where(function ($subQuery) use ($q) {
                $subQuery
                    ->where('code', 'like', "%{$q}%")
                    ->orWhere('from_location', 'like', "%{$q}%")
                    ->orWhere('to_location', 'like', "%{$q}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $routes = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách tuyến xe thành công.',
            'data' => $routes,
        ]);
    }

    public function store(StoreRouteRequest $request): JsonResponse
    {
        $route = Route::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo tuyến xe thành công.',
            'data' => $route,
        ], 201);
    }

    public function show(Route $route): JsonResponse
    {
        $route->loadCount('trips');

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết tuyến xe thành công.',
            'data' => $route,
        ]);
    }

    public function update(StoreRouteRequest $request, Route $route): JsonResponse
    {
        $route->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', $route->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tuyến xe thành công.',
            'data' => $route->fresh(),
        ]);
    }

    public function destroy(Route $route): JsonResponse
    {
        if ($route->trips()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tuyến xe đã có chuyến. Hãy khóa tuyến thay vì xóa.',
            ], 422);
        }

        $route->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa tuyến xe thành công.',
        ]);
    }
}
