<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'whatsapp_account_id',
        'name',
        'message_template',
        'status',
        'total_recipients',
        // Aggiungi questi tre campi
        'header_media_id',
        'header_media_type',
        'header_media_name',
    ];

    /**
     * Get the whatsapp account that owns the campaign.
     */
    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsappAccount::class);
    }
}