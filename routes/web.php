<?php

use App\Http\Controllers\TourismController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('no.history')->group(function () {
    Route::get('/ingresar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->name('login.attempt');
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
});
Route::get('/registro/{token}', [TourismController::class, 'publicForm'])->whereUuid('token')->name('tourist.qr');
Route::get('/reservar', [TourismController::class, 'onlineForm'])->name('tourist.online');
Route::post('/reservas', [TourismController::class, 'storeTourist'])->name('reservations.store');
Route::middleware(['staff', 'no.history'])->group(function () {
    Route::get('/panel', [TourismController::class, 'dashboard'])->name('dashboard');
    Route::put('/reservas/{id}/completar', [TourismController::class, 'completeReservation'])->whereNumber('id')->name('reservations.complete');
    Route::put('/salidas/{id}/cerrar', [TourismController::class, 'closeDeparture'])->whereNumber('id')->middleware('admin')->name('departures.close');
    Route::post('/catalogo/{type}', [TourismController::class, 'storeResource'])->whereIn('type', ['branches','categories','packages','equipment','lodgings','employees','slides'])->middleware('admin')->name('resources.store');
    Route::put('/catalogo/{type}/{id}', [TourismController::class, 'updateResource'])->whereIn('type', ['branches','categories','packages','equipment','lodgings','employees','slides'])->whereNumber('id')->middleware('admin')->name('resources.update');
    Route::delete('/carrusel/{id}', [TourismController::class, 'destroySlide'])->whereNumber('id')->middleware('admin')->name('slides.destroy');
});
Route::get('/', [TourismController::class, 'publicHome'])->name('home');

Route::fallback(function (\Illuminate\Http\Request $request) {
    if ($request->is('api/*')) {
        return response()->json(['ok'=>false, 'message'=>'Ruta no encontrada.'], 404);
    }

    return redirect()->route('login')->withErrors([
        'email' => 'La URL solicitada no existe. Inicia sesión para continuar.',
    ]);
});
