<?php

namespace App\Jobs;

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

    protected $recipient;
    protected $message;

    /**
     * Create a new job instance.
     *
     * @param string $recipient Il numero di telefono del destinatario
     * @param string $message Il testo del messaggio da inviare
     */
    public function __construct(string $recipient, string $message)
    {
        $this->recipient = $recipient;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = config('services.meta_whatsapp.token');
        $phoneNumberId = config('services.meta_whatsapp.phone_number_id');
        $apiVersion = config('services.meta_whatsapp.api_version', 'v18.0');

        if (!$token || !$phoneNumberId) {
            Log::critical('WhatsApp credentials not configured. Please check config/services.php and your .env file.');
            // Fa fallire il job permanentemente se la configurazione è mancante
            $this->fail('WhatsApp credentials not configured.');
            return;
        }

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken($token)->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $this->recipient,
                'type' => 'text',
                'text' => [
                    'preview_url' => false, // Disabilita l'anteprima degli URL per messaggi più puliti
                    'body' => $this->message,
                ],
            ]);

            if ($response->failed()) {
                // La richiesta è fallita, logghiamo l'errore e facciamo fallire il job
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                Log::error("Failed to send WhatsApp message to {$this->recipient}. Status: {$response->status()}. Error: {$errorMessage}", $errorData);

                // Fa fallire il job per permettere a Laravel di ritentare in base alla configurazione della coda
                $this->fail(new \Exception("WhatsApp API Error: {$errorMessage}"));
                return;
            }

            // La richiesta ha avuto successo
            $messageId = $response->json('messages')[0]['id'] ?? 'N/A';
            Log::info("WhatsApp message successfully queued for delivery to {$this->recipient}. Message ID: {$messageId}");

        } catch (Throwable $e) {
            Log::error("Exception caught while sending WhatsApp message to {$this->recipient}: " . $e->getMessage());
            // Rilancia l'eccezione per far fallire il job e permettere i tentativi
            $this->fail($e);
        }
    }
}
