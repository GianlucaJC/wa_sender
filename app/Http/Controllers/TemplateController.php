<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TemplateController extends Controller
{
    /**
     * Mostra l'elenco dei template esistenti interrogando le API di Meta.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Simulazione di un controllo di autorizzazione.
        $is_admin = true;
        if (!$is_admin) {
            abort(403, 'Azione non autorizzata.');
        }

        $accounts = WhatsappAccount::all();
        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');
        $allTemplates = [];
        $error = null;

        if ($accounts->isEmpty()) {
            $error = 'Nessun account WhatsApp collegato. Impossibile recuperare i template.';
        } else {
            foreach ($accounts as $account) {
                try {
                    $url = "https://graph.facebook.com/{$apiVersion}/{$account->waba_id}/message_templates";
                    $response = Http::withToken($account->access_token)
                        ->get($url, ['fields' => 'name,status,category,language']);

                    $response->throw();

                    $templatesFromAccount = $response->json('data');
                    // Aggiungiamo l'informazione sull'account a ogni template per la visualizzazione
                    foreach ($templatesFromAccount as &$template) {
                        $template['account_name'] = $account->name;
                        $template['account_id'] = $account->id;
                    }
                    $allTemplates = array_merge($allTemplates, $templatesFromAccount);

                } catch (Throwable $e) {
                    $errorMessage = "Impossibile recuperare i template per l'account '{$account->name}'.";
                    Log::error($errorMessage . ' Dettaglio: ' . $e->getMessage());
                    $error = ($error ? $error . '<br>' : '') . $errorMessage;
                }
            }
        }

        return view('templates.index', [
            'is_admin' => $is_admin, 'templates' => $allTemplates, 'error' => $error
        ]);
    }

    /**
     * Mostra il form per creare un nuovo template.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Simulazione di un controllo di autorizzazione.
        // In un'applicazione reale, questo verrebbe da un sistema di autenticazione (es. $user->isAdmin()).
        $is_admin = true;
        $accounts = WhatsappAccount::all();

        return view('templates.create', [
            'is_admin' => $is_admin,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Invia un nuovo template a Meta per l'approvazione.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Simulazione di un controllo di autorizzazione server-side.
        $is_admin = true;
        if (!$is_admin) {
            abort(403, 'Azione non autorizzata.');
        }

        $validated = $request->validate([
            'whatsapp_account_id' => 'required|exists:whatsapp_accounts,id',
            'name' => 'required|string|max:512|regex:/^[a-z0-9_]+$/',
            'category' => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
            'language_code' => 'required|string|max:15',
            'body_text' => 'required|string',
        ]);

        $account = WhatsappAccount::findOrFail($validated['whatsapp_account_id']);
        $token = $account->access_token;
        $wabaId = $account->waba_id;
        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

        if (!$token || !$wabaId) {
            return back()->with('error', 'Credenziali non valide per l\'account selezionato.');
        }

        $url = "https://graph.facebook.com/{$apiVersion}/{$wabaId}/message_templates";

        // Costruisce il payload secondo le specifiche di Meta
        $payload = [
            'name' => $validated['name'],
            'language' => $validated['language_code'],
            'category' => $validated['category'],
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => $validated['body_text'],
                ],
                // Qui si potrebbero aggiungere HEADER, FOOTER, BUTTONS
            ],
        ];

        try {
            $response = Http::withToken($token)->post($url, $payload);

            if ($response->failed()) {
                $errorData = $response->json('error');
                $errorMessage = $errorData['message'] ?? 'Errore sconosciuto dall\'API di Meta.';
                Log::error('Errore invio template a Meta:', $errorData);
                return back()->with('error', "Errore API: {$errorMessage}")->withInput();
            }

            Log::info('Template inviato con successo a Meta per approvazione:', $response->json());
            return back()->with('success', 'Template inviato con successo per l\'approvazione! Controlla lo stato nella dashboard di Meta.');

        } catch (Throwable $e) {
            Log::error('Eccezione durante l\'invio del template a Meta: ' . $e->getMessage());
            return back()->with('error', 'Si è verificato un errore imprevisto. Controlla i log.')->withInput();
        }
    }
}