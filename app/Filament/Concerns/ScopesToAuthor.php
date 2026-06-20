<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;

trait ScopesToAuthor
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role === UserRole::Author) {
            $query->where('author_id', $user->id);
        }

        return $query;
    }
}
