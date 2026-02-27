<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'phone_number',
        'name',
        'params',
        'status',
        'processed_at',
        'error_message',
        'message_id',
    ];

    protected $casts = [
        'params' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * La campagna a cui questo destinatario appartiene.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}