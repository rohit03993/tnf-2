<?php

namespace App\Filament\Widgets;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Filament\Resources\Videos\VideoResource;
use App\Models\Submission;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions-widget';

    public static function canView(): bool
    {
        return auth()->user()?->role->canAccessAdmin() ?? false;
    }

    /** @return list<array{label: string, url: string, color: string}> */
    public function getActions(): array
    {
        $user = auth()->user();
        $actions = [
            [
                'label' => 'Create news',
                'url' => ArticleResource::getUrl('create'),
                'color' => 'primary',
            ],
            [
                'label' => 'Create video',
                'url' => VideoResource::getUrl('create'),
                'color' => 'gray',
            ],
        ];

        if (in_array($user?->role, [UserRole::Editor, UserRole::Admin], true)) {
            $pending = Submission::query()->where('status', SubmissionStatus::Pending)->count();

            $actions[] = [
                'label' => $pending > 0 ? "Review submissions ({$pending})" : 'Review submissions',
                'url' => SubmissionResource::getUrl('index'),
                'color' => $pending > 0 ? 'warning' : 'gray',
            ];
            $actions[] = [
                'label' => 'All news',
                'url' => ArticleResource::getUrl('index'),
                'color' => 'gray',
            ];
        }

        return $actions;
    }
}
