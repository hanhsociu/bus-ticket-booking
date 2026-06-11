<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PayOSPaymentController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\TripController;

use App\Http\Controllers\Api\Admin\AdminBookingController;
use App\Http\Controllers\Api\Admin\AdminBusController;
use App\Http\Controllers\Api\Admin\AdminBusTypeController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminRouteController;
use App\Http\Controllers\Api\Admin\AdminTripController;
use App\Http\Controllers\Api\Admin\AdminRefundController;
use App\Http\Controllers\Api\Admin\AdminTripOperationController;
use App\Http\Controllers\Api\Admin\AdminPassengerCheckInController;
use App\Http\Controllers\Api\Admin\AdminTicketVerificationController;
use App\Http\Controllers\Api\Admin\AdminUserController;


use App\Http\Controllers\Api\Customer\CustomerDashboardController;
use App\Http\Controllers\Api\Customer\CustomerNotificationController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH PUBLIC
|--------------------------------------------------------------------------
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/routes', [RouteController::class, 'index']);

Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{trip}', [TripController::class, 'show']);
Route::get('/trips/{trip}/seats', [TripController::class, 'seats']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED CUSTOMER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/customer/dashboard/overview', [CustomerDashboardController::class, 'overview']);

    Route::get('/customer/notifications', [CustomerNotificationController::class, 'index']);
    Route::get('/customer/notifications/unread-count', [CustomerNotificationController::class, 'unreadCount']);
    Route::post('/customer/notifications/{notification}/mark-as-read', [CustomerNotificationController::class, 'markAsRead']);
    Route::post('/customer/notifications/mark-all-as-read', [CustomerNotificationController::class, 'markAllAsRead']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::get('/my/bookings', [BookingController::class, 'myBookings']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/request-refund', [BookingController::class, 'requestRefund']);

    Route::post('/payments/payos/create', [PayOSPaymentController::class, 'create']);
});

/*
|--------------------------------------------------------------------------
| PAYOS CALLBACKS
|--------------------------------------------------------------------------
| PayOS gọi/redirect về nên không bắt auth token.
*/
Route::get('/payments/payos/return', [PayOSPaymentController::class, 'return']);
Route::get('/payments/payos/cancel', [PayOSPaymentController::class, 'cancel']);
Route::post('/payments/payos/webhook', [PayOSPaymentController::class, 'webhook']);

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard/overview', [AdminDashboardController::class, 'overview']);

        Route::apiResource('/routes', AdminRouteController::class);

        Route::apiResource('/bus-types', AdminBusTypeController::class);
        Route::post('/bus-types/{busType}/generate-seats', [AdminBusTypeController::class, 'generateSeats']);

        Route::apiResource('/buses', AdminBusController::class);

        Route::get('/trips', [AdminTripController::class, 'index']);
        Route::post('/trips', [AdminTripController::class, 'store']);
        Route::get('/trips/{trip}', [AdminTripController::class, 'show']);
        Route::post('/trips/{trip}/cancel', [AdminTripController::class, 'cancel']);

        Route::get('/bookings', [AdminBookingController::class, 'index']);
        Route::get('/bookings/{booking}', [AdminBookingController::class, 'show']);
        Route::post('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel']);

        Route::get('/refunds', [AdminRefundController::class, 'index']);
        Route::post('/bookings/{booking}/approve-refund', [AdminRefundController::class, 'approve']);
        Route::post('/bookings/{booking}/reject-refund', [AdminRefundController::class, 'reject']);

        Route::post('/trips/{trip}/depart', [AdminTripOperationController::class, 'depart']);
        Route::post('/trips/{trip}/complete', [AdminTripOperationController::class, 'complete']);
        Route::get('/trips/{trip}/passengers', [AdminTripOperationController::class, 'passengers']);


        Route::post('/bookings/{booking}/check-in', [AdminPassengerCheckInController::class, 'checkInBooking']);
        Route::post('/booking-items/{bookingItem}/check-in', [AdminPassengerCheckInController::class, 'checkInItem']);
        Route::post('/booking-items/{bookingItem}/undo-check-in', [AdminPassengerCheckInController::class, 'undoCheckInItem']);

        Route::get('/tickets/verify', [AdminTicketVerificationController::class, 'verify']);
        Route::post('/tickets/check-in', [AdminTicketVerificationController::class, 'checkInByCode']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::post('/users/{user}/lock', [AdminUserController::class, 'lock']);
        Route::post('/users/{user}/unlock', [AdminUserController::class, 'unlock']);

        /*
        |--------------------------------------------------------------------------
        | DEV / TEST ONLY
        |--------------------------------------------------------------------------
        */
        Route::post('/payments/{payment}/fake-success', [PayOSPaymentController::class, 'fakeSuccess']);
    });
