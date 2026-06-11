<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'in:admin,customer'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()
            ->withCount([
                'bookings',
                'notifications',
            ]);

        if ($request->filled('q')) {
            $q = $request->query('q');

            $query->where(function ($subQuery) use ($q) {
                $subQuery
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách người dùng thành công.',
            'data' => $users,
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount([
            'bookings',
            'notifications',
        ]);

        $user->load([
            'bookings' => function ($query) {
                $query
                    ->with([
                        'trip:id,code,route_id,bus_id,departure_time,arrival_time,status',
                        'trip.route:id,code,from_location,to_location',
                        'trip.bus:id,name,license_plate',
                        'items:id,booking_id,trip_seat_id,seat_number,price,checked_in_at,checked_in_by',
                        'payments:id,booking_id,payment_code,method,status,amount,paid_at',
                    ])
                    ->latest()
                    ->limit(20);
            },
            'notifications' => function ($query) {
                $query
                    ->latest()
                    ->limit(20);
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết người dùng thành công.',
            'data' => $user,
        ]);
    }

    public function lock(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $user = DB::transaction(function () use ($request, $user) {
                $user = User::query()
                    ->where('id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $user->id === (int) $request->user()->id) {
                    abort(422, 'Admin không thể tự khóa tài khoản của chính mình.');
                }

                if ($user->role === 'admin') {
                    abort(422, 'Không thể khóa tài khoản admin bằng API này.');
                }

                if (! $user->is_active) {
                    abort(422, 'Tài khoản này đã bị khóa trước đó.');
                }

                $user->update([
                    'is_active' => false,
                ]);

                /*
                 * Xóa toàn bộ token Sanctum hiện tại.
                 * Nhờ đó user đang đăng nhập cũng bị đá ra khỏi hệ thống.
                 */
                $user->tokens()->delete();

                return $user->fresh()->loadCount([
                    'bookings',
                    'notifications',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Khóa tài khoản người dùng thành công.',
                'data' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function unlock(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $user = DB::transaction(function () use ($user) {
                $user = User::query()
                    ->where('id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($user->is_active) {
                    abort(422, 'Tài khoản này đang hoạt động, không cần mở khóa.');
                }

                $user->update([
                    'is_active' => true,
                ]);

                return $user->fresh()->loadCount([
                    'bookings',
                    'notifications',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Mở khóa tài khoản người dùng thành công.',
                'data' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
