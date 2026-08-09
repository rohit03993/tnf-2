<?php

namespace App\Models;

use App\Enums\WhatsAppAudienceType;
use App\Enums\WhatsAppCampaignStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappCampaign extends Model
{
    protected $table = 'whatsapp_campaigns';

    protected $fillable = [
        'whatsapp_template_id',
        'name',
        'status',
        'audience_type',
        'total_recipients',
        'sent_count',
        'failed_count',
        'campaign_variables',
        'started_at',
        'finished_at',
        'created_by',
        'shot_by',
        'shot_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppCampaignStatus::class,
            'audience_type' => WhatsAppAudienceType::class,
            'campaign_variables' => 'array',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'shot_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsappCampaignRecipient::class, 'whatsapp_campaign_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shotBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shot_by');
    }

    public function campaignVariable(string $key, mixed $default = ''): mixed
    {
        return data_get($this->campaign_variables, $key, $default);
    }
}
