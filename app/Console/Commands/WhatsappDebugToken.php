<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhatsappDebugToken extends Command
{
    protected $signature = 'whatsapp:debug-token';
    protected $description = 'Inspects the current System User Token to verify its permissions and configuration.';

    public function handle(): int
    {
        $systemUserToken = config('services.meta_whatsapp.system_user_token');
        $appId = config('services.meta_whatsapp.client_id');
        $appSecret = config('services.meta_whatsapp.app_secret');

        if (!$systemUserToken) {
            $this->error('META_SYSTEM_USER_TOKEN non è impostato nel file .env.');
            return Command::FAILURE;
        }

        if (!$appId || !$appSecret) {
            $this->error('META_WHATSAPP_CLIENT_ID o META_APP_SECRET non sono impostati nel file .env. Sono necessari per ispezionare il token.');
            return Command::FAILURE;
        }

        $this->info('Ispezionando il token di sistema configurato nel file .env...');

        // Un App Token è necessario per ispezionare un altro token.
        $appToken = "{$appId}|{$appSecret}";

        $response = Http::get('https://graph.facebook.com/debug_token', [
            'input_token' => $systemUserToken,
            'access_token' => $appToken,
        ]);

        if ($response->failed()) {
            $this->error('Errore durante la chiamata all\'endpoint di debug del token:');
            $this->line($response->body());
            return Command::FAILURE;
        }

        $data = $response->json('data');

        if (isset($data['error'])) {
            $this->error("Il token non è valido o è scaduto. Messaggio: " . $data['error']['message']);
            return Command::FAILURE;
        }

        $this->line('');
        $this->info('--- Risultati dell\'analisi del Token ---');
        $this->table(
            ['Proprietà', 'Valore'],
            [
                ['App ID', $data['app_id'] ?? 'N/A'],
                ['Nome Applicazione', $data['application'] ?? 'N/A'],
                ['Valido', ($data['is_valid'] ?? false) ? '<fg=green>Sì</>' : '<fg=red>No</>'],
                ['Scadenza', isset($data['expires_at']) && $data['expires_at'] > 0 ? date('Y-m-d H:i:s', $data['expires_at']) : '<fg=green>Mai</>'],
                ['Tipo', $data['type'] ?? 'N/A'],
                ['User ID (del System User)', $data['user_id'] ?? 'N/A'],
            ]
        );

        $this->line('');
        $this->info('--- Permessi (Scopes) associati al Token ---');
        $scopes = $data['scopes'] ?? [];

        if (empty($scopes)) {
            $this->warn('Nessun permesso trovato per questo token.');
        } else {
            foreach ($scopes as $scope) {
                if ($scope === 'whatsapp_business_management') {
                    $this->line("<fg=green;options=bold>✓ {$scope}</> <fg=green>(Permesso CORRETTO trovato!)</>");
                } else {
                    $this->line("- {$scope}");
                }
            }
        }

        $this->line('');
        if (in_array('whatsapp_business_management', $scopes)) {
            $this->info("✅ Il token sembra avere i permessi corretti. Questo è un caso raro.");
            $this->warn("Possibile causa alternativa: L'utente di sistema, pur avendo il permesso, non è stato associato come 'Admin' all'asset 'Account WhatsApp' specifico nel Business Manager. Controlla che l'asset WABA ID '1997879657490286' sia assegnato a questo System User con 'Controllo Completo'.");
        } else {
            $this->error("❌ ERRORE CRITICO: Il permesso 'whatsapp_business_management' è MANCANTE.");
            $this->line("Questa è la causa del tuo problema. Devi:");
            $this->line("1. Andare nel Business Manager > Utenti di sistema.");
            $this->line("2. Selezionare l'utente, cliccare 'Assegna asset' e assegnare l'Account WhatsApp con 'Controllo Completo'.");
            $this->line("3. Generare un NUOVO token (quello vecchio non si aggiorna).");
            $this->line("4. Aggiornare il file .env e rieseguire 'php artisan config:clear'.");
        }

        return Command::SUCCESS;
    }
}


