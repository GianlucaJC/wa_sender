<?php

namespace App\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    /**
     * Mostra il form per creare una nuova campagna.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $templates = [];
        $templates_error = null;

        $token = config('services.meta_whatsapp.token');
        $wabaId = config('services.meta_whatsapp.business_account_id');
        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

        if ($token && $wabaId) {
            try {
                $url = "https://graph.facebook.com/{$apiVersion}/{$wabaId}/message_templates";
                // Filtriamo per ottenere solo i template approvati, che sono gli unici utilizzabili
                $response = Http::withToken($token)->get($url, [
                    'fields' => 'name,status,components', // Chiediamo anche i components per future elaborazioni (variabili)
                    'status' => 'APPROVED'
                ]);
                $response->throw();
                $templates = $response->json('data');
            } catch (Throwable $e) {
                Log::error('Errore nel recuperare i template approvati da Meta: ' . $e->getMessage());
                $templates_error = 'Impossibile recuperare i template approvati da Meta. Controlla i log.';
            }
        } else {
            $templates_error = 'Credenziali WhatsApp (WABA ID o Token) non configurate per recuperare i template.';
        }

        // Se non sono stati trovati template approvati, ne aggiungiamo alcuni di esempio per lo sviluppo
        if (empty($templates)) {
            $templates = [
                [
                    'name' => 'messaggio_simulato_1',
                    'status' => 'APPROVED',
                    'components' => [
                        ['type' => 'BODY', 'text' => 'Ciao {{1}}, ti confermiamo l\'iscrizione al servizio. Grazie!']
                    ]
                ],
                [
                    'name' => 'messaggio_simulato_con_variabili',
                    'status' => 'APPROVED',
                    'components' => [
                        ['type' => 'BODY', 'text' => 'Gentile {{1}}, la sua pratica n. {{2}} è stata aggiornata.']
                    ]
                ]
            ];
            // Aggiungiamo una nota all'errore esistente o ne creiamo uno nuovo
            $templates_error = $templates_error
                ? $templates_error . ' Vengono mostrati template di esempio per continuare.'
                : 'Nessun template approvato trovato. Vengono mostrati template di esempio per continuare.';
        }

        return view('welcome', [
            'templates' => $templates,
            'templates_error' => $templates_error,
        ]);
    }

    /**
     * Salva i dati della nuova campagna e reindirizza alla vista dei destinatari.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request)
    {
        $messages = [
            'required' => 'Il campo :attribute è obbligatorio.',
            'string' => 'Il campo :attribute deve essere una stringa.',
            'max' => 'Il campo :attribute non può superare :max caratteri o kilobyte.',
            'url' => 'Il campo :attribute deve essere un URL valido.',
            'file' => 'Il campo :attribute deve essere un file.',
            'mimes' => 'Il file :attribute deve essere di tipo: :values.',
            'recipient_file.required_if' => 'È necessario caricare un file quando si sceglie la modalità "Da file Excel/CSV".',
        ];

        $attributes = [
            'campaign_name' => 'Nome Campagna',
            'recipient_source' => 'Modalità di Invio',
            'recipient_file' => 'File Destinatari',
            'message_template' => 'Template Messaggio',
            'attachment_link' => 'Link da allegare',
            'attachment_pdf' => 'PDF da allegare',
        ];

        $validator = Validator::make($request->all(), [
            'campaign_name' => 'required|string|max:255',
            'recipient_source' => 'required|in:fillea_tabulato,assemblea_generale,organismi_dirigenti,file_upload',
            'recipient_file' => [
                'required_if:recipient_source,file_upload',
                'file',
                'max:10240', // max 10MB
                function ($attribute, $value, $fail) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        $extension = strtolower($value->getClientOriginalExtension());
                        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
                            $fail('Il tipo di file per i destinatari non è valido. Sono ammessi solo file CSV, XLSX, o XLS.');
                        }
                    }
                },
            ],
            'message_template' => 'required|string',
            'attachment_link' => 'nullable|url',
            'attachment_pdf' => 'nullable|file|mimes:pdf|max:5120', // max 5MB
        ], $messages, $attributes);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Salva i dati base della campagna in sessione
        $campaignData = [
            'name' => $validated['campaign_name'],
            'recipient_source' => $validated['recipient_source'],
            'message_template' => $validated['message_template'],
            'attachment_link' => $validated['attachment_link'] ?? null,
            'attachment_pdf_path' => null,
            'recipient_file_path' => null,
        ];

        // Gestisci gli allegati (se presenti)
        if ($request->hasFile('attachment_pdf')) {
            $path = $request->file('attachment_pdf')->store('campaign_attachments', 'local');
            $campaignData['attachment_pdf_path'] = $path;
        }

        if ($validated['recipient_source'] === 'file_upload') {
            $filePath = $request->file('recipient_file')->store('recipient_files', 'local');
            $campaignData['recipient_file_path'] = $filePath;
        }

        // Metti tutti i dati in sessione
        $request->session()->put('campaign_creation_data', $campaignData);

        // Reindirizza allo step 2
        return redirect()->route('campaigns.step2');
    }

    /**
     * Mostra la seconda fase della creazione della campagna (anteprima destinatari o mapping file).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function step2(Request $request)
    {
        // Per ora, passiamo solo i dati dalla sessione alla vista.
        // La logica di lettura file/db verrà implementata successivamente.
        return view('step2', [
            'campaignData' => $request->session()->get('campaign_creation_data')
        ]);
    }

    /**
     * Invia un messaggio di test a un singolo destinatario accodando un job.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'recipient' => 'required|string|min:10', // Aggiunta una validazione base
            'message_template' => 'required|string',
        ]);

        try {
            // Invece di inviare direttamente, mettiamo il messaggio in coda.
            // Questo rende l'applicazione più veloce e resiliente.
            SendWhatsAppMessage::dispatch($validated['recipient'], $validated['message_template']);

            // Log per tracciamento
            Log::info('Messaggio di test accodato per: ' . $validated['recipient']);

            return response()->json([
                'message' => 'Messaggio di prova accodato per l\'invio.',
                'message_id' => 'job_queued' // L'ID reale sarà gestito dal worker
            ]);

        } catch (\Exception $e) {
            Log::error('Errore durante l\'accodamento del messaggio di test: ' . $e->getMessage());

            return response()->json(['message' => 'Impossibile accodare il messaggio di prova.'], 500);
        }
    }
}