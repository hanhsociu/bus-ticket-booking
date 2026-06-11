<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string', 'in:sent,failed,read,unread'],
            'type' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Notification::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'booking:id,booking_code,status,total_amount,trip_id,created_at,confirmed_at',
                'booking.trip:id,code,route_id,bus_id,departure_time,arrival_time,status',
                'booking.trip.route:id,code,from_location,to_location',
                'booking.trip.bus:id,name,license_plate',
            ])
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');

            if ($status === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($status === 'unread') {
                $query->whereNull('read_at');
            } else {
                $query->where('status', $status);
            }
        }

        $notifications = $query->paginate(
            $request->integer('per_page', 10)
        );

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách thông báo thành công.',
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Lấy số thông báo chưa đọc thành công.',
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thao tác thông báo này.',
            ], 403);
        }

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu thông báo là đã đọc.',
            'data' => $notification->fresh([
                'booking:id,booking_code,status,total_amount,trip_id,created_at,confirmed_at',
                'booking.trip:id,code,route_id,bus_id,departure_time,arrival_time,status',
                'booking.trip.route:id,code,from_location,to_location',
                'booking.trip.bus:id,name,license_plate',
            ]),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc.',
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }
}
