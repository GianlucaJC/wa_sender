<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique(); // Il numero di telefono bloccato
            $table->string('reason')->nullable(); // Es. 'stop_reply', 'spam_report'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_recipients');
    }
};
