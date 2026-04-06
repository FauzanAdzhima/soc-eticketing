<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketChatAttachmentController;
use App\Http\Controllers\TicketEvidenceController;
use App\Http\Controllers\TicketReportImageController;
use App\Livewire\Pages\Admin\IncidentCategories\IndexPage as AdminIncidentCategoriesIndexPage;
use App\Livewire\Pages\Admin\Organizations\IndexPage as AdminOrganizationsIndexPage;
use App\Livewire\Pages\Admin\Roles\IndexPage as AdminRolesIndexPage;
use App\Livewire\Pages\Admin\Users\IndexPage as AdminUsersIndexPage;
use App\Livewire\Pages\DashboardPage;
use App\Livewire\Pages\ProfilePage;
use App\Livewire\Pages\Tickets\IndexPage as TicketsIndexPage;
use App\Livewire\Pages\Tickets\TicketChatPage;
use App\Livewire\Pages\Tickets\TicketCoordinatorReportEditorPage;
use App\Livewire\Pages\Tickets\TicketAnalysisPage;
use App\Livewire\Pages\Tickets\TicketRespondPage;
use App\Livewire\Pages\Tickets\TrackTicketChatPage;
use App\Livewire\Pages\Tickets\TrackTicketSearchPage;
use App\Models\Ticket;
use App\Models\TicketReport;
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

Route::get('/tickets/track', TrackTicketSearchPage::class)
    ->middleware('throttle:60,1')
    ->name('tickets.track.search');

Route::get('/tickets/track/{ticket}/{token}/messages/{message}/attachment', [TicketChatAttachmentController::class, 'showGuest'])
    ->middleware('throttle:120,1')
    ->where('token', '[^/]+')
    ->name('tickets.track.chat.attachment');

Route::get('/tickets/track/{ticket}/{token}', TrackTicketChatPage::class)
    ->middleware('throttle:30,1')
    ->where('token', '[^/]+')
    ->name('tickets.track.chat');

Route::middleware('auth')->group(function () {
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/profile/edit', function () {
        return redirect()->route('profile');
    })->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tickets/{ticket}/chat/messages/{message}/attachment', [TicketChatAttachmentController::class, 'showStaff'])
        ->middleware('verified')
        ->name('tickets.chat.attachment.show');

    Route::get('/tickets/{ticket}/chat', TicketChatPage::class)
        ->middleware(['verified', 'can:ticket.chat.view'])
        ->name('tickets.chat');

    Route::get('/tickets', TicketsIndexPage::class)
        ->middleware('can:ticket.view')
        ->name('tickets.index');
    Route::redirect('/tickets/assigned', '/tickets?scope=analyst')
        ->middleware(['can:ticket.view']);
    Route::get('/tickets/{ticket}/analysis', TicketAnalysisPage::class)
        ->middleware(['can:analyze,ticket'])
        ->name('tickets.analysis');
    Route::get('/tickets/{ticket}/respond', TicketRespondPage::class)
        ->middleware(['can:respond,ticket'])
        ->name('tickets.respond');
    Route::get('/tickets/evidence/{evidence}', [TicketEvidenceController::class, 'show'])
        ->middleware('can:ticket.view')
        ->name('tickets.evidence.show');
    Route::get('/tickets/{ticket}', function (Ticket $ticket) {
        Gate::authorize('view', $ticket);

        return redirect()->route('tickets.index', ['ticket' => $ticket->public_id]);
    })->name('tickets.show');

    Route::get('/tickets/{ticket}/reports/edit', TicketCoordinatorReportEditorPage::class)
        ->middleware(['can:manageIncidentReport,ticket'])
        ->name('tickets.reports.edit');

    Route::get('/tickets/{ticket}/reports/{report}/print', function (Ticket $ticket, TicketReport $report) {
        abort_unless((int) $report->ticket_id === (int) $ticket->id, 404);

        Gate::authorize('manageIncidentReport', $ticket);

        return view('tickets.reports.print', [
            'ticket' => $ticket,
            'ticketReport' => $report,
        ]);
    })->name('tickets.reports.print');

    Route::post('/tickets/{ticket}/reports/images', [TicketReportImageController::class, 'store'])
        ->middleware(['can:manageIncidentReport,ticket'])
        ->name('tickets.reports.images.store');
    Route::get('/tickets/{ticket}/reports/images/{path}', [TicketReportImageController::class, 'show'])
        ->where('path', '.*')
        ->middleware(['can:manageIncidentReport,ticket'])
        ->name('tickets.reports.images.show');

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
