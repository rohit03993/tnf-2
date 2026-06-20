<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ContentPublishingGuard
{
    public static function enforce(User $user, array $data, bool $requiresCategories = true): array
    {
        $data = self::normalizeCategories($data);

        if ($user->role === UserRole::Author) {
            $data = self::enforceAuthorRules($user, $data, $requiresCategories);
        }

        return self::ensurePublishedAtWhenLive($data);
    }

    /** @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function normalizeCategories(array $data): array
    {
        $categories = $data['categories'] ?? [];

        if (! is_array($categories)) {
            $categories = filled($categories) ? [$categories] : [];
            $data['categories'] = $categories;
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function enforceAuthorRules(User $user, array $data, bool $requiresCategories): array
    {
        $status = self::statusValue($data['status'] ?? ContentStatus::Draft->value);
        $categories = $data['categories'] ?? [];

        if ($user->requires_approval) {
            if ($status === ContentStatus::Published->value) {
                $data['status'] = ContentStatus::Pending->value;
                $status = ContentStatus::Pending->value;
            }

            if ($requiresCategories && $status === ContentStatus::Pending->value && $categories === []) {
                throw ValidationException::withMessages([
                    'categories' => 'Select at least one category before submitting for review.',
                ]);
            }

            $data['published_at'] = null;

            return $data;
        }

        if ($status === ContentStatus::Published->value) {
            if ($requiresCategories && $categories === []) {
                throw ValidationException::withMessages([
                    'categories' => 'Select at least one category before publishing.',
                ]);
            }
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function ensurePublishedAtWhenLive(array $data): array
    {
        if (self::statusValue($data['status'] ?? null) === ContentStatus::Published->value
            && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected static function statusValue(mixed $status): string
    {
        if ($status instanceof ContentStatus) {
            return $status->value;
        }

        return (string) ($status ?? ContentStatus::Draft->value);
    }
}
