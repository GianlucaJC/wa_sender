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
            // Tipo di media: DOCUMENT, IMAGE, VIDEO
            $table->string('header_media_type')->nullable()->after('message_template');
            // ID del media ottenuto da WhatsApp
            $table->string('header_media_id')->nullable()->after('header_media_type');
            // Nome originale del file, utile per i documenti
            $table->string('header_media_name')->nullable()->after('header_media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['header_media_type', 'header_media_id', 'header_media_name']);
        });
    }
};
