<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsappAccount;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\Schema;

class WhatsappAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Per poter usare truncate() su tabelle con chiavi esterne,
        // dobbiamo disabilitare temporaneamente i controlli.
        Schema::disableForeignKeyConstraints();

        // Pulisce le tabelle correlate per evitare dati orfani e duplicati.
        // Questo assicura che non ci siano vecchie campagne che puntano a un account che stiamo per eliminare.
        CampaignRecipient::truncate();
        Campaign::truncate();
        WhatsappAccount::truncate();

        // Riabilitiamo i controlli
        Schema::enableForeignKeyConstraints();

        // Crea un account di SIMULAZIONE.
        // Grazie al nome 'SIMULATE', l'applicazione non invierà messaggi reali
        // ma simulerà l'invio, permettendo di testare tutto il flusso.
        WhatsappAccount::create([
            // NOME: Impostato a 'SIMULATE' per attivare la modalità di simulazione
            'name' => 'SIMULATE',

            // NOME BUSINESS: Nome visualizzato nell'interfaccia
            'business_name' => 'Fillea CGIL Simulazione',

            // NUMERO DI TELEFONO (per visualizzazione)
            'phone_number_display' => '+39 333 0000000',

            // --- Le chiavi seguenti possono essere dei segnaposto in modalità simulazione ---

            // WHATSAPP BUSINESS ACCOUNT ID (WABA ID): Richiesto per recuperare i template.
            'waba_id' => '123456789012345',

            // PHONE NUMBER ID: L'ID del numero di telefono associato al WABA.
            'phone_number_id' => '123456789098765',

            // ACCESS TOKEN: Il token di accesso all'API di WhatsApp.
            'access_token' => 'SIMULATED_TOKEN',
        ]);
    }
}