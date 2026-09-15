<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/documentacion', fn () => response(view('api-docs'))->header('X-Robots-Tag', 'noindex, nofollow'))->name('api.docs');

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/public/catalog', [PublicController::class, 'catalog']);
    Route::get('/public/home', [PublicController::class, 'home']);
    Route::get('/public/carousel', [PublicController::class, 'carousel']);
    Route::get('/public/packages/{id}', [PublicController::class, 'package'])->whereNumber('id');
    Route::get('/public/booking', [PublicController::class, 'bookingForm']);
    Route::get('/public/qr/{token}', [PublicController::class, 'qrForm'])->whereUuid('token');
    Route::post('/public/reservations/online', [PublicController::class, 'onlineReservation'])->middleware('throttle:30,1');
    Route::post('/public/reservations/qr/{token}', [PublicController::class, 'qrReservation'])->whereUuid('token')->middleware('throttle:30,1');

    Route::middleware(['api.staff', 'throttle:120,1'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', [OperationsController::class, 'dashboard']);
        Route::get('/reservations', [OperationsController::class, 'reservations']);
        Route::get('/reservations/{id}', [OperationsController::class, 'reservation'])->whereNumber('id');
        Route::put('/reservations/{id}/complete', [OperationsController::class, 'completeReservation'])->whereNumber('id');
        Route::get('/departures', [OperationsController::class, 'departures']);
        Route::get('/departures/{id}', [OperationsController::class, 'departure'])->whereNumber('id');

        Route::get('/catalog/{type}', [CatalogController::class, 'index'])->whereIn('type', ['branches','categories','packages','equipment','lodgings','employees','slides']);
        Route::get('/catalog/{type}/{id}', [CatalogController::class, 'show'])->whereIn('type', ['branches','categories','packages','equipment','lodgings','employees','slides'])->whereNumber('id');

        Route::middleware('api.admin')->group(function () {
            Route::put('/departures/{id}/close', [OperationsController::class, 'closeDeparture'])->whereNumber('id');
            Route::post('/catalog/{type}', [CatalogController::class, 'store'])->whereIn('type', ['branches','categories','packages','equipment','lodgings','employees','slides']);
            Route::put('/catalog/{type}/{id}', [CatalogController::class, 'update'])->whereIn('type', ['branches','categories','packages','equipment','lodgings','employees','slides'])->whereNumber('id');
            Route::delete('/catalog/slides/{id}', [CatalogController::class, 'destroySlide'])->whereNumber('id');
            Route::get('/branches/{id}/qr', [CatalogController::class, 'branchQr'])->whereNumber('id');
        });
    });
});
