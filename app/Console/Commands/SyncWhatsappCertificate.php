<?php

namespace App\Console\Commands;

use App\Models\WhatsappAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncWhatsappCertificate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:sync-certificate {accountId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches available display name certificates from Meta and applies one to a phone number.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $accountId = $this->argument('accountId');
        $account = WhatsappAccount::find($accountId);

        if (!$account) {
            $this->error("Account WhatsApp con ID {$accountId} non trovato.");
            return Command::FAILURE;
        }

        $this->info("Trovato account: '{$account->name}' ({$account->business_name})");

        // Recupera le credenziali dalla configurazione
        $token = config('services.meta_whatsapp.system_user_token');
        $apiVersion = config('services.meta_whatsapp.api_version', 'v20.0');
        $wabaId = $account->waba_id;
        $phoneNumberId = $account->phone_number_id;

        if (!$token || !$wabaId || !$phoneNumberId) {
            $this->error('Credenziali mancanti (System User Token, WABA ID, o Phone Number ID) per questo account.');
            return Command::FAILURE;
        }

        // --- NUOVO: Verifica le autorizzazioni del System User Token ---
        $appId = config('services.meta_whatsapp.client_id');
        $appSecret = config('services.meta_whatsapp.app_secret');

        if (!$appId || !$appSecret) {
            $this->error('META_WHATSAPP_CLIENT_ID o META_APP_SECRET non sono impostati nel file .env. Sono necessari per verificare il token.');
            return Command::FAILURE;
        }

        $this->info('Verificando le autorizzazioni del System User Token...');
        $appToken = "{$appId}|{$appSecret}";
        $debugResponse = Http::get('https://graph.facebook.com/debug_token', [
            'input_token' => $token,
            'access_token' => $appToken,
        ]);

        if ($debugResponse->failed()) {
            $this->error('Errore durante la verifica del System User Token: ' . $debugResponse->body());
            return Command::FAILURE;
        }

        $debugData = $debugResponse->json('data');
        if (isset($debugData['error'])) {
            $this->error("Il System User Token non è valido o è scaduto. Messaggio: " . $debugData['error']['message']);
            return Command::FAILURE;
        }

        $scopes = $debugData['scopes'] ?? [];
        if (!in_array('whatsapp_business_management', $scopes)) {
            $this->error("Il System User Token MANCA del permesso 'whatsapp_business_management'. Questo è necessario per gestire i numeri di telefono.");
            $this->line("Assicurati che l'utente di sistema sia associato come 'Admin' all'asset 'Account WhatsApp' specifico nel Business Manager e rigenera il token.");
            return Command::FAILURE;
        }
        $this->info('System User Token verificato e ha i permessi necessari.');
        // --- FINE NUOVO: Verifica autorizzazioni ---

        // --- Passaggio 1: Chiedi all'utente quale nome visualizzato applicare ---
        $this->info("Per applicare un nome visualizzato, è necessario che sia stato precedentemente approvato da Meta per il WABA ID: {$wabaId}.");
        $this->info("Il nome visualizzato verrà applicato al numero: {$account->phone_number_display}.");

        try {
            $chosenName = $this->ask('Inserisci il nome visualizzato approvato che vuoi applicare (es. "Nome Azienda S.r.l.")');

            if (empty($chosenName)) {
                $this->error('Il nome visualizzato non può essere vuoto.');
                return Command::FAILURE;
            }

            // Chiediamo il nome visualizzato per riferimento, ma il "certificato" da inviare all'API
            // è la stringa lunga fornita da Meta.
            $certificateString = $this->ask('Inserisci la stringa del certificato che hai ricevuto da Meta (quella lunga)');

            if (empty($certificateString)) {
                $this->error('La stringa del certificato non può essere vuota.');
                return Command::FAILURE;
            }

            // --- Passaggio 3: Applica il certificato scelto ---
            $this->line("Sto applicando il certificato per '{$chosenName}' al numero ID: {$phoneNumberId}...");

            $postResponse = Http::withToken($token)->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}", [
                // Meta si aspetta la stringa del certificato, non il nome visualizzato.
                // Il nome visualizzato è già stato approvato e associato a questa stringa.
                'certificate' => $certificateString,
            ]);

            if ($postResponse->failed()) {
                $error = $postResponse->json('error');
                $errorMessage = $error['message'] ?? 'Errore sconosciuto';
                $errorCode = $error['code'] ?? 'N/A';
                $this->error("Errore API durante l'applicazione del certificato: ({$errorCode}) {$errorMessage}");
                Log::error("Failed to apply certificate for account {$accountId}. Response: " . $postResponse->body());
                return Command::FAILURE;
            }

            if ($postResponse->json('success') === true) {
                $this->info("\nOperazione completata con successo!");
                $this->info("Il nome visualizzato '{$chosenName}' è ora attivo per il numero {$account->phone_number_display}.");
                $this->warn("Potrebbero essere necessari alcuni minuti prima che la modifica sia visibile a tutti.");
            } else {
                $this->error('L\'API di Meta non ha confermato il successo dell\'operazione. Controlla la risposta API per maggiori dettagli.');
                Log::error("Meta API did not confirm success for certificate application for account {$accountId}. Response: " . $postResponse->body());
            }

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Si è verificato un errore imprevisto: ' . $e->getMessage());
            Log::error('Errore nel comando whatsapp:sync-certificate: ' . $e);
            return Command::FAILURE;
        }
    }
}