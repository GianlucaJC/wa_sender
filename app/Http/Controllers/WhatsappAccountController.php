<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        // Mostra un semplice form per la creazione manuale.
        return view('whatsapp_accounts.create', ['token' => session('jwt_token')]);
    }

    /**
     * Salva un nuovo account WhatsApp inserito dall'admin.
     * Questo metodo gestisce un form di inserimento manuale.
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
            // L'attributo 'access_token' verrà automaticamente cifrato dal model
            // grazie al cast 'encrypted'. Assegniamo i campi esplicitamente per
            // garantire che il processo di cifratura venga sempre attivato.
            $account = new WhatsappAccount();
            $account->name = $validated['name'];
            $account->business_name = $validated['business_name'];
            $account->phone_number_display = $validated['phone_number_display'];
            $account->waba_id = $validated['waba_id'];
            $account->phone_number_id = $validated['phone_number_id'];
            $account->access_token = $validated['access_token'];
            $account->save();

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
            'facebook_client_id' => config('services.meta_whatsapp.client_id'),
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

        if ($whatsappAccount->name === 'SIMULATE') {
            return redirect()->route('whatsapp-accounts.index', ['token' => $request->session()->get('jwt_token')])
                ->with('error', 'L\'account di simulazione non può essere modificato.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'access_token' => 'nullable|string', // L'admin può lasciarlo vuoto per non aggiornarlo
            'waba_id' => ['required', 'string', Rule::unique('whatsapp_accounts')->ignore($whatsappAccount->id)],
            'phone_number_id' => ['required', 'string', Rule::unique('whatsapp_accounts')->ignore($whatsappAccount->id)],
            'business_name' => 'required|string|max:255',
            'phone_number_display' => 'required|string|max:255',
        ]);

        try {
            // Assegniamo esplicitamente i campi per garantire il corretto funzionamento dei mutator/cast.
            $whatsappAccount->name = $validated['name'];
            $whatsappAccount->business_name = $validated['business_name'];
            $whatsappAccount->phone_number_display = $validated['phone_number_display'];
            $whatsappAccount->waba_id = $validated['waba_id'];
            $whatsappAccount->phone_number_id = $validated['phone_number_id'];

            // Aggiorniamo il token solo se ne è stato fornito uno nuovo.
            // L'attributo 'encrypted' nel modello si occuperà della cifratura.
            if (!empty($validated['access_token'])) {
                $whatsappAccount->access_token = $validated['access_token'];
            }
            $whatsappAccount->save();

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
            // Aggiungiamo un controllo per non permettere la cancellazione dell'account di simulazione
            if ($whatsappAccount->name === 'SIMULATE') {
                return back()->with('error', 'L\'account di simulazione non può essere rimosso.');
            }

            // Aggiungiamo un controllo per non permettere la cancellazione se ci sono campagne associate
            if ($whatsappAccount->campaigns()->exists()) {
                return back()->with('error', 'Impossibile rimuovere l\'account: esistono campagne associate. Rimuovi prima le campagne.');
            }

            $whatsappAccount->delete();
            return redirect()->route('whatsapp-accounts.index', ['token' => session('jwt_token')])->with('success', 'Account WhatsApp rimosso con successo.');
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
        // L'utente è considerato admin se il suo identificativo utente è 'F0001'.
        // Questo valore viene passato nel token JWT e salvato in sessione.
        if (session('user') !== 'F0001') {
            Log::warning('Tentativo di accesso non autorizzato alla sezione admin.', [
                'wa_id' => session('wa_id'),
                'user' => session('user'),
                'ip_address' => request()->ip()
            ]);
            abort(redirect()->route('campaigns.create', ['token' => session('jwt_token')])->with('error', 'Azione non autorizzata. Accesso riservato agli amministratori.'));
        }
    }
}