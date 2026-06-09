<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PayOSPaymentController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\TripController;

//ADMIN
use App\Http\Controllers\Api\Admin\AdminTripController;

use Illuminate\Support\Facades\Route;

Route::get('/routes', [RouteController::class, 'index']);

Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{trip}', [TripController::class, 'show']);
Route::get('/trips/{trip}/seats', [TripController::class, 'seats']);

Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/{booking}', [BookingController::class, 'show']);

Route::post('/payments/payos/create', [PayOSPaymentController::class, 'create']);
Route::get('/payments/payos/return', [PayOSPaymentController::class, 'return']);
Route::get('/payments/payos/cancel', [PayOSPaymentController::class, 'cancel']);
Route::post('/payments/payos/webhook', [PayOSPaymentController::class, 'webhook']);
// fake 
Route::post('/payments/{payment}/fake-success', [PayOSPaymentController::class, 'fakeSuccess']);
// user cancel bookings
Route::get('/users/{user}/bookings', [BookingController::class, 'userBookings']);
Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

// ADMIN
Route::prefix('admin')->group(function () {
    Route::get('/trips', [AdminTripController::class, 'index']);
    Route::post('/trips', [AdminTripController::class, 'store']);
    Route::get('/trips/{trip}', [AdminTripController::class, 'show']);
    Route::post('/trips/{trip}/cancel', [AdminTripController::class, 'cancel']);
});