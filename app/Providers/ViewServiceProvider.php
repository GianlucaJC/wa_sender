<?php

namespace App\Providers;

use App\Http\View\Composers\TokenComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
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
        // Associa il TokenComposer a tutte le viste ('*').
        // Questo assicura che la variabile 'token' sia disponibile globalmente.
        View::composer('*', TokenComposer::class);
    }
}