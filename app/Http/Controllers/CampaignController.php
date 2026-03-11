<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ManagesWhatsappAccount;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\WhatsappAccount;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    use ManagesWhatsappAccount;

    /**
     * Mostra il form per creare una nuova campagna.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $account = $this->getCurrentAccount();

        $templates = [];
        $templates_error = null;

        // Se un account è configurato, proviamo a recuperare i suoi template.
        if ($account) {
            // Non tentare di recuperare template reali per l'account di simulazione.
            // Il flusso procederà e userà i template di esempio più avanti.
            if ($account->name !== 'SIMULATE') {
                try {
                    // Con il modello Portfolio, il token è quello del System User, centralizzato.
                    $token = config('services.meta_whatsapp.system_user_token');
                    $wabaId = $account->waba_id;
                    $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

                    $url = "https://graph.facebook.com/{$apiVersion}/{$wabaId}/message_templates";
                    // Filtriamo per ottenere solo i template approvati, che sono gli unici utilizzabili
                    $response = Http::withToken($token)->get($url, [
                        'fields' => 'name,status,language,components', // Chiediamo anche i components e la lingua
                        'status' => 'APPROVED',
                    ]);
                    $response->throw();
                    $templates = $response->json('data');
                } catch (Throwable $e) {
                    Log::error('Errore nel recuperare i template approvati da Meta: ' . $e->getMessage());
                    $templates_error = 'Impossibile recuperare i template approvati da Meta. Controlla i log o le credenziali dell\'account configurato.';
                }
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

        // Controlla se l'utente ha i privilegi di amministratore
        $isAdmin = session('user') === 'F0001';

        return view('welcome', [
            'account' => $account, // Passiamo il singolo account (o null)
            'templates' => $templates,
            'templates_error' => $templates_error,
            'campaignData' => $campaignData,
            'token' => session('jwt_token'),
            'isAdmin' => $isAdmin,
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
        return redirect()->route('campaigns.step2', ['token' => $request->session()->get('jwt_token')]);
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
            return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Sessione della campagna scaduta. Per favore, ricomincia.');
        }
        $viewData = [
            'campaignData' => $campaignData,
            'token' => $request->session()->get('jwt_token'),
        ];

        // Se è stato selezionato un template, ne analizziamo le variabili
        if (!empty($campaignData['message_template'])) {
            // Dobbiamo recuperare di nuovo la lista dei template per trovare quello selezionato
            $account = $this->getCurrentAccount();
            if ($account) {
                $templateDetails = $this->fetchTemplateDetails($account, $campaignData['message_template']);
                if ($templateDetails) {
                    $bodyComponent = collect($templateDetails['components'])->firstWhere('type', 'BODY');
                    if ($bodyComponent && isset($bodyComponent['text'])) {
                        preg_match_all('/\{\{(\d+)\}\}/', $bodyComponent['text'], $matches);
                        $viewData['variable_count'] = !empty($matches[1]) ? max($matches[1]) : 0;
                    }
                }
            }
            if (!isset($viewData['variable_count'])) {
                $viewData['variable_count'] = 0;
            }
        }

        // Se la fonte è un file, leggiamo le intestazioni per il mapping
        if ($campaignData['recipient_source'] === 'file_upload' && !empty($campaignData['recipient_file_path'])) {
            try {
                $filePath = storage_path('app/' . $campaignData['recipient_file_path']);

                if (!file_exists($filePath)) {
                    Log::error('File non trovato nel percorso di storage: ' . $filePath);
                    return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Errore critico: il file caricato non è stato trovato sul server. Potrebbe essere un problema di permessi sulla cartella `storage/app/recipient_files`.')->withInput($campaignData);
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
                        return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Impossibile leggere le intestazioni dal file CSV. Assicurati che il file non sia vuoto, sia codificato correttamente e usi il punto e virgola (;) come separatore.')->withInput($campaignData);
                    }
                } elseif (in_array($extension, ['xls', 'xlsx'])) {
                    // Usa Maatwebsite/Excel per leggere gli header
                    $headings = (new HeadingRowImport)->toArray($filePath);
                    if (isset($headings[0][0])) {
                        $viewData['file_headers'] = array_filter($headings[0][0]);
                    }

                    if (empty($viewData['file_headers'])) {
                        return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Impossibile leggere le intestazioni dal file Excel. Assicurati che il file non sia vuoto e che la prima riga contenga le intestazioni.')->withInput($campaignData);
                    }
                } else {
                    // Questo caso non dovrebbe verificarsi grazie alla validazione, ma è una sicurezza in più.
                    return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', "Tipo di file non valido ('{$extension}'). Sono ammessi solo file CSV.")->withInput($campaignData);
                }

            } catch (Throwable $e) {
                $debugMessage = ' Dettaglio tecnico: ' . $e->getMessage();
                Log::error('Errore lettura file per mapping: ' . $e->getMessage());
                return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Errore durante la lettura del file. Assicurati che sia in un formato valido (CSV, XLS, XLSX) e non sia corrotto.' . $debugMessage)->withInput($campaignData);
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
            'map_phone' => 'required|string',
            'map_vars' => 'present|array',
            'map_vars.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();

        $campaignData = $request->session()->get('campaign_creation_data');

        if (!$campaignData || $campaignData['recipient_source'] !== 'file_upload') {
            return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Sessione della campagna scaduta o non valida.');
        }

        $filePath = storage_path('app/' . $campaignData['recipient_file_path']);
        $mapVars = $validated['map_vars'];

        $totalRows = 0;
        $normalizedCount = 0;
        $validRecipients = [];
        $invalidEntries = [];

        try {
            if (!is_readable($filePath)) {
                return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Errore critico: il file dei destinatari non è leggibile. Controllare i permessi della cartella `storage`.');
            }

            $dataCollection = $this->getFileDataAsCollection($filePath);
            
            $lineNumber = 1;
            foreach ($dataCollection as $row) {
                $lineNumber++;
                $totalRows++;

                $phoneNumberRaw = isset($row[$mapPhone]) ? trim($row[$mapPhone]) : '';
                
                $params = [];
                foreach ($mapVars as $varName) {
                    $params[] = ($varName && isset($row[$varName])) ? trim($row[$varName]) : '';
                }

                $validationResult = $this->normalizeAndValidatePhoneNumber($phoneNumberRaw);

                if ($validationResult['status'] === 'invalid') {
                    $invalidEntries[] = [
                        'line' => $lineNumber,
                        'name' => $params[0] ?? '',
                        'phone' => $phoneNumberRaw,
                        'reason' => $validationResult['reason'],
                    ];
                } else {
                    if ($validationResult['status'] === 'normalized') $normalizedCount++;
                    $validRecipients[] = [
                        'phone_number' => $validationResult['number'],
                        'params' => $params,
                    ];
                }
            }

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
            return redirect()->route('campaigns.step2', ['token' => $request->session()->get('jwt_token')])->with('validation_report', $report);

        } catch (Throwable $e) {
            Log::error("Errore durante la validazione del file per la campagna: " . $e->getMessage());
            return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Si è verificato un errore imprevisto durante la lettura del file.');
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
            return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Sessione scaduta o nessun destinatario valido trovato. Riprova.');
        }

        $account = $this->getCurrentAccount();
        if (!$account) {
            return redirect()->route('campaigns.create', ['token' => $request->session()->get('jwt_token')])->with('error', 'Nessun account WhatsApp configurato. Impossibile avviare la campagna.');
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
                'name' => $rec['params'][0] ?? null, // Usiamo il primo parametro come nome di riferimento
                'params' => $rec['params'],
                'status' => 'queued',
            ]);
            SendWhatsAppMessage::dispatch($recipient);
        }

        // 3. Aggiorna lo stato della campagna
        $campaign->update(['status' => 'processing']);

        // 4. Pulisci la sessione dai dati usati
        $request->session()->forget(['campaign_creation_data', 'validated_recipients']);

        // 5. Reindirizza alla pagina di avanzamento
        return redirect()->route('campaigns.progress', ['campaign' => $campaign->id, 'token' => $request->session()->get('jwt_token')]);
    }

    /**
     * Mostra la pagina di avanzamento di una campagna.
     */
    public function showProgress(Campaign $campaign)
    {
        // La vista ora caricherà i dati dei destinatari in modo asincrono tramite DataTables.
        // Passiamo solo i dati della campagna.
        return view('campaigns.progress', [
            'campaign' => $campaign,
            'token' => session('jwt_token'),
        ]);
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
     * Fornisce i dati dei destinatari per DataTables con paginazione, ricerca e ordinamento server-side.
     */
    public function getRecipientsData(Request $request, Campaign $campaign)
    {
        $query = CampaignRecipient::where('campaign_id', $campaign->id);

        $totalRecords = $query->count(); // Totale record senza filtri

        // Ricerca globale
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('phone_number', 'like', "%{$searchValue}%")
                  ->orWhere('status', 'like', "%{$searchValue}%")
                  ->orWhere('message_id', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count(); // Totale record dopo il filtro

        // Ordinamento
        if ($request->filled('order')) {
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $columnName = $request->input('columns')[$columnIndex]['data'];
            $direction = $order['dir'];

            // Mappatura sicura per evitare SQL injection su nomi di colonna
            $allowedColumns = ['id', 'name', 'phone_number', 'status', 'processed_at', 'message_id'];
            if (in_array($columnName, $allowedColumns)) {
                $query->orderBy($columnName, $direction);
            } else {
                $query->orderBy('created_at', 'asc'); // Fallback
            }
        } else {
            $query->orderBy('created_at', 'asc');
        }

        // Paginazione
        if ($request->filled('length') && $request->input('length') != -1) {
            $query->skip($request->input('start'))->take($request->input('length'));
        }

        $recipients = $query->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $recipients
        ]);
    }

    /**
     * Mostra l'elenco di tutte le campagne.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // La vista ora caricherà i dati delle campagne in modo asincrono tramite DataTables.
        return view('campaigns.index', [
            'token' => session('jwt_token'),
        ]);
    }

    /**
     * Fornisce i dati delle campagne per DataTables con paginazione, ricerca e ordinamento server-side.
     */
    public function getCampaignsData(Request $request)
    {
        $account = $this->getCurrentAccount();
        $query = Campaign::where('whatsapp_account_id', $account?->id);

        // Controlla e aggiorna lo stato delle campagne "bloccate" in 'processing'
        // prima di servire i dati, per avere sempre lo stato più recente.
        $processingCampaigns = (clone $query)->where('status', 'processing')->get();
        foreach ($processingCampaigns as $campaign) {
            if (($campaign->processed_count + $campaign->failed_count) >= $campaign->total_recipients) {
                $campaign->update(['status' => 'completed']);
            }
        }

        $totalRecords = (clone $query)->count();

        // Ricerca globale
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('status', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        // Ordinamento
        if ($request->filled('order')) {
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $columnName = $request->input('columns')[$columnIndex]['data'];
            $direction = $order['dir'];

            $allowedColumns = ['name', 'status', 'total_recipients', 'created_at'];
            if (in_array($columnName, $allowedColumns)) {
                $query->orderBy($columnName, $direction);
            } else {
                $query->latest(); // Fallback
            }
        } else {
            $query->latest(); // Default order
        }

        // Paginazione
        if ($request->filled('length') && $request->input('length') != -1) {
            $query->skip($request->input('start'))->take($request->input('length'));
        }

        $campaigns = $query->get();

        return response()->json(['draw' => intval($request->input('draw')), 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $campaigns]);
    }

    /**
     * Mostra la pagina della documentazione.
     */
    public function showDocs()
    {
        return view('docs.index', ['token' => session('jwt_token')]);
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
            'recipient' => 'required|string|min:10', // Aggiunta una validazione base
            'message_template' => 'required|string',
            'variable_count' => 'required|integer|min:0',
            'header_format' => 'nullable|string|in:IMAGE,DOCUMENT,VIDEO',
            // Aggiungiamo la validazione per il file di test, se presente.
            // I tipi di file sono indicativi, Meta ha le sue restrizioni.
            'header_attachment' => 'nullable|file|max:5120', // Max 5MB
        ]);

        // Il template viene passato come "nome|lingua"
        $templateParts = explode('|', $validated['message_template']);
        if (count($templateParts) !== 2) {
            return response()->json(['message' => 'Formato del template non valido.'], 422);
        }
        $templateName = $templateParts[0];
        $languageCode = $templateParts[1];

        $account = $this->getCurrentAccount();

        try {
            if (!$account) {
                throw new \Exception('Nessun account WhatsApp è configurato nel sistema.');
            }

            $token = config('services.meta_whatsapp.system_user_token');
            $phoneNumberId = $account->phone_number_id;
            $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

            if (!$token || !$phoneNumberId) {
                throw new \Exception('Credenziali non valide per l\'account selezionato.');
            }
            
            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

            // Costruisce il payload per un messaggio template di test
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $validated['recipient'],
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $languageCode],
                    'components' => [], // Inizializza l'array dei componenti
                ]
            ];

            $headerComponent = null;

            // Priorità 1: Se è stato caricato un file specifico per il test, usiamo quello.
            if ($request->hasFile('header_attachment')) {
                $file = $request->file('header_attachment');
                $mediaId = $this->uploadMediaToWhatsapp($account, $file);

                if (!$mediaId) {
                    throw new \Exception("Impossibile caricare il file di test su WhatsApp.");
                }

                $mediaType = strtolower($validated['header_format']); // 'document', 'image', etc.
                $parameter = ['type' => $mediaType];

                if ($mediaType === 'document') {
                    $parameter['document'] = ['id' => $mediaId, 'filename' => $file->getClientOriginalName()];
                } elseif ($mediaType === 'image') {
                    $parameter['image'] = ['id' => $mediaId];
                } // Aggiungere 'video' se necessario

                if (isset($parameter[$mediaType])) {
                    $headerComponent = ['type' => 'header', 'parameters' => [$parameter]];
                }

            // Priorità 2: Se non c'è file ma il template richiede un header, usiamo un link di esempio.
            } elseif (!empty($validated['header_format'])) {
                $mediaType = strtolower($validated['header_format']);
                $parameter = ['type' => $mediaType];

                if ($mediaType === 'document') {
                    $parameter['document'] = [
                        'link' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                        'filename' => 'documento_di_test.pdf'
                    ];
                } elseif ($mediaType === 'image') {
                    $parameter['image'] = ['link' => 'https://www.filleacgil.it/images/loghi/fillea-logo-colori.png'];
                }

                if (isset($parameter[$mediaType])) {
                    $headerComponent = ['type' => 'header', 'parameters' => [$parameter]];
                }
            }

            // Aggiunge il componente header al payload, se è stato creato.
            if ($headerComponent) {
                $payload['template']['components'][] = $headerComponent;
            }

            // Aggiunge le variabili del body, se presenti
            $bodyParameters = [];
            for ($i = 1; $i <= $validated['variable_count']; $i++) {
                $bodyParameters[] = ['type' => 'text', 'text' => "Test $i"];
            }
            if (!empty($bodyParameters)) {
                $payload['template']['components'][] = ['type' => 'body', 'parameters' => $bodyParameters];
            }

            // Se non ci sono componenti, rimuove la chiave per evitare di inviare un array vuoto.
            if (empty($payload['template']['components'])) {
                unset($payload['template']['components']);
            }

            // SIMULAZIONE: Se l'account è 'SIMULATE', non inviamo realmente ma logghiamo il payload.
            // La funzione di upload media gestisce già la sua parte di simulazione restituendo un ID fittizio.
            if ($account->name === 'SIMULATE') {
                Log::info('SIMULATED test send to: ' . $validated['recipient'], ['payload' => $payload]);
                return response()->json([
                    'message' => 'Messaggio di prova (simulato) inviato con successo. Controlla i log per il payload.',
                    'message_id' => 'simulated_test_' . uniqid()
                ]);
            }

            // Invio reale
            $response = Http::withToken($token)->post($url, $payload);
            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                throw new \Exception("Errore API: {$errorMessage}");
            }

            $messageId = $response->json('messages')[0]['id'] ?? 'N/A';
            Log::info('Messaggio di test inviato a: ' . $validated['recipient'] . '. Message ID: ' . $messageId);

            return response()->json(['message' => 'Messaggio di prova inviato con successo.', 'message_id' => $messageId]);

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $errorMessage = "Impossibile leggere le credenziali dell'account. Il token salvato potrebbe essere corrotto o la chiave di cifratura è cambiata.";
            Log::critical("Errore di decrittazione del token durante l'invio di un test: " . $e->getMessage(), ['account_id' => $account->id ?? null]);
            return response()->json(['message' => 'Impossibile inviare il messaggio di prova. Dettaglio: ' . $errorMessage], 500);
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
            // identificano i file CSV come 'text/plain'.
            'recipient_file' => 'required|file|max:10240|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], ['recipient_file.mimetypes' => 'Sono ammessi solo file di tipo CSV o Excel (XLS, XLSX).']);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $file = $request->file('recipient_file');
        $filename = uniqid('file_', true) . '.' . $file->getClientOriginalExtension();
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->storeAs('recipient_files', $filename, 'local');
        $fullPath = storage_path('app/' . $filePath);

        $headers = [];
        if ($extension === 'csv') {
            if (($handle = fopen($fullPath, "r")) !== FALSE) {
                $headerData = fgetcsv($handle, 0, ";");
                fclose($handle);
                if ($headerData) {
                    $headers = array_filter($headerData, fn($h) => !is_null($h) && $h !== '');
                }
            }
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            try {
                $headings = (new HeadingRowImport)->toArray($fullPath);
                if (isset($headings[0][0])) {
                    $headers = array_filter($headings[0][0]);
                }
            } catch (\Exception $e) {
                Storage::disk('local')->delete($filePath);
                return response()->json(['success' => false, 'message' => 'Impossibile leggere il file Excel. Assicurati che non sia protetto da password o corrotto.'], 400);
            }
        }

        if (empty($headers)) {
            Storage::disk('local')->delete($filePath);
            return response()->json(['success' => false, 'message' => 'Impossibile leggere le intestazioni dal file. Assicurati che usi il punto e virgola (;) come separatore per i CSV e che la prima riga contenga le intestazioni.'], 400);
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
            'map_phone' => 'required|string',
            'map_vars' => 'present|array',
            'map_vars.*' => 'nullable|string',
        ]);

        $filePath = storage_path('app/' . $validated['file_path']);
        if (!is_readable($filePath)) {
            return response()->json(['success' => false, 'message' => 'File non trovato o non leggibile sul server.'], 404);
        }

        $totalRows = 0;
        $normalizedCount = 0;
        $validRecipients = [];
        $invalidEntries = [];
        
        try {
            $dataCollection = $this->getFileDataAsCollection($filePath);
        } catch (\Exception $e) {
            Log::error('Errore lettura file in ajaxValidate: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Errore durante la lettura del file. Assicurati che sia in un formato valido e non corrotto.'], 500);
        }

        $mapPhone = $validated['map_phone'];
        $mapVars = $validated['map_vars'];

        $lineNumber = 1; // La collezione parte dalla prima riga di dati
        foreach ($dataCollection as $row) {
            $lineNumber++;
            $totalRows++;
            $phoneNumberRaw = isset($row[$mapPhone]) ? trim($row[$mapPhone]) : '';
            
            $params = [];
            foreach ($mapVars as $varName) {
                $params[] = ($varName && isset($row[$varName])) ? trim($row[$varName]) : '';
            }

            $validationResult = $this->normalizeAndValidatePhoneNumber($phoneNumberRaw);

            // --- NUOVA LOGICA: Controlla se il numero è nella blocklist ---
            if ($validationResult['status'] !== 'invalid' && \App\Models\BlockedRecipient::where('phone_number', $validationResult['number'])->exists()) {
                $invalidEntries[] = ['line' => $lineNumber, 'name' => $params[0] ?? '', 'phone' => $phoneNumberRaw, 'reason' => 'Utente bloccato (opt-out)'];
                continue; // Salta al prossimo destinatario
            }

            if ($validationResult['status'] === 'invalid') {
                $invalidEntries[] = ['line' => $lineNumber, 'name' => $params[0] ?? '', 'phone' => $phoneNumberRaw, 'reason' => $validationResult['reason']];
            } else {
                if ($validationResult['status'] === 'normalized') $normalizedCount++;
                $validRecipients[] = ['phone_number' => $validationResult['number'], 'params' => $params];
            }
        }

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
        $account = $this->getCurrentAccount();

        if (!$account) {
            return back()->with('error', 'Nessun account WhatsApp configurato. Impossibile avviare la campagna.')->withInput();
        }

        $validated = $request->validate([
            // 'whatsapp_account_id' => 'required|exists:whatsapp_accounts,id', // Rimosso
            'campaign_name' => 'required|string|max:255',
            'message_template' => 'required|string',
            'recipient_source' => 'required|in:fillea_tabulato,assemblea_generale,organismi_dirigenti,file_upload',
            'header_attachment' => 'nullable|file|max:5120',
            // Il tipo di header viene ora passato direttamente dal form, rendendo il processo più robusto.
            'header_format' => 'nullable|string|in:DOCUMENT,IMAGE,VIDEO',
        ]);

        // --- GESTIONE ALLEGATO CAMPAGNA ---
        $mediaId = null;
        $mediaType = null;
        $mediaName = null;

        // 1. Usa direttamente il formato dell'header passato dal form, eliminando la necessità di una seconda chiamata API.
        $requiredHeaderFormat = $validated['header_format'] ?? null;

        // 2. Se il template richiede un allegato, il file è obbligatorio (validazione server-side).
        if ($requiredHeaderFormat && !$request->hasFile('header_attachment')) {
            $templateNameOnly = explode('|', $validated['message_template'])[0];
            return back()->with('error', "Il template '{$templateNameOnly}' richiede un allegato di tipo {$requiredHeaderFormat}, ma non è stato fornito alcun file.")->withInput();
        }

        // 3. Se un file è stato caricato (e richiesto), lo carichiamo su Meta.
        if ($requiredHeaderFormat && $request->hasFile('header_attachment')) {
            $file = $request->file('header_attachment');
            $uploadedMediaId = $this->uploadMediaToWhatsapp($account, $file);

            if (!$uploadedMediaId) {
                return back()->with('error', 'Impossibile caricare l\'allegato su WhatsApp. La campagna non è stata avviata.')->withInput();
            }
            // Solo se l'upload ha successo, impostiamo tutti i campi del media.
            $mediaId   = $uploadedMediaId;
            $mediaType = $requiredHeaderFormat;
            $mediaName = $file->getClientOriginalName();
        }
        // --- FINE GESTIONE ALLEGATO ---

        if ($validated['recipient_source'] === 'file_upload') {
            $validatedRecipients = $request->session()->get('validated_recipients');

            if (empty($validatedRecipients)) {
                return back()->with('error', 'Nessun destinatario valido trovato. Esegui nuovamente il processo di caricamento e validazione del file.')->withInput();
            }

            // --- DEBUG: Stampa i dati prima dell'inserimento nel DB ---
            // Decommenta la riga `dd(...)` qui sotto per bloccare l'esecuzione e
            // vedere l'array esatto che sta per essere salvato.
            // Controlla 'header_format' nei dati validati e 'type' nei dati media.
            // Se sono 'null', hai la conferma che il dato non arriva correttamente dal form.
          
            $campaign = Campaign::create([
                'whatsapp_account_id' => $account->id,
                'name' => $validated['campaign_name'],
                'message_template' => $validated['message_template'],
                'status' => 'pending',
                'total_recipients' => count($validatedRecipients),
                // Aggiungiamo i dati del media alla campagna
                'header_media_id' => $mediaId,
                'header_media_type' => $mediaType,
                'header_media_name' => $mediaName,
            ]);

            foreach ($validatedRecipients as $rec) {
                $recipient = CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'phone_number' => $rec['phone_number'],
                    'name' => $rec['params'][0] ?? null,
                    'params' => $rec['params'],
                    'status' => 'queued',
                ]);
                SendWhatsAppMessage::dispatch($recipient);
            }

            $campaign->update(['status' => 'processing']);
            $request->session()->forget('validated_recipients');

            return redirect()->route('campaigns.progress', ['campaign' => $campaign->id, 'token' => $request->session()->get('jwt_token')]);
        } else {
            // Logica per le altre fonti di destinatari (da implementare)
            return back()->with('error', 'La modalità di invio selezionata non è ancora stata implementata.')->withInput();
        }
    }

    /**
     * Interrompe una campagna in corso.
     *
     * @param Campaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stop(Campaign $campaign)
    {
        // Recupera l'account dell'utente corrente per l'autorizzazione
        $account = $this->getCurrentAccount();

        // Verifica che l'utente sia autorizzato a modificare questa campagna
        if (!$account || $campaign->whatsapp_account_id !== $account->id) {
            return back()->with('error', 'Non sei autorizzato a fermare questa campagna.');
        }

        // Si può interrompere solo una campagna in 'processing'
        if ($campaign->status === 'processing') {
            $campaign->update(['status' => 'cancelled']);
            return back()->with('success', 'La campagna è stata impostata per l\'interruzione. I messaggi non ancora elaborati verranno annullati.');
        }

        return back()->with('info', 'Questa campagna non è in esecuzione o è già stata completata/cancellata.');
    }

    /**
     * Determina il tipo di media per l'API di WhatsApp in base al MIME type.
     */
    private function getMediaType(string $mimeType): ?string
    {
        if ($mimeType === 'application/pdf') {
            return 'DOCUMENT';
        }
        if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
            return 'IMAGE';
        }
        // In futuro si potrebbero aggiungere 'VIDEO' e 'AUDIO'
        return null;
    }

    /**
     * Carica un file multimediale sui server di WhatsApp e restituisce il media ID.
     */
    private function uploadMediaToWhatsapp(WhatsappAccount $account, \Illuminate\Http\UploadedFile $file): ?string
    {
        if ($account->name === 'SIMULATE') {
            return 'simulated_media_id_' . uniqid();
        }

        try {
            $token = config('services.meta_whatsapp.system_user_token');
            $phoneNumberId = $account->phone_number_id;
            $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');
            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/media";

            $response = Http::withToken($token)
                ->attach('file', $file->get(), $file->getClientOriginalName())
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                ]);

            $response->throw(); // Lancia un'eccezione se la risposta è un errore (4xx o 5xx)

            $mediaId = $response->json('id');
            if ($mediaId) {
                Log::info("File media '{$file->getClientOriginalName()}' caricato con successo per l'account #{$account->id}. Media ID: {$mediaId}");
                return $mediaId;
            }

            Log::error("Impossibile ottenere il media ID dalla risposta dell'API di WhatsApp.", ['response' => $response->json()]);
            return null;

        } catch (Throwable $e) {
            Log::error("Eccezione durante il caricamento del media su WhatsApp per l'account #{$account->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Recupera i dettagli di un singolo template da Meta.
     */
    private function fetchTemplateDetails(WhatsappAccount $account, string $templateName): ?array
    {
        try {
            // Non fare chiamate API per l'account di simulazione, che non ha template reali.
            if ($account->name === 'SIMULATE') {
                return null;
            }

            $token = config('services.meta_whatsapp.system_user_token');
            $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');
            $url = "https://graph.facebook.com/{$apiVersion}/{$account->waba_id}/message_templates";
            $response = Http::withToken($token)->get($url, ['name' => $templateName, 'fields' => 'components']);
            $response->throw();
            return $response->json('data')[0] ?? null;
        } catch (Throwable $e) {
            Log::error("Impossibile recuperare i dettagli del template '{$templateName}' per l'account #{$account->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Legge i dati da un file (CSV o Excel) e li restituisce come array di array associativi.
     */
    private function getFileDataAsCollection(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $dataCollection = [];

        if ($extension === 'csv') {
            $handle = fopen($filePath, "r");
            $csv_headers = fgetcsv($handle, 0, ";");
            if ($csv_headers) {
                while($row = fgetcsv($handle, 0, ";")) {
                    // Ignora righe vuote
                    if (empty(array_filter($row))) continue;
                    // Assicura che il numero di colonne corrisponda
                    if(count($csv_headers) == count($row)) {
                        $dataCollection[] = array_combine($csv_headers, $row);
                    }
                }
            }
            fclose($handle);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            $sheets = Excel::toArray(null, $filePath);
            if (!empty($sheets) && !empty($sheets[0])) {
                $sheetData = $sheets[0];
                $headers = array_shift($sheetData); // Get and remove header row
                foreach($sheetData as $row) {
                    if (empty(array_filter($row))) continue; // Ignora righe vuote
                    if (count($headers) == count($row)) $dataCollection[] = array_combine($headers, $row);
                }
            }
        }
        return $dataCollection;
    }

    /**
     * Gestisce la richiesta di verifica del webhook da parte di Meta.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function verifyWebhook(Request $request)
    {
        $verifyToken = config('services.meta_whatsapp.webhook_verify_token');

        if (
            $request->has('hub_mode') &&
            $request->has('hub_challenge') &&
            $request->has('hub_verify_token') &&
            $request->input('hub_mode') === 'subscribe' &&
            $request->input('hub_verify_token') === $verifyToken
        ) {
            Log::info('Webhook verification successful.');
            return response($request->input('hub_challenge'), 200);
        }

        Log::warning('Webhook verification failed.', $request->all());
        return response('Forbidden', 403);
    }

    /**
     * Gestisce le notifiche di stato inviate dal webhook di Meta.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        // 1. Validazione della firma per sicurezza
        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = config('services.meta_whatsapp.app_secret');

        if (!$signature || !$appSecret) {
            Log::warning('Webhook received without signature or app_secret not configured.');
            return response('Forbidden', 403);
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::error('Webhook signature validation failed.');
            return response('Forbidden', 403);
        }

        // 2. Processa il payload
        $payload = $request->all();
        Log::info('Meta Webhook received and validated:', $payload);

        $changes = data_get($payload, 'entry.0.changes.0.value');

        if (!$changes) {
            Log::info('Webhook received, but no changes found.');
            return response('EVENT_RECEIVED', 200);
        }

        // --- Gestione messaggi in arrivo (es. "STOP") ---
        $messages = data_get($changes, 'messages');
        if ($messages) {
            foreach ($messages as $message) {
                // Controlla solo i messaggi di tipo 'text'
                if (data_get($message, 'type') === 'text') {
                    $text = strtolower(trim(data_get($message, 'text.body', '')));
                    if ($text === 'stop') {
                        $phoneNumber = data_get($message, 'from');
                        $this->blockRecipient($phoneNumber, 'stop_reply');
                    }
                }
            }
        }

        // --- Gestione stati e segnalazioni spam ---
        $statuses = data_get($changes, 'statuses');
        if ($statuses) {
            $this->handleStatusUpdates($statuses);
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Gestisce gli aggiornamenti di stato dei messaggi e le segnalazioni di spam.
     */
    private function handleStatusUpdates(array $statuses): void
    {
        foreach ($statuses as $statusData) {
            $messageId = data_get($statusData, 'id');
            $newStatus = data_get($statusData, 'status'); // es: 'sent', 'delivered', 'read'

            if (!$messageId) {
                continue;
            }

            // --- NUOVA LOGICA: Controlla se è una segnalazione di spam ---
            // Questo evento arriva come uno stato 'failed' con un codice di errore specifico.
            $errors = data_get($statusData, 'errors');
            if ($errors && data_get($errors, '0.code') === 131051) { // Codice errore per "Message reported as spam"
                $phoneNumber = data_get($statusData, 'recipient_id');
                $this->blockRecipient($phoneNumber, 'spam_report');
                continue; // Passa al prossimo stato, non c'è altro da fare
            }

            if (!$newStatus) {
                continue;
            }

            $recipient = CampaignRecipient::where('message_id', $messageId)->first();

            if ($recipient) {
                // Definiamo un ordine per gli stati per evitare di sovrascrivere uno stato
                // più avanzato con uno precedente (es. 'read' con 'delivered').
                $statusOrder = [
                    'queued' => 0, 'processing' => 1, 'sent' => 2, 'delivered' => 3, 'read' => 4, 'opted-out' => 6,
                    'failed' => 5, 'cancelled' => 5
                ];

                $currentStatusValue = $statusOrder[$recipient->status] ?? -1;
                $newStatusValue = $statusOrder[$newStatus] ?? -1;

                if ($newStatusValue > $currentStatusValue) {
                    $recipient->status = $newStatus;
                    $recipient->save(); // 'updated_at' verrà aggiornato automaticamente
                    Log::info("Recipient #{$recipient->id} status updated to '{$newStatus}' for message_id {$messageId}.");
                } else {
                    Log::info("Skipping status update for Recipient #{$recipient->id} from '{$recipient->status}' to '{$newStatus}'.");
                }
            } else {
                Log::warning("Received webhook for unknown message_id: {$messageId}");
            }
        }
    }

    /**
     * Blocca un destinatario e aggiorna il suo stato in tutte le campagne.
     */
    private function blockRecipient(string $phoneNumber, string $reason): void
    {
        if (empty($phoneNumber)) return;

        // --- Normalizza il numero di telefono in formato E.164 (+39...) ---
        // I webhook di Meta per i messaggi in arrivo forniscono il numero senza il '+'.
        // La nostra applicazione salva e confronta i numeri sempre in formato E.164 completo.
        $normalizedPhoneNumber = $phoneNumber;
        if (!str_starts_with($normalizedPhoneNumber, '+')) {
            $normalizedPhoneNumber = '+' . $normalizedPhoneNumber;
        }

        // Aggiunge o aggiorna il numero nella blocklist usando il formato normalizzato
        $blocked = \App\Models\BlockedRecipient::updateOrCreate(['phone_number' => $normalizedPhoneNumber], ['reason' => $reason]);
        Log::info("Recipient {$normalizedPhoneNumber} added/updated in blocklist. Reason: {$reason}. Blocked ID: {$blocked->id}");

        // Aggiorna lo stato di tutti i record esistenti per questo numero a 'opted-out'
        $updatedCount = CampaignRecipient::where('phone_number', $normalizedPhoneNumber)->update(['status' => 'opted-out']);
        Log::info("Updated {$updatedCount} existing CampaignRecipient records to 'opted-out' for phone number {$normalizedPhoneNumber}.");

        Log::info("Recipient {$normalizedPhoneNumber} has been fully processed for blocking. Reason: {$reason}.");
    }
}