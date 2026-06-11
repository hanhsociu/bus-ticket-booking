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

use App\Http\Controllers\Api\Customer\CustomerDashboardController;

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

        /*
        |--------------------------------------------------------------------------
        | DEV / TEST ONLY
        |--------------------------------------------------------------------------
        */
        Route::post('/payments/{payment}/fake-success', [PayOSPaymentController::class, 'fakeSuccess']);
    });
