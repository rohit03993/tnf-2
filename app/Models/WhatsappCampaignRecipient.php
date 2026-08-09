<?php

namespace App\Models;

use App\Enums\WhatsAppRecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappCampaignRecipient extends Model
{
    protected $table = 'whatsapp_campaign_recipients';

    protected $fillable = [
        'whatsapp_campaign_id',
        'user_id',
        'phone',
        'wamid',
        'status',
        'template_params',
        'message_sent',
        'provider_response',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppRecipientStatus::class,
            'template_params' => 'array',
            'provider_response' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsappCampaign::class, 'whatsapp_campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
