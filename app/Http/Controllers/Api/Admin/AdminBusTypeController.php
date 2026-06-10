<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusTypeRequest;
use App\Models\BusType;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBusTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BusType::query()
            ->withCount(['buses', 'seats']);

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->query('q') . '%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $busTypes = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách loại xe thành công.',
            'data' => $busTypes,
        ]);
    }

    public function store(StoreBusTypeRequest $request): JsonResponse
    {
        $busType = BusType::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo loại xe thành công.',
            'data' => $busType,
        ], 201);
    }

    public function show(BusType $busType): JsonResponse
    {
        $busType->load(['seats' => function ($query) {
            $query
                ->orderBy('floor')
                ->orderBy('seat_row')
                ->orderBy('seat_column');
        }])->loadCount(['buses', 'seats']);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết loại xe thành công.',
            'data' => $busType,
        ]);
    }

    public function update(StoreBusTypeRequest $request, BusType $busType): JsonResponse
    {
        if ($busType->tripsExists()) {
            return response()->json([
                'success' => false,
                'message' => 'Loại xe đã phát sinh chuyến, không nên sửa cấu hình trực tiếp.',
            ], 422);
        }

        $busType->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', $busType->is_active),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật loại xe thành công.',
            'data' => $busType->fresh(),
        ]);
    }

    public function generateSeats(BusType $busType): JsonResponse
    {
        if ($busType->seats()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Loại xe này đã có danh sách ghế. Không thể sinh lại để tránh trùng dữ liệu.',
            ], 422);
        }

        $layout = $busType->seat_layout ?? [];

        $floors = (int) ($layout['floors'] ?? 1);
        $rows = (int) ($layout['rows'] ?? 10);
        $columns = (int) ($layout['columns'] ?? 2);

        $capacity = $floors * $rows * $columns;

        if ($capacity < $busType->total_seats) {
            return response()->json([
                'success' => false,
                'message' => 'Layout không đủ số ghế so với total_seats.',
            ], 422);
        }

        $createdSeats = DB::transaction(function () use ($busType, $floors, $rows, $columns) {
            $created = 0;

            for ($floor = 1; $floor <= $floors; $floor++) {
                for ($row = 1; $row <= $rows; $row++) {
                    for ($column = 1; $column <= $columns; $column++) {
                        if ($created >= $busType->total_seats) {
                            break 3;
                        }

                        $prefix = match ($floor) {
                            1 => 'A',
                            2 => 'B',
                            3 => 'C',
                            default => 'S',
                        };

                        Seat::create([
                            'bus_type_id' => $busType->id,
                            'seat_number' => $prefix . str_pad((string) ($created + 1), 2, '0', STR_PAD_LEFT),
                            'seat_row' => $row,
                            'seat_column' => $column,
                            'floor' => $floor,
                            'seat_type' => 'sleeper',
                            'is_active' => true,
                        ]);

                        $created++;
                    }
                }
            }

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => 'Sinh danh sách ghế cho loại xe thành công.',
            'data' => [
                'bus_type_id' => $busType->id,
                'created_seats' => $createdSeats,
            ],
        ]);
    }

    public function destroy(BusType $busType): JsonResponse
    {
        if ($busType->buses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa loại xe đã có xe sử dụng. Hãy khóa loại xe thay vì xóa.',
            ], 422);
        }

        $busType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa loại xe thành công.',
        ]);
    }
}
