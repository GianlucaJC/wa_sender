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

        // --- Passaggio 1: Scarica i certificati disponibili ---
        $this->line("Sto scaricando i certificati per il WABA ID: {$wabaId}...");

        try {
            $response = Http::withToken($token)->get("https://graph.facebook.com/{$apiVersion}/{$wabaId}/certificates");

            if ($response->failed()) {
                $error = $response->json('error');
                $this->error("Errore API durante il recupero dei certificati: ({$error['code']}) {$error['message']}");
                $this->warn("Assicurati che il tuo System User Token abbia il permesso 'whatsapp_business_management'.");
                return Command::FAILURE;
            }

            $certificates = $response->json('data');

            if (empty($certificates)) {
                $this->warn('Nessun certificato disponibile per il download. Potrebbe essere necessario attendere l\'approvazione da parte di Meta.');
                return Command::SUCCESS;
            }

            $this->info('Certificati disponibili trovati:');
            $certChoices = collect($certificates)->pluck('display_name')->all();
            $certMap = collect($certificates)->keyBy('display_name')->all();

            // --- Passaggio 2: Chiedi all'utente quale certificato applicare ---
            $chosenName = $this->choice(
                'Quale nome visualizzato vuoi applicare al numero ' . $account->phone_number_display . '?',
                $certChoices
            );

            $chosenCertificate = $certMap[$chosenName]['certificate'];

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