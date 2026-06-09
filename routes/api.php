<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/routes', [RouteController::class, 'index']);

Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{trip}', [TripController::class, 'show']);
Route::get('/trips/{trip}/seats', [TripController::class, 'seats']);

Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/{booking}', [BookingController::class, 'show']);
