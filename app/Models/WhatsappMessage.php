<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'wamid',
        'direction',
        'phone',
        'user_id',
        'type',
        'body',
        'payload',
        'status',
        'template_name',
        'error_message',
        'read_at',
        'provider_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
            'provider_timestamp' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public static function unreadInboundCount(): int
    {
        return static::query()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->count();
    }
}
