<?php

namespace App\Models;

use App\Enums\WhatsAppLiveCampaignStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLiveCampaign extends Model
{
    protected $table = 'whatsapp_live_campaigns';

    protected $fillable = [
        'name',
        'whatsapp_template_id',
        'status',
        'description',
        'default_variables',
        'went_live_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppLiveCampaignStatus::class,
            'default_variables' => 'array',
            'went_live_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLive(): bool
    {
        return $this->status === WhatsAppLiveCampaignStatus::Live;
    }

    public function templateName(): ?string
    {
        return $this->template?->name;
    }

    public function templateLanguage(): string
    {
        return $this->template?->language ?? 'en';
    }

    public function label(): string
    {
        $template = $this->template?->name ?: 'no template';

        return $this->name.' · '.$template.' · '.$this->status->label();
    }
}
