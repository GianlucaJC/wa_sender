<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_account_id',
        'name',
        'message_template',
        'status',
        'total_recipients',
        'processed_count',
        'failed_count',
    ];

    /**
     * L'account WhatsApp usato per inviare questa campagna.
     */
    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class);
    }

    /**
     * I destinatari di questa campagna.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}