<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ManagesWhatsappAccount;

use App\Models\WhatsappAccount;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TemplateController extends Controller
{
    use ManagesWhatsappAccount;

    /**
     * Mostra l'elenco dei template esistenti interrogando le API di Meta.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $account = $this->getCurrentAccount();

        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');
        $templates = [];
        $error = null;

        if (!$account) {
            $error = 'Nessun account WhatsApp collegato. Impossibile recuperare i template.';
        } elseif ($account->name !== 'SIMULATE') {
            try {
                $token = $account->access_token; // Può lanciare DecryptException
                $url = "https://graph.facebook.com/{$apiVersion}/{$account->waba_id}/message_templates";
                $response = Http::withToken($token)
                    ->get($url, ['fields' => 'name,status,category,language']);

                $response->throw();

                $templates = $response->json('data');
                
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $errorMessage = "Impossibile leggere le credenziali per l'account '{$account->name}'.";
                Log::critical($errorMessage . " Verificare che l'APP_KEY sia corretta e che il token nel database sia valido.", ['exception' => $e]);
                $error = $errorMessage . ' Il token salvato potrebbe essere corrotto o la chiave di cifratura è cambiata.';
            } catch (Throwable $e) {
                $errorMessage = "Impossibile recuperare i template per l'account '{$account->name}'.";
                Log::error($errorMessage . ' Dettaglio: ' . $e->getMessage());
                $error = $errorMessage;
            }
        } else {
            // Per l'account SIMULATE, non facciamo nulla. La vista mostrerà una lista vuota.
            $error = "L'account di simulazione non ha template reali. La lista è vuota.";
        }

        return view('templates.index', [
            'templates' => $templates,
            'error' => $error,
            'token' => session('jwt_token'),
        ]);
    }

    /**
     * Mostra il form per creare un nuovo template.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $account = $this->getCurrentAccount();

        if (!$account) {
            return redirect()->route('campaigns.create', ['token' => session('jwt_token')])
                ->with('error', 'Nessun account WhatsApp associato. Impossibile creare template.');
        }

        return view('templates.create', [
            // Passiamo una collezione con un solo account per mantenere la compatibilità con la vista.
            'accounts' => [$account],
            'token' => session('jwt_token'),
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
        // Recupera l'account dell'utente corrente per l'autorizzazione.
        $wa_id = session('wa_id');
        if (!$wa_id) {
            return redirect()->route('templates.create', ['token' => session('jwt_token')])->with('error', 'Sessione non valida. Impossibile salvare il template.')->withInput();
        }

        $validated = $request->validate([
            // L'utente può creare template solo per il proprio account.
            'whatsapp_account_id' => ['required', 'exists:whatsapp_accounts,id', Rule::in([$wa_id])],
            'name' => 'required|string|max:512|regex:/^[a-z0-9_]+$/',
            'category' => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
            'language_code' => 'required|string|max:15',
            'body_text' => 'required|string',
        ], [
            'whatsapp_account_id.in' => 'Non sei autorizzato a creare template per questo account.',
            'name.regex' => 'Il nome del template può contenere solo lettere minuscole, numeri e underscore (es. mio_template_speciale).'
        ]);

        $account = WhatsappAccount::findOrFail($validated['whatsapp_account_id']);

        // Non è possibile creare template per l'account di simulazione, poiché richiede una chiamata API reale.
        if ($account->name === 'SIMULATE') {
            return redirect()->route('templates.create', ['token' => session('jwt_token')])
                ->with('error', 'Non è possibile creare template per l\'account di simulazione.')
                ->withInput();
        }

        try {
            $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');
            $token = $account->access_token;
            $wabaId = $account->waba_id;

            if (!$token || !$wabaId) {
                return redirect()->route('templates.create', ['token' => session('jwt_token')])->with('error', 'Credenziali non valide per l\'account selezionato.')->withInput();
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

            $response = Http::withToken($token)->post($url, $payload);

            if ($response->failed()) {
                $errorData = $response->json('error');
                $errorMessage = $errorData['message'] ?? 'Errore sconosciuto dall\'API di Meta.';
                Log::error('Errore invio template a Meta:', $errorData);
                return redirect()->route('templates.create', ['token' => session('jwt_token')])->with('error', "Errore API: {$errorMessage}")->withInput();
            }

            Log::info('Template inviato con successo a Meta per approvazione:', $response->json());
            return redirect()->route('templates.index', ['token' => session('jwt_token')])->with('success', 'Template inviato con successo per l\'approvazione! Controlla lo stato nella dashboard di Meta.');
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::critical("Errore di decrittazione del token per l'account '{$account->name}' durante la creazione del template.", ['exception' => $e]);
            return redirect()->route('templates.create', ['token' => session('jwt_token')])
                ->with('error', "Impossibile leggere le credenziali dell\'account. Il token salvato potrebbe essere corrotto o la chiave di cifratura è cambiata.")
                ->withInput();
        } catch (Throwable $e) {
            Log::error('Eccezione durante l\'invio del template a Meta: ' . $e->getMessage());
            return redirect()->route('templates.create', ['token' => session('jwt_token')])->with('error', 'Si è verificato un errore imprevisto. Controlla i log.')->withInput();
        }
    }
}