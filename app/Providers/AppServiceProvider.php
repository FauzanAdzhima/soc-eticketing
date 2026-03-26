<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent temporary upload token from expiring too quickly
        // when users spend longer time filling the incident form.
        config([
            'livewire.temporary_file_upload.max_upload_time' => 30,
        ]);
    }
}
