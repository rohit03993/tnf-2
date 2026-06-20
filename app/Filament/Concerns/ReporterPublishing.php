<?php

namespace App\Filament\Concerns;

use App\Enums\ContentStatus;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

trait ReporterPublishing
{
    public static function categorySelect(): Select
    {
        return Select::make('categories')
            ->label('Category')
            ->relationship(
                'categories',
                'name',
                fn ($query) => $query->orderBy('name'),
            )
            ->multiple(fn () => ! auth()->user()?->isReporter())
            ->preload()
            ->searchable()
            ->live()
            ->columnSpanFull()
            ->required(function (Get $get): bool {
                $user = auth()->user();

                if (! $user?->isReporter()) {
                    return false;
                }

                $status = $get('status');

                return in_array($status, [
                    ContentStatus::Published->value,
                    ContentStatus::Pending->value,
                ], true);
            })
            ->helperText(fn () => auth()->user()?->isReporter()
                ? 'Required before publish or submit for review.'
                : 'Assign one or more categories.');
    }

    /** @return array<string, string> */
    public static function statusOptionsFor(?User $user): array
    {
        $labels = [
            ContentStatus::Draft->value => 'Draft',
            ContentStatus::Pending->value => 'Pending review',
            ContentStatus::Published->value => 'Published',
        ];

        if (! $user?->isReporter()) {
            return $labels;
        }

        if ($user->requires_approval) {
            return [
                ContentStatus::Draft->value => $labels[ContentStatus::Draft->value],
                ContentStatus::Pending->value => $labels[ContentStatus::Pending->value],
            ];
        }

        return $labels;
    }

    public static function statusHelperFor(?User $user): ?string
    {
        if (! $user?->isReporter()) {
            return null;
        }

        if ($user->requires_approval) {
            return 'Your account is approval-locked. Save as Pending review for editor approval.';
        }

        return 'You can publish directly when a category is selected.';
    }
}
