<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Aggiunge la chiave esterna per l'account WhatsApp.
            // 'after' la posiziona dopo la colonna 'id' per ordine logico.
            // 'nullable' e 'onDelete('set null')' assicurano che se un account WhatsApp viene eliminato,
            // le campagne associate non vengono eliminate, ma il loro collegamento viene rimosso.
            // Questo preserva lo storico delle campagne.
            $table->foreignId('whatsapp_account_id')
                  ->after('id')
                  ->nullable()
                  ->constrained('whatsapp_accounts')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Rimuove prima il vincolo di chiave esterna e poi la colonna.
            $table->dropForeign(['whatsapp_account_id']);
            $table->dropColumn('whatsapp_account_id');
        });
    }
};