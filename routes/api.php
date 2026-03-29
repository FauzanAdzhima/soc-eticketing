<?php

use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/tickets/public', [TicketController::class, 'storePublic'])->middleware('throttle:30,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/tickets', [TicketController::class, 'index'])
        ->middleware('permission:ticket.view');

    Route::post('/tickets', [TicketController::class, 'storeAuthenticated'])
        ->middleware('permission:ticket.create.pic');

    Route::patch('/tickets/{id}/status', [TicketController::class, 'updateStatus']);

    Route::post('/tickets/{id}/verify', [TicketController::class, 'verifyReport'])
        ->middleware('permission:ticket.assign');

    Route::post('/tickets/{id}/reject', [TicketController::class, 'rejectReport'])
        ->middleware('permission:ticket.assign');

    Route::post('/tickets/{id}/assign', [TicketController::class, 'assign'])
        ->middleware('permission:ticket.assign');
});