<?php

namespace App\Http\Controllers;

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

        $token = config('services.meta_whatsapp.token');
        $wabaId = config('services.meta_whatsapp.business_account_id');
        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

        if (!$token || !$wabaId) {
            return view('templates.index', [
                'is_admin' => $is_admin,
                'error' => 'Credenziali WhatsApp non configurate correttamente (WABA ID o Token mancanti).',
                'templates' => []
            ]);
        }

        $url = "https://graph.facebook.com/{$apiVersion}/{$wabaId}/message_templates";

        try {
            // Usiamo il parametro 'fields' per richiedere solo i dati che ci servono
            $response = Http::withToken($token)->get($url, ['fields' => 'name,status,category,language']);
            $response->throw(); // Lancia un'eccezione se la richiesta fallisce

            $templates = $response->json('data');

            return view('templates.index', ['is_admin' => $is_admin, 'templates' => $templates]);

        } catch (Throwable $e) {
            Log::error('Errore nel recuperare i template da Meta: ' . $e->getMessage());
            return view('templates.index', ['is_admin' => $is_admin, 'error' => 'Impossibile recuperare l\'elenco dei template. Controlla i log.', 'templates' => []]);
        }
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

        return view('templates.create', ['is_admin' => $is_admin]);
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
            'name' => 'required|string|max:512|regex:/^[a-z0-9_]+$/',
            'category' => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
            'language_code' => 'required|string|max:15',
            'body_text' => 'required|string',
        ]);

        $token = config('services.meta_whatsapp.token');
        $wabaId = config('services.meta_whatsapp.business_account_id');
        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

        if (!$token || !$wabaId) {
            return back()->with('error', 'Credenziali WhatsApp non configurate correttamente (WABA ID o Token mancanti).');
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