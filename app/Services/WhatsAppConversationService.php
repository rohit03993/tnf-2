<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappMessage;
use App\Support\PhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsAppConversationService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentConversations(?string $search = null, int $limit = 200): Collection
    {
        $latestIds = WhatsappMessage::query()
            ->selectRaw('MAX(id) as id')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy('phone')
            ->pluck('id');

        $messages = WhatsappMessage::query()
            ->with('user:id,name,phone')
            ->whereIn('id', $latestIds)
            ->orderByDesc(DB::raw('COALESCE(provider_timestamp, created_at)'))
            ->limit($limit)
            ->get();

        return $messages
            ->map(function (WhatsappMessage $message): array {
                $phone = PhoneNumber::normalize($message->phone) ?? (string) $message->phone;
                $user = $message->user;
                $lastAt = $message->provider_timestamp ?? $message->created_at;

                return [
                    'phone' => $phone,
                    'phone_display' => PhoneNumber::formatDisplay($phone) ?? $phone,
                    'name' => $user?->name ?: ('+'.$phone),
                    'user_id' => $user?->id,
                    'preview' => Str::limit((string) ($message->body ?: '['.$message->type.']'), 80),
                    'last_direction' => $message->direction,
                    'last_at' => $lastAt?->toIso8601String(),
                    'last_at_label' => $lastAt?->timezone(config('app.timezone'))->format('M j, g:i A'),
                    'unread' => WhatsappMessage::query()
                        ->where('phone', $phone)
                        ->where('direction', 'inbound')
                        ->whereNull('read_at')
                        ->exists(),
                    'session_open' => WhatsappMessage::query()
                        ->where('phone', $phone)
                        ->where('direction', 'inbound')
                        ->where('created_at', '>=', now()->subHours(24))
                        ->exists(),
                ];
            })
            ->when(filled($search), function (Collection $rows) use ($search) {
                $q = Str::lower((string) $search);

                return $rows->filter(function (array $row) use ($q): bool {
                    return str_contains(Str::lower($row['name']), $q)
                        || str_contains(Str::lower($row['phone']), $q)
                        || str_contains(Str::lower($row['preview']), $q);
                })->values();
            });
    }

    /**
     * @return Collection<int, WhatsappMessage>
     */
    public function thread(string $phone, int $limit = 200): Collection
    {
        $phone = PhoneNumber::normalize($phone) ?? $phone;

        return WhatsappMessage::query()
            ->where('phone', $phone)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function markThreadRead(string $phone): void
    {
        $phone = PhoneNumber::normalize($phone) ?? $phone;

        WhatsappMessage::query()
            ->where('phone', $phone)
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function displayName(string $phone): string
    {
        $phone = PhoneNumber::normalize($phone) ?? $phone;
        $user = User::query()->where('phone', $phone)->first();

        return $user?->name ?: (PhoneNumber::formatDisplay($phone) ?? $phone);
    }
}
