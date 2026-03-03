<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class WhatsappAccountController extends Controller
{
    /**
     * Mostra l'elenco degli account WhatsApp collegati.
     */
    public function index()
    {
        $this->authorizeAdmin();
        $accounts = WhatsappAccount::all();
        return view('whatsapp_accounts.index', [
            'accounts' => $accounts,
            'token' => session('jwt_token'),
        ]);
    }

    /**
     * Mostra la pagina per creare un nuovo account (per admin).
     */
    public function create()
    {
        $this->authorizeAdmin();
        return view('whatsapp_accounts.create', ['token' => session('jwt_token')]);
    }

    /**
     * Salva un nuovo account WhatsApp inserito dall'admin.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'access_token' => 'required|string',
            'waba_id' => 'required|string|unique:whatsapp_accounts,waba_id',
            'phone_number_id' => 'required|string|unique:whatsapp_accounts,phone_number_id',
            'business_name' => 'required|string|max:255',
            'phone_number_display' => 'required|string|max:255',
        ]);

        try {
            WhatsappAccount::create($validated);

            return redirect()->route('whatsapp-accounts.index', ['token' => $request->session()->get('jwt_token')])
                ->with('success', 'Account WhatsApp creato con successo!');

        } catch (Throwable $e) {
            Log::error('Errore durante il salvataggio dell\'account WhatsApp: ' . $e->getMessage());
            return back()->with('error', 'Si è verificato un errore interno durante il salvataggio dell\'account. Controlla i log.')->withInput();
        }
    }

    /**
     * Mostra il form per modificare un account esistente.
     *
     * @param  \App\Models\WhatsappAccount  $whatsappAccount
     * @return \Illuminate\View\View
     */
    public function edit(WhatsappAccount $whatsappAccount)
    {
        $this->authorizeAdmin();
        return view('whatsapp_accounts.edit', [
            'account' => $whatsappAccount,
            'token' => session('jwt_token'),
        ]);
    }

    /**
     * Aggiorna un account WhatsApp esistente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WhatsappAccount  $whatsappAccount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, WhatsappAccount $whatsappAccount)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'access_token' => 'nullable|string', // L'admin può lasciarlo vuoto per non aggiornarlo
            'waba_id' => ['required', 'string', Rule::unique('whatsapp_accounts')->ignore($whatsappAccount->id)],
            'phone_number_id' => ['required', 'string', Rule::unique('whatsapp_accounts')->ignore($whatsappAccount->id)],
            'business_name' => 'required|string|max:255',
            'phone_number_display' => 'required|string|max:255',
        ]);

        try {
            $updateData = $validated;
            // Non aggiornare il token se il campo è stato lasciato vuoto
            if (empty($validated['access_token'])) {
                unset($updateData['access_token']);
            }

            $whatsappAccount->update($updateData);

            return redirect()->route('whatsapp-accounts.index', ['token' => $request->session()->get('jwt_token')])
                ->with('success', 'Account WhatsApp aggiornato con successo!');

        } catch (Throwable $e) {
            Log::error("Errore durante l'aggiornamento dell'account WhatsApp #{$whatsappAccount->id}: " . $e->getMessage());
            return back()->with('error', 'Si è verificato un errore interno durante l\'aggiornamento dell\'account.')->withInput();
        }
    }

    /**
     * Rimuove un account WhatsApp collegato.
     */
    public function destroy(WhatsappAccount $whatsappAccount)
    {
        $this->authorizeAdmin();
        try {
            $whatsappAccount->delete();
            return redirect()->route('whatsapp-accounts.index', ['token' => session('jwt_token')])->with('success', 'Account rimosso con successo.');
        } catch (Throwable $e) {
            Log::error("Errore durante la rimozione dell'account WhatsApp #{$whatsappAccount->id}: " . $e->getMessage());
            return back()->with('error', 'Impossibile rimuovere l\'account. Si è verificato un errore.');
        }
    }

    /**
     * Verifica se l'utente ha i privilegi di amministratore.
     */
    private function authorizeAdmin(): void
    {
        // Il valore di 'is_admin' viene impostato dal middleware 'VerifyJwtToken'
        if (!session('is_admin', false)) {
            abort(403, 'Azione non autorizzata. Accesso riservato agli amministratori.');
        }
    }
}