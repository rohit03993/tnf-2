<?php

namespace App\Support;

class WhatsAppInboundParser
{
    /**
     * @param  array<string, mixed>  $message
     * @return array{
     *   type: string,
     *   body: ?string,
     *   media_id: ?string,
     *   media_mime_type: ?string,
     *   media_filename: ?string,
     *   caption: ?string
     * }
     */
    public static function parse(array $message): array
    {
        $type = (string) ($message['type'] ?? 'unknown');

        return match ($type) {
            'text' => [
                'type' => 'text',
                'body' => $message['text']['body'] ?? null,
                'media_id' => null,
                'media_mime_type' => null,
                'media_filename' => null,
                'caption' => null,
            ],
            'image' => self::media('image', $message, '📷 Photo'),
            'video' => self::media('video', $message, '🎥 Video'),
            'audio' => self::media('audio', $message, '🎵 Audio'),
            'document' => self::media('document', $message, '📄 Document'),
            'sticker' => self::media('sticker', $message, 'Sticker'),
            'button' => [
                'type' => 'button',
                'body' => $message['button']['text'] ?? null,
                'media_id' => null,
                'media_mime_type' => null,
                'media_filename' => null,
                'caption' => null,
            ],
            'interactive' => [
                'type' => 'interactive',
                'body' => $message['interactive']['button_reply']['title']
                    ?? $message['interactive']['list_reply']['title']
                    ?? '[interactive]',
                'media_id' => null,
                'media_mime_type' => null,
                'media_filename' => null,
                'caption' => null,
            ],
            default => [
                'type' => $type,
                'body' => '['.$type.']',
                'media_id' => null,
                'media_mime_type' => null,
                'media_filename' => null,
                'caption' => null,
            ],
        };
    }

    public static function isMediaType(string $type): bool
    {
        return in_array($type, ['image', 'video', 'audio', 'document', 'sticker'], true);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{
     *   type: string,
     *   body: ?string,
     *   media_id: ?string,
     *   media_mime_type: ?string,
     *   media_filename: ?string,
     *   caption: ?string
     * }
     */
    protected static function media(string $type, array $message, string $fallback): array
    {
        $node = is_array($message[$type] ?? null) ? $message[$type] : [];
        $caption = isset($node['caption']) ? trim((string) $node['caption']) : null;

        return [
            'type' => $type,
            'body' => filled($caption) ? $caption : $fallback,
            'media_id' => isset($node['id']) ? (string) $node['id'] : null,
            'media_mime_type' => isset($node['mime_type']) ? (string) $node['mime_type'] : null,
            'media_filename' => isset($node['filename']) ? (string) $node['filename'] : null,
            'caption' => $caption,
        ];
    }
}
