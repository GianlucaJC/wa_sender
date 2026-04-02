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

        // --- Passaggio 1: Chiedi all'utente quale nome visualizzato applicare ---
        $this->info("Per applicare un nome visualizzato, è necessario che sia stato precedentemente approvato da Meta per il WABA ID: {$wabaId}.");
        $this->info("Il nome visualizzato verrà applicato al numero: {$account->phone_number_display}.");

        try {
            $chosenName = $this->ask('Inserisci il nome visualizzato approvato che vuoi applicare (es. "Nome Azienda S.r.l.")');

            if (empty($chosenName)) {
                $this->error('Il nome visualizzato non può essere vuoto.');
                return Command::FAILURE;
            }

            // Il "certificato" da inviare all'API è in realtà il nome visualizzato stesso,
            // una volta che è stato approvato da Meta.
            // Non c'è un endpoint per "scaricare" una lista di certificati in questo contesto,
            // ma si usa direttamente il nome approvato.
            $chosenCertificate = $chosenName;

            // --- Passaggio 3: Applica il certificato scelto ---
            $this->line("Sto applicando il certificato per '{$chosenName}' al numero ID: {$phoneNumberId}...");

            $postResponse = Http::withToken($token)->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}", [
                'certificate' => $chosenCertificate,
            ]);

            if ($postResponse->failed()) {
                $error = $postResponse->json('error');
                $this->error("Errore API durante l'applicazione del certificato: ({$error['code']}) {$error['message']}");
                return Command::FAILURE;
            }

            if ($postResponse->json('success') === true) {
                $this->info("\nOperazione completata con successo!");
                $this->info("Il nome visualizzato '{$chosenName}' è ora attivo per il numero {$account->phone_number_display}.");
                $this->warn("Potrebbero essere necessari alcuni minuti prima che la modifica sia visibile a tutti.");
            } else {
                $this->error('L\'API di Meta non ha confermato il successo dell\'operazione.');
            }

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Si è verificato un errore imprevisto: ' . $e->getMessage());
            Log::error('Errore nel comando whatsapp:sync-certificate: ' . $e);
            return Command::FAILURE;
        }
    }
}