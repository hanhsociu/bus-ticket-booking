<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CLIENT / CUSTOMER WEB
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('client.home'));
Route::get('/login', fn () => view('client.auth.login'));
Route::get('/register', fn () => view('client.auth.register'));
Route::get('/trips', fn () => view('client.trips.index'));
Route::get('/trips/{tripId}', fn (int $tripId) => view('client.trips.show', ['tripId' => $tripId]))->whereNumber('tripId');

Route::get('/customer/dashboard', fn () => view('client.dashboard'));
Route::get('/customer/bookings', fn () => view('client.bookings.index'));
Route::get('/customer/bookings/{bookingId}', fn (int $bookingId) => view('client.bookings.show', ['bookingId' => $bookingId]))->whereNumber('bookingId');
Route::get('/customer/bookings/{bookingId}/payment', fn (int $bookingId) => view('client.bookings.payment', ['bookingId' => $bookingId]))->whereNumber('bookingId');
Route::get('/customer/notifications', fn () => view('client.notifications.index'));

/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD WEB
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', fn () => view('admin.dashboard'));
    Route::get('/routes', fn () => view('admin.routes.index'));
    Route::get('/bus-types', fn () => view('admin.bus-types.index'));
    Route::get('/buses', fn () => view('admin.buses.index'));
    Route::get('/trips', fn () => view('admin.trips.index'));
    Route::get('/trips/{tripId}/passengers', fn (int $tripId) => view('admin.trips.passengers', ['tripId' => $tripId]))->whereNumber('tripId');
    Route::get('/bookings', fn () => view('admin.bookings.index'));
    Route::get('/refunds', fn () => view('admin.refunds.index'));
    Route::get('/tickets', fn () => view('admin.tickets.index'));
    Route::get('/users', fn () => view('admin.users.index'));
});
