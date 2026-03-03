<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

class TokenComposer
{
    /**
     * Associa i dati alla vista.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Rende la variabile 'token' disponibile in tutte le viste
        // a cui questo composer è associato.
        $view->with('token', session('jwt_token'));
    }
}