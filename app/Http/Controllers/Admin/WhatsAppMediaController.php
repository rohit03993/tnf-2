<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppMediaService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppMediaController extends Controller
{
    public function __invoke(WhatsappMessage $message, WhatsAppMediaService $media): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user && in_array($user->role, [UserRole::Editor, UserRole::Admin], true),
            403,
        );

        $message = $media->ensureStored($message);

        abort_unless($message->hasStoredMedia(), 404);

        $path = (string) $message->media_path;
        $mime = $message->media_mime_type ?: 'application/octet-stream';
        $filename = $message->media_filename ?: basename($path);

        return Storage::disk(WhatsAppMediaService::DISK)->response($path, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
