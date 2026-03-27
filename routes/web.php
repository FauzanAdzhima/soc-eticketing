<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Pages\DashboardPage;
use App\Livewire\Pages\ProfilePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('index');
})->name('home');

Route::get('/report/category', function () {
    return view('report.category');
})->name('ticket.create');

Route::get('/dashboard', DashboardPage::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/profile/edit', function () {
        return redirect()->route('profile');
    })->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test', function () {
    return view('test');
});

require __DIR__ . '/auth.php';
