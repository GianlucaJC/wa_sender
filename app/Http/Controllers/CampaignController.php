<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\WhatsappAccount;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
        $account = WhatsappAccount::first();
        $templates = [];
        $templates_error = null;

        // Se un account è configurato, proviamo a recuperare i suoi template.
        if ($account) {
            $token = $account->access_token;
            $wabaId = $account->waba_id;
            $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

            try {
                $url = "https://graph.facebook.com/{$apiVersion}/{$wabaId}/message_templates";
                // Filtriamo per ottenere solo i template approvati, che sono gli unici utilizzabili
                $response = Http::withToken($token)->get($url, [
                    'fields' => 'name,status,components', // Chiediamo anche i components per future elaborazioni (variabili)
                    'status' => 'APPROVED',
                ]);
                $response->throw();
                $templates = $response->json('data');
            } catch (Throwable $e) {
                Log::error('Errore nel recuperare i template approvati da Meta: ' . $e->getMessage());
                $templates_error = 'Impossibile recuperare i template approvati da Meta. Controlla i log o le credenziali dell\'account configurato.';
            }
        } else {
            // Non ci sono account configurati nel database.
            $templates_error = 'Nessun account WhatsApp è stato configurato. Per favore, vai alla sezione "Account WhatsApp" e aggiungi un nuovo account.';
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

        // Recupera i dati della campagna dalla sessione, se presenti, per pre-compilare il form
        // quando si torna indietro dallo step 2.
        $campaignData = session()->get('campaign_creation_data');

        return view('welcome', [
            'account' => $account, // Passiamo il singolo account (o null)
            'templates' => $templates,
            'templates_error' => $templates_error,
            'campaignData' => $campaignData,
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
            'campaign_name' => $validated['campaign_name'],
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
            $file = $request->file('recipient_file');
            // Usiamo storeAs per preservare l'estensione originale del file,
            // che a volte viene interpretata erroneamente come .txt.
            // Generiamo un nome univoco per evitare sovrascritture.
            $filename = uniqid('file_', true) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('recipient_files', $filename, 'local');
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
        $campaignData = $request->session()->get('campaign_creation_data');

        if (!$campaignData) {
            return redirect()->route('campaigns.create')->with('error', 'Sessione della campagna scaduta. Per favore, ricomincia.');
        }

        $viewData = ['campaignData' => $campaignData];

        // Se la fonte è un file, leggiamo le intestazioni per il mapping
        if ($campaignData['recipient_source'] === 'file_upload' && !empty($campaignData['recipient_file_path'])) {
            try {
                $filePath = storage_path('app/' . $campaignData['recipient_file_path']);

                // AGGIUNTA DIAGNOSTICA: Verifichiamo se il file esiste fisicamente prima di leggerlo.
                if (!file_exists($filePath)) {
                    Log::error('File non trovato nel percorso di storage: ' . $filePath);
                    return redirect()->route('campaigns.create')->with('error', 'Errore critico: il file caricato non è stato trovato sul server. Potrebbe essere un problema di permessi sulla cartella `storage/app/recipient_files`.')->withInput($campaignData);
                }

                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                // NUOVA LOGICA: Usiamo funzioni native per i CSV, come richiesto
                if ($extension === 'csv') {
                    $headers = [];
                    if (($handle = fopen($filePath, "r")) !== FALSE) {
                        // Leggiamo la prima riga (le intestazioni) usando il punto e virgola come separatore
                        $headerData = fgetcsv($handle, 0, ";");
                        fclose($handle);

                        if ($headerData !== false) {
                            // Tentiamo di correggere la codifica se non è UTF-8
                            foreach ($headerData as $header) {
                                if ($header && mb_check_encoding($header, 'UTF-8') === false) {
                                    // Proviamo a convertire da una codifica comune di Windows
                                    $convertedHeader = mb_convert_encoding($header, 'UTF-8', 'ISO-8859-1');
                                    $headers[] = $convertedHeader !== false ? $convertedHeader : $header;
                                } else {
                                    $headers[] = $header;
                                }
                            }
                            $viewData['file_headers'] = array_filter($headers);
                        }
                    }

                    if (empty($viewData['file_headers'])) {
                        return redirect()->route('campaigns.create')->with('error', 'Impossibile leggere le intestazioni dal file CSV. Assicurati che il file non sia vuoto, sia codificato correttamente e usi il punto e virgola (;) come separatore.')->withInput($campaignData);
                    }
                } elseif (in_array($extension, ['xls', 'xlsx'])) {
                    // La logica per i file Excel è temporaneamente disabilitata per concentrarsi sui CSV.
                    // Verrà implementata in un secondo momento.
                    return redirect()->route('campaigns.create')->with('error', 'La lettura di file Excel (XLS, XLSX) non è ancora implementata. Per favore, usa un file CSV.')->withInput($campaignData);
                } else {
                    // Questo caso non dovrebbe verificarsi grazie alla validazione, ma è una sicurezza in più.
                    return redirect()->route('campaigns.create')->with('error', "Tipo di file non valido ('{$extension}'). Sono ammessi solo file CSV.")->withInput($campaignData);
                }

            } catch (Throwable $e) {
                $debugMessage = ' Dettaglio tecnico: ' . $e->getMessage();
                Log::error('Errore lettura file per mapping: ' . $e->getMessage());
                return redirect()->route('campaigns.create')->with('error', 'Errore durante la lettura del file. Assicurati che sia in un formato valido (CSV, XLS, XLSX) e non sia corrotto.' . $debugMessage)->withInput($campaignData);
            }
        }

        return view('step2', $viewData);
    }

    /**
     * Valida il file dei destinatari, normalizza i numeri e mostra un report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function validateFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'map_name' => 'required|string',
            'map_phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();

        $campaignData = $request->session()->get('campaign_creation_data');

        if (!$campaignData || $campaignData['recipient_source'] !== 'file_upload') {
            return redirect()->route('campaigns.create')->with('error', 'Sessione della campagna scaduta o non valida.');
        }

        $filePath = storage_path('app/' . $campaignData['recipient_file_path']);
        $mapName = $validated['map_name'];
        $mapPhone = $validated['map_phone'];

        $totalRows = 0;
        $normalizedCount = 0;
        $validRecipients = [];
        $invalidEntries = [];

        try {
            if (!is_readable($filePath)) {
                return redirect()->route('campaigns.create')->with('error', 'Errore critico: il file dei destinatari non è leggibile. Controllare i permessi della cartella `storage`.');
            }

            $handle = fopen($filePath, "r");
            $headers = fgetcsv($handle, 0, ";");

            $nameIndex = array_search($mapName, $headers);
            $phoneIndex = array_search($mapPhone, $headers);

            if ($phoneIndex === false) {
                fclose($handle);
                return redirect()->back()->with('error', 'La colonna del telefono mappata non è stata trovata nel file.')->withInput();
            }

            $lineNumber = 1;
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $lineNumber++;
                $totalRows++;

                $phoneNumberRaw = isset($data[$phoneIndex]) ? trim($data[$phoneIndex]) : '';
                $name = ($nameIndex !== false && isset($data[$nameIndex])) ? trim($data[$nameIndex]) : '';

                $validationResult = $this->normalizeAndValidatePhoneNumber($phoneNumberRaw);

                if ($validationResult['status'] === 'invalid') {
                    $invalidEntries[] = [
                        'line' => $lineNumber,
                        'name' => $name,
                        'phone' => $phoneNumberRaw,
                        'reason' => $validationResult['reason'],
                    ];
                } else {
                    if ($validationResult['status'] === 'normalized') {
                        $normalizedCount++;
                    }
                    $validRecipients[] = [
                        'name' => $name,
                        'phone_number' => $validationResult['number'],
                    ];
                }
            }
            fclose($handle);

            $report = [
                'total_rows' => $totalRows,
                'valid_count' => count($validRecipients),
                'invalid_count' => count($invalidEntries),
                'normalized_count' => $normalizedCount,
                'invalid_entries' => $invalidEntries,
            ];

            // Salva i destinatari validi in sessione per il prossimo step
            $request->session()->put('validated_recipients', $validRecipients);

            // Reindirizza indietro alla pagina step2, passando il report per mostrare il modal
            return redirect()->route('campaigns.step2')->with('validation_report', $report);

        } catch (Throwable $e) {
            Log::error("Errore durante la validazione del file per la campagna: " . $e->getMessage());
            return redirect()->route('campaigns.create')->with('error', 'Si è verificato un errore imprevisto durante la lettura del file.');
        }
    }

    /**
     * Avvia la campagna usando i destinatari validati salvati in sessione.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function launchCampaign(Request $request)
    {
        $campaignData = $request->session()->get('campaign_creation_data');
        $validatedRecipients = $request->session()->get('validated_recipients');

        if (!$campaignData || empty($validatedRecipients)) {
            return redirect()->route('campaigns.create')->with('error', 'Sessione scaduta o nessun destinatario valido trovato. Riprova.');
        }

        $account = WhatsappAccount::first();
        if (!$account) {
            // Anche se improbabile arrivare qui senza un account, è una sicurezza in più.
            return redirect()->route('campaigns.create')->with('error', 'Nessun account WhatsApp configurato. Impossibile avviare la campagna.');
        }

        // 1. Crea la Campagna nel database
        $campaign = Campaign::create([
            'whatsapp_account_id' => $account->id,
            'name' => $campaignData['campaign_name'],
            'message_template' => $campaignData['message_template'],
            'status' => 'pending',
            'total_recipients' => count($validatedRecipients),
        ]);

        // 2. Crea i destinatari e accoda i job
        foreach ($validatedRecipients as $rec) {
            $recipient = CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'phone_number' => $rec['phone_number'],
                'name' => $rec['name'],
                'params' => ['name' => $rec['name']],
                'status' => 'queued',
            ]);
            SendWhatsAppMessage::dispatch($recipient);
        }

        // 3. Aggiorna lo stato della campagna
        $campaign->update(['status' => 'processing']);

        // 4. Pulisci la sessione dai dati usati
        $request->session()->forget(['campaign_creation_data', 'validated_recipients']);

        // 5. Reindirizza alla pagina di avanzamento
        return redirect()->route('campaigns.progress', $campaign->id);
    }

    /**
     * Mostra la pagina di avanzamento di una campagna.
     */
    public function showProgress(Campaign $campaign)
    {
        return view('campaigns.progress', ['campaign' => $campaign]);
    }

    /**
     * Fornisce i dati di stato di una campagna per l'aggiornamento via AJAX.
     */
    public function getStatus(Campaign $campaign)
    {
        // Se la campagna è in elaborazione e tutti i job sono terminati, la segno come completata.
        if ($campaign->status === 'processing' && ($campaign->processed_count + $campaign->failed_count) >= $campaign->total_recipients) {
            $campaign->update(['status' => 'completed']);
        }

        return response()->json($campaign->only([
            'id', 'status', 'total_recipients', 'processed_count', 'failed_count'
        ]));
    }

    /**
     * Mostra l'elenco di tutte le campagne.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Recupera le campagne paginate, ordinate dalla più recente
        $campaigns = Campaign::latest()->paginate(15);

        return view('campaigns.index', ['campaigns' => $campaigns]);
    }

    /**
     * Mostra la pagina della documentazione.
     *
     * @return \Illuminate\View\View
     */
    public function showDocs()
    {
        return view('docs.index');
    }

    /**
     * Mostra la pagina dell'informativa sulla privacy.
     *
     * @return \Illuminate\View\View
     */
    public function showPrivacyPolicy()
    {
        return view('privacy.index');
    }

    /**
     * Normalizza e valida un numero di telefono.
     *
     * @param string $number
     * @return array
     */
    private function normalizeAndValidatePhoneNumber(string $number): array
    {
        // 1. Pulisce da spazi e caratteri comuni
        $cleanedNumber = trim(str_replace([' ', '-', '.', '(', ')', '/'], '', $number));

        if (empty($cleanedNumber)) {
            return ['status' => 'invalid', 'reason' => 'Numero vuoto'];
        }

        $isNormalized = false;

        // 2. Normalizzazione prefisso
        if (str_starts_with($cleanedNumber, '0039')) {
            $cleanedNumber = '+' . substr($cleanedNumber, 2);
            $isNormalized = true;
        } elseif (str_starts_with($cleanedNumber, '39') && strlen($cleanedNumber) > 10) {
            $cleanedNumber = '+' . $cleanedNumber;
            $isNormalized = true;
        } elseif (preg_match('/^3\d{8,9}$/', $cleanedNumber)) { // Cellulare italiano senza prefisso
            $cleanedNumber = '+39' . $cleanedNumber;
            $isNormalized = true;
        }

        // 3. Validazione finale (formato cellulare italiano +393XXXXXXXXX)
        if (preg_match('/^\+393\d{8,9}$/', $cleanedNumber)) {
            return [
                'status' => $isNormalized ? 'normalized' : 'valid',
                'number' => $cleanedNumber
            ];
        }

        return ['status' => 'invalid', 'reason' => 'Formato non riconosciuto o non italiano'];
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
            // 'whatsapp_account_id' => 'required|exists:whatsapp_accounts,id', // Rimosso, si usa l'account di default
            'recipient' => 'required|string|min:10', // Aggiunta una validazione base
            'message_template' => 'required|string',
        ]);

        try {
            $account = WhatsappAccount::first();
            if (!$account) {
                throw new \Exception('Nessun account WhatsApp è configurato nel sistema.');
            }

            $token = $account->access_token; // L'attributo viene decifrato automaticamente
            $phoneNumberId = $account->phone_number_id;
            $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

            if (!$token || !$phoneNumberId) {
                throw new \Exception('Credenziali non valide per l\'account selezionato.');
            }

            // SIMULAZIONE: Se il nome dell'account è 'SIMULATE', non inviamo realmente.
            if ($account->name === 'SIMULATE') {
                Log::info('SIMULATED test send to: ' . $validated['recipient']);
                return response()->json([
                    'message' => 'Messaggio di prova (simulato) inviato con successo.',
                    'message_id' => 'simulated_test_' . uniqid()
                ]);
            }

            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

            // Costruisce il payload per un messaggio template di test
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $validated['recipient'],
                'type' => 'template',
                'template' => [
                    'name' => $validated['message_template'],
                    'language' => ['code' => 'it'], // Assumiamo 'it', da rendere configurabile in futuro
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => 'Test'] // Parametro fittizio per la variabile {{1}}
                            ]
                        ]
                    ]
                ]
            ];

            $response = Http::withToken($token)->post($url, $payload);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                throw new \Exception("Errore API: {$errorMessage}");
            }

            $messageId = $response->json('messages')[0]['id'] ?? 'N/A';
            Log::info('Messaggio di test inviato a: ' . $validated['recipient'] . '. Message ID: ' . $messageId);

            return response()->json([
                'message' => 'Messaggio di prova inviato con successo.',
                'message_id' => $messageId
            ]);

        } catch (\Exception $e) {
            Log::error('Errore durante l\'invio del messaggio di test: ' . $e->getMessage());
            return response()->json(['message' => 'Impossibile inviare il messaggio di prova. Dettaglio: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gestisce l'upload asincrono del file e restituisce gli header.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Usiamo 'mimetypes' per essere più flessibili. Alcuni sistemi operativi
            // identificano i file CSV come 'text/plain' invece di 'text/csv'.
            'recipient_file' => 'required|file|max:10240|mimetypes:text/csv,text/plain,application/csv',
        ], ['recipient_file.mimetypes' => 'Sono ammessi solo file di tipo CSV.']);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $file = $request->file('recipient_file');
        $filename = uniqid('file_', true) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('recipient_files', $filename, 'local');
        $fullPath = storage_path('app/' . $filePath);

        $headers = [];
        if (($handle = fopen($fullPath, "r")) !== FALSE) {
            $headerData = fgetcsv($handle, 0, ";");
            fclose($handle);
            if ($headerData) {
                $headers = array_filter($headerData, fn($h) => !is_null($h) && $h !== '');
            }
        }

        if (empty($headers)) {
            Storage::disk('local')->delete($filePath);
            return response()->json(['success' => false, 'message' => 'Impossibile leggere le intestazioni dal file CSV. Assicurati che usi il punto e virgola (;) come separatore.'], 400);
        }

        return response()->json([
            'success' => true,
            'file_path' => $filePath,
            'headers' => $headers
        ]);
    }

    /**
     * Valida un file via AJAX, salva i destinatari validi in sessione e restituisce il report.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxValidate(Request $request)
    {
        $validated = $request->validate([
            'file_path' => 'required|string',
            'map_name' => 'required|string',
            'map_phone' => 'required|string',
        ]);

        $filePath = storage_path('app/' . $validated['file_path']);
        if (!is_readable($filePath)) {
            return response()->json(['success' => false, 'message' => 'File non trovato o non leggibile sul server.'], 404);
        }

        $totalRows = 0;
        $normalizedCount = 0;
        $validRecipients = [];
        $invalidEntries = [];

        $handle = fopen($filePath, "r");
        $headers = fgetcsv($handle, 0, ";");
        $nameIndex = array_search($validated['map_name'], $headers);
        $phoneIndex = array_search($validated['map_phone'], $headers);

        if ($phoneIndex === false) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'La colonna del telefono mappata non è stata trovata.'], 400);
        }

        $lineNumber = 1;
        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
            $lineNumber++;
            $totalRows++;
            $phoneNumberRaw = isset($data[$phoneIndex]) ? trim($data[$phoneIndex]) : '';
            $name = ($nameIndex !== false && isset($data[$nameIndex])) ? trim($data[$nameIndex]) : '';
            $validationResult = $this->normalizeAndValidatePhoneNumber($phoneNumberRaw);

            if ($validationResult['status'] === 'invalid') {
                $invalidEntries[] = ['line' => $lineNumber, 'name' => $name, 'phone' => $phoneNumberRaw, 'reason' => $validationResult['reason']];
            } else {
                if ($validationResult['status'] === 'normalized') $normalizedCount++;
                $validRecipients[] = ['name' => $name, 'phone_number' => $validationResult['number']];
            }
        }
        fclose($handle);

        $report = [
            'total_rows' => $totalRows,
            'valid_count' => count($validRecipients),
            'invalid_count' => count($invalidEntries),
            'normalized_count' => $normalizedCount,
            'invalid_entries' => $invalidEntries,
        ];

        $request->session()->put('validated_recipients', $validRecipients);

        return response()->json(['success' => true, 'report' => $report]);
    }

    /**
     * Gestisce l'avvio unificato della campagna dal form principale.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function launchUnified(Request $request)
    {
        $account = WhatsappAccount::first();
        if (!$account) {
            return back()->with('error', 'Nessun account WhatsApp configurato. Impossibile avviare la campagna.')->withInput();
        }

        $validated = $request->validate([
            // 'whatsapp_account_id' => 'required|exists:whatsapp_accounts,id', // Rimosso
            'campaign_name' => 'required|string|max:255',
            'message_template' => 'required|string',
            'recipient_source' => 'required|in:fillea_tabulato,assemblea_generale,organismi_dirigenti,file_upload',
        ]);

        if ($validated['recipient_source'] === 'file_upload') {
            $validatedRecipients = $request->session()->get('validated_recipients');

            if (empty($validatedRecipients)) {
                return back()->with('error', 'Nessun destinatario valido trovato. Esegui nuovamente il processo di caricamento e validazione del file.')->withInput();
            }

            $campaign = Campaign::create([
                'whatsapp_account_id' => $account->id,
                'name' => $validated['campaign_name'],
                'message_template' => $validated['message_template'],
                'status' => 'pending',
                'total_recipients' => count($validatedRecipients),
            ]);

            foreach ($validatedRecipients as $rec) {
                $recipient = CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'phone_number' => $rec['phone_number'],
                    'name' => $rec['name'],
                    'params' => ['name' => $rec['name']],
                    'status' => 'queued',
                ]);
                SendWhatsAppMessage::dispatch($recipient);
            }

            $campaign->update(['status' => 'processing']);
            $request->session()->forget('validated_recipients');

            return redirect()->route('campaigns.progress', $campaign->id);
        } else {
            // Logica per le altre fonti di destinatari (da implementare)
            return back()->with('error', 'La modalità di invio selezionata non è ancora stata implementata.')->withInput();
        }
    }
}