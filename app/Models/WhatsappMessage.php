<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'wamid',
        'direction',
        'phone',
        'user_id',
        'type',
        'media_id',
        'media_path',
        'media_mime_type',
        'media_filename',
        'caption',
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

    public function isMedia(): bool
    {
        return in_array($this->type, ['image', 'video', 'audio', 'document', 'sticker'], true);
    }

    public function isImage(): bool
    {
        return $this->type === 'image'
            || str_starts_with((string) $this->media_mime_type, 'image/');
    }

    public function hasStoredMedia(): bool
    {
        return filled($this->media_path) && Storage::disk('local')->exists((string) $this->media_path);
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
