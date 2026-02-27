<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsappAccountController extends Controller
{
    /**
     * Mostra l'elenco degli account WhatsApp collegati.
     */
    public function index()
    {
        $accounts = WhatsappAccount::all();
        return view('whatsapp_accounts.index', ['accounts' => $accounts]);
    }

    /**
     * Mostra la pagina per collegare un nuovo account.
     */
    public function create()
    {
        $facebook_client_id = config('services.meta_whatsapp.client_id');
        if (!$facebook_client_id) {
            // Per semplicità, non ho modificato il file config/services.php, ma dovresti aggiungere
            // 'client_id' => env('META_WHATSAPP_CLIENT_ID') all'array 'meta_whatsapp'.
            // Ho simulato la sua presenza qui per la vista.
            return view('whatsapp_accounts.create')->with('error', 'L\'ID Cliente di Facebook non è configurato. Imposta `META_WHATSAPP_CLIENT_ID` nel tuo file .env e aggiungilo a `config/services.php`.');
        }
        return view('whatsapp_accounts.create', ['facebook_client_id' => $facebook_client_id]);
    }

    /**
     * Salva un nuovo account WhatsApp collegato tramite Embedded Signup.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'accessToken' => 'required|string',
            'wabaId' => 'required|string',
            'phoneNumberId' => 'required|string',
            'businessName' => 'required|string',
            'phoneNumber' => 'required|string',
        ]);

        try {
            // Controlla se un account con lo stesso WABA ID o Phone Number ID esiste già
            $existing = WhatsappAccount::where('waba_id', $validated['wabaId'])
                ->orWhere('phone_number_id', $validated['phoneNumberId'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo account WhatsApp o numero di telefono è già stato collegato.'
                ], 409); // 409 Conflict
            }

            WhatsappAccount::create([
                'name' => $validated['name'],
                'access_token' => $validated['accessToken'],
                'waba_id' => $validated['wabaId'],
                'phone_number_id' => $validated['phoneNumberId'],
                'business_name' => $validated['businessName'],
                'phone_number_display' => $validated['phoneNumber'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account collegato con successo!',
                'redirect_url' => route('whatsapp-accounts.index')
            ]);

        } catch (Throwable $e) {
            Log::error('Errore durante il salvataggio dell\'account WhatsApp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore interno durante il salvataggio dell\'account. Controlla i log.'
            ], 500);
        }
    }

    /**
     * Rimuove un account WhatsApp collegato.
     */
    public function destroy(WhatsappAccount $whatsappAccount)
    {
        try {
            $whatsappAccount->delete();
            return redirect()->route('whatsapp-accounts.index')->with('success', 'Account rimosso con successo.');
        } catch (Throwable $e) {
            Log::error("Errore durante la rimozione dell'account WhatsApp #{$whatsappAccount->id}: " . $e->getMessage());
            return back()->with('error', 'Impossibile rimuovere l\'account. Si è verificato un errore.');
        }
    }
}