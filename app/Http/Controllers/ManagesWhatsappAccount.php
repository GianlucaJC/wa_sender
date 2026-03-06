<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;

/**
 * Trait per gestire il recupero dell'account WhatsApp corrente.
 */
trait ManagesWhatsappAccount
{
    /**
     * Recupera l'account WhatsApp corrente dalla sessione, con fallback a 'SIMULATE'.
     */
    private function getCurrentAccount(): ?WhatsappAccount
    {
        $wa_id = session('wa_id');
        return !empty($wa_id) ? WhatsappAccount::find($wa_id) : WhatsappAccount::where('name', 'SIMULATE')->first();
    }
}