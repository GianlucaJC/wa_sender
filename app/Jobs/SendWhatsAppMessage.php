<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public CampaignRecipient $recipient;

    /**
     * Create a new job instance.
     *
     * @param CampaignRecipient $recipient Il record del destinatario da processare
     */
    public function __construct(CampaignRecipient $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ricarichiamo il modello con le sue relazioni per avere lo stato più aggiornato,
        // specialmente per processi di lunga durata, per intercettare comandi come 'stop'.
        $this->recipient->load('campaign.whatsappAccount');
        $campaign = $this->recipient->campaign;
        $account = $campaign->whatsappAccount;

        // --- NUOVO CONTROLLO: Verifica se il destinatario è stato bloccato ---
        if (\App\Models\BlockedRecipient::where('phone_number', $this->recipient->phone_number)->exists()) {
            Log::info("Recipient {$this->recipient->phone_number} is blocked. Skipping message send for recipient #{$this->recipient->id}.");
            $this->recipient->update(['status' => 'opted-out', 'processed_at' => now()]);
            $campaign->increment('failed_count'); // Consideriamo l'opt-out come un "fallimento" per il conteggio
            return; // Termina il job senza inviare il messaggio
        }


        // Se la campagna è stata annullata, interrompiamo l'esecuzione.
        if ($campaign->status === 'cancelled') {
            $this->recipient->update(['status' => 'cancelled', 'processed_at' => now()]);
            // Incrementiamo il contatore dei falliti per far avanzare la barra di progresso
            // e permettere alla campagna di raggiungere il 100% di elaborazione.
            $campaign->increment('failed_count');
            // Non falliamo il job, lo terminiamo "con successo" in modo che venga rimosso dalla coda.
            return;
        }

        // Aggiorna lo stato del destinatario a 'processing'
        $this->recipient->update(['status' => 'processing']);

        if (!$account) {
            Log::critical("La campagna #{$campaign->id} non ha un account WhatsApp valido associato. Job fallito per il destinatario #{$this->recipient->id}.");
            $this->handleFailure('Account WhatsApp non trovato per questa campagna.');
            return;
        }

        // Costruzione del payload per un messaggio TEMPLATE
        // Grazie al cast 'array' nel modello CampaignRecipient, non è più necessario decodificare manualmente.
        // Laravel lo fa in automatico quando si accede all'attributo.
        $params = $this->recipient->params ?? [];
        $bodyParameters = [];
        foreach ($params as $param) {
            $bodyParameters[] = [
                'type' => 'text',
                'text' => (string) $param, // L'API si aspetta sempre una stringa
            ];
        }

        // Il nome del template nella campagna potrebbe essere un valore composito "nome|lingua"
        $templateParts = explode('|', $campaign->message_template);
        $templateName = $templateParts[0];
        $languageCode = $templateParts[1] ?? 'it'; // Fallback a 'it' per compatibilità

        $templatePayload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->recipient->phone_number,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
            ]
        ];

        // Aggiungiamo i parametri solo se ce ne sono, come richiesto dall'API di Meta
        if (!empty($bodyParameters)) {
            $templatePayload['template']['components'] = [['type' => 'body', 'parameters' => $bodyParameters]];
        }

        try {
            // SIMULAZIONE: Se il nome dell'account è 'SIMULATE', non inviamo realmente.
            if ($account->name === 'SIMULATE') {
                $this->simulateSend();
                return;
            }

            // Spostiamo il recupero delle credenziali e la costruzione dell'URL qui dentro
            // per intercettare eventuali errori di decrittazione.
            // Con il modello Portfolio, il token è quello del System User, centralizzato.
            $token = config('services.meta_whatsapp.system_user_token');

            // L'ID del numero di telefono, invece, è specifico dell'account selezionato.
            $phoneNumberId = $account->phone_number_id;
            $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

            if (empty($token) || empty($phoneNumberId)) {
                throw new \Exception('Credenziali dell\'account (token o ID telefono) mancanti.');
            }

            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

            $response = Http::withToken($token)->post($url, $templatePayload);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                Log::error("Failed to send WhatsApp message to {$this->recipient->phone_number}. Status: {$response->status()}. Error: {$errorMessage}", $errorData);
                $this->handleFailure($errorMessage, new \Exception("WhatsApp API Error: {$errorMessage}"));
                return;
            }

            // Utilizziamo data_get per recuperare in modo sicuro l'ID del messaggio.
            // Questo previene errori se la struttura della risposta non è quella attesa.
            $messageId = data_get($response->json(), 'messages.0.id', 'N/A');
            Log::info("WhatsApp message successfully queued for delivery to {$this->recipient->phone_number}. Message ID: {$messageId}");

            $this->recipient->update([
                'status' => 'sent',
                'processed_at' => now(),
                'message_id' => $messageId,
            ]);
            $campaign->increment('processed_count');

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::critical("DECRYPTION FAILED in Job for account #{$account->id}. The queue worker might have a stale config/APP_KEY.", ['exception' => $e]);
            $this->handleFailure('Errore di decrittazione del token. Riavviare il servizio di coda.', $e);
        } catch (Throwable $e) {
            Log::error("Exception caught while sending WhatsApp message to {$this->recipient->phone_number}: " . $e->getMessage());
            $this->handleFailure($e->getMessage(), $e);
        }
    }

    /**
     * Gestisce la logica di simulazione dell'invio.
     */
    private function simulateSend(): void
    {
        sleep(rand(1, 2)); // Simula il tempo di risposta dell'API
        $this->recipient->update(['status' => 'sent', 'processed_at' => now(), 'message_id' => 'simulated_' . uniqid()]);
        $this->recipient->campaign->increment('processed_count');
        Log::info("SIMULATED send to {$this->recipient->phone_number}");
    }

    /**
     * Centralizza la gestione dei fallimenti del job.
     */
    private function handleFailure(string $errorMessage, Throwable $exception = null): void
    {
        $this->recipient->update(['status' => 'failed', 'error_message' => $errorMessage, 'processed_at' => now()]);
        $this->recipient->campaign->increment('failed_count');
        $this->fail($exception);
    }
}
