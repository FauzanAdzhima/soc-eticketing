<?php

use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('tickets', TicketController::class);

Route::patch('/tickets/{id}/status', [TicketController::class, 'updateStatus'])->middleware('auth:sanctum');

Route::post('/tickets/{id}/assign', [TicketController::class, 'assign'])->middleware('auth:sanctum');