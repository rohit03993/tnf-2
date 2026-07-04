<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'password',
        'role',
        'is_active',
        'requires_approval',
        'subscription_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
            'subscription_active' => 'boolean',
        ];
    }

    public function isReporter(): bool
    {
        return $this->role === UserRole::Author;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->role->canAccessAdmin();
    }

    public function canSelfPublishArticles(): bool
    {
        return $this->isReporter() && ! $this->requires_approval;
    }

    public function hasPremiumAccess(): bool
    {
        return \App\Services\PremiumAccess::userHasPremium($this);
    }

    public function homeUrl(): string
    {
        if ($this->role->canAccessAdmin()) {
            return url('/admin');
        }

        return route('account');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'author_id');
    }

    public function epaperEditions(): HasMany
    {
        return $this->hasMany(EpaperEdition::class, 'author_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return '/storage/'.$this->avatar_path;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $url = $this->avatarUrl();

        return $url ? url($url) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
    }
}
