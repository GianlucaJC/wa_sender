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
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('phone_number');
            $table->string('name')->nullable();
            // MODIFICA: Usiamo TEXT invece di JSON per compatibilità con versioni MySQL più vecchie
            $table->text('params')->nullable();
            $table->string('status')->default('queued'); // es. queued, processing, sent, failed
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('message_id')->nullable(); // ID del messaggio da Meta
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
