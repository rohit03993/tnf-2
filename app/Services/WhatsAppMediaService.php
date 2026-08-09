<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppMediaService
{
    public const DISK = 'local';

    public const DIRECTORY = 'whatsapp-media';

    public function __construct(
        protected WhatsAppCloudService $whatsApp,
    ) {}

    public function mediaUrl(WhatsappMessage $message): ?string
    {
        if (! $message->hasStoredMedia()) {
            return null;
        }

        return route('admin.whatsapp.media', ['message' => $message->id]);
    }

    public function ensureStored(WhatsappMessage $message): WhatsappMessage
    {
        if ($message->hasStoredMedia()) {
            return $message;
        }

        if (blank($message->media_id)) {
            $payload = is_array($message->payload) ? $message->payload : [];
            $type = (string) ($payload['type'] ?? $message->type);
            $message->media_id = data_get($payload, $type.'.id');
        }

        if (blank($message->media_id) || ! $this->whatsApp->isConfigured()) {
            return $message;
        }

        return $this->downloadInboundMedia($message) ?? $message;
    }

    public function downloadInboundMedia(WhatsappMessage $message, int $timeoutSeconds = 30): ?WhatsappMessage
    {
        $mediaId = (string) ($message->media_id ?? '');
        if ($mediaId === '' || ! $this->whatsApp->isConfigured()) {
            return null;
        }

        if ($message->hasStoredMedia()) {
            return $message;
        }

        $metaResponse = Http::timeout($timeoutSeconds)
            ->withToken((string) $this->whatsApp->accessToken())
            ->acceptJson()
            ->get($this->whatsApp->publicGraphUrl($mediaId));

        if (! $metaResponse->successful()) {
            Log::warning('WhatsApp media meta failed', [
                'message_id' => $message->id,
                'status' => $metaResponse->status(),
                'body' => $metaResponse->body(),
            ]);

            return null;
        }

        $downloadUrl = (string) data_get($metaResponse->json(), 'url', '');
        $mimeType = (string) data_get($metaResponse->json(), 'mime_type', (string) ($message->media_mime_type ?? ''));

        if ($downloadUrl === '') {
            return null;
        }

        $binaryResponse = Http::timeout(max($timeoutSeconds, 20))
            ->withToken((string) $this->whatsApp->accessToken())
            ->get($downloadUrl);

        if (! $binaryResponse->successful()) {
            Log::warning('WhatsApp media download failed', [
                'message_id' => $message->id,
                'status' => $binaryResponse->status(),
            ]);

            return null;
        }

        $extension = $this->extensionForMime($mimeType, (string) $message->type);
        $path = self::DIRECTORY.'/in-'.$message->id.'-'.Str::lower(Str::random(8)).'.'.$extension;
        Storage::disk(self::DISK)->put($path, $binaryResponse->body());

        $message->update([
            'media_id' => $mediaId,
            'media_path' => $path,
            'media_mime_type' => $mimeType !== '' ? $mimeType : $message->media_mime_type,
            'media_filename' => $message->media_filename ?: basename($path),
        ]);

        return $message->fresh();
    }

    protected function extensionForMime(string $mime, string $type): string
    {
        return match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'mp4') => 'mp4',
            str_contains($mime, 'pdf') => 'pdf',
            $type === 'image' => 'jpg',
            $type === 'video' => 'mp4',
            $type === 'audio' => 'ogg',
            default => 'bin',
        };
    }
}
