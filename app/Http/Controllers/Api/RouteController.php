<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\JsonResponse;

class RouteController extends Controller
{
    public function index(): JsonResponse
    {
        $routes = Route::query()
            ->where('is_active', true)
            ->orderBy('from_location')
            ->get([
                'id',
                'code',
                'from_location',
                'to_location',
                'distance_km',
                'estimated_duration_minutes',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách tuyến xe thành công.',
            'data' => $routes,
        ]);
    }
}
