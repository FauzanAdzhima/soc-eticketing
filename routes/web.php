<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketEvidenceController;
use App\Livewire\Pages\Admin\IncidentCategories\IndexPage as AdminIncidentCategoriesIndexPage;
use App\Livewire\Pages\Admin\Organizations\IndexPage as AdminOrganizationsIndexPage;
use App\Livewire\Pages\Admin\Roles\IndexPage as AdminRolesIndexPage;
use App\Livewire\Pages\Admin\Users\IndexPage as AdminUsersIndexPage;
use App\Livewire\Pages\DashboardPage;
use App\Livewire\Pages\ProfilePage;
use App\Livewire\Pages\Tickets\IndexPage as TicketsIndexPage;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

    Route::get('/tickets', TicketsIndexPage::class)
        ->middleware('can:ticket.view')
        ->name('tickets.index');
    Route::get('/tickets/evidence/{evidence}', [TicketEvidenceController::class, 'show'])
        ->middleware('can:ticket.view')
        ->name('tickets.evidence.show');
    Route::get('/tickets/{ticket}', function (Ticket $ticket) {
        Gate::authorize('view', $ticket);

        return redirect()->route('tickets.index', ['ticket' => $ticket->public_id]);
    })->name('tickets.show');

    Route::prefix('dashboard/admin')->name('admin.')->group(function () {
        Route::get('/users', AdminUsersIndexPage::class)
            ->middleware('can:user.view')
            ->name('users.index');
        Route::get('/roles', AdminRolesIndexPage::class)
            ->middleware('can:role.view')
            ->name('roles.index');
        Route::get('/organizations', AdminOrganizationsIndexPage::class)
            ->middleware('can:opd.view')
            ->name('organizations.index');
        Route::get('/incident-categories', AdminIncidentCategoriesIndexPage::class)
            ->middleware('can:incident-category.view')
            ->name('incident-categories.index');
    });
});

Route::get('/test', function () {
    return view('test');
});

require __DIR__ . '/auth.php';
