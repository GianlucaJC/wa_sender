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
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            // In un'app multi-utente, qui andrebbe un foreignId('user_id') per collegare l'account a un utente specifico
            $table->string('name'); // Nome dato dall'utente per riconoscere l'account
            $table->string('business_name'); // Nome dell'azienda da Meta
            $table->string('waba_id')->unique(); // WhatsApp Business Account ID
            $table->string('phone_number_id')->unique(); // ID del numero di telefono
            $table->string('phone_number_display'); // Numero di telefono formattato
            $table->text('access_token'); // Token di accesso (verrà cifrato)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};