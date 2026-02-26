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
        // Aggiorna lo stato del destinatario a 'processing'
        $this->recipient->update(['status' => 'processing']);
        $campaign = $this->recipient->campaign;

        $token = config('services.meta_whatsapp.token');
        $phoneNumberId = config('services.meta_whatsapp.phone_number_id');
        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

        if (!$token || !$phoneNumberId) {
            Log::critical('WhatsApp credentials not configured. Please check config/services.php and your .env file.');
            $this->handleFailure('Credenziali WhatsApp non configurate.');
            return;
        }

        // SIMULAZIONE: Se il token è 'SIMULATE', non inviamo realmente.
        if ($token === 'SIMULATE') {
            $this->simulateSend();
            return;
        }

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        // Costruzione del payload per un messaggio TEMPLATE
        // Grazie al cast 'array' nel modello CampaignRecipient, non è più necessario decodificare manualmente.
        // Laravel lo fa in automatico quando si accede all'attributo.
        $params = $this->recipient->params;
        $templatePayload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->recipient->phone_number,
            'type' => 'template',
            'template' => [
                'name' => $campaign->message_template,
                'language' => [
                    'code' => 'it' // Assumiamo 'it', da rendere configurabile in futuro
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                // Usiamo il nome del destinatario come primo parametro
                                'text' => $params['name'] ?? ''
                            ]
                            // Qui andrebbero aggiunti altri parametri se il template li richiede
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Http::withToken($token)->post($url, $templatePayload);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                Log::error("Failed to send WhatsApp message to {$this->recipient->phone_number}. Status: {$response->status()}. Error: {$errorMessage}", $errorData);
                $this->handleFailure($errorMessage, new \Exception("WhatsApp API Error: {$errorMessage}"));
                return;
            }

            $messageId = $response->json('messages')[0]['id'] ?? 'N/A';
            Log::info("WhatsApp message successfully queued for delivery to {$this->recipient->phone_number}. Message ID: {$messageId}");

            $this->recipient->update([
                'status' => 'sent',
                'processed_at' => now(),
                'message_id' => $messageId,
            ]);
            $campaign->increment('processed_count');

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
