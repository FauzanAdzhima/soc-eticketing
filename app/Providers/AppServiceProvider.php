<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once base_path('app/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);

        // Prevent temporary upload token from expiring too quickly
        // when users spend longer time filling the incident form.
        // Allow signed temporary URLs for PDF preview (e.g. chat composer).
        $previewMimes = config('livewire.temporary_file_upload.preview_mimes', []);
        if (is_array($previewMimes) && ! in_array('pdf', $previewMimes, true)) {
            $previewMimes[] = 'pdf';
        }

        config([
            'livewire.temporary_file_upload.max_upload_time' => 30,
            'livewire.temporary_file_upload.preview_mimes' => $previewMimes,
        ]);
    }
}
