<?php

namespace App\Enums;

enum UserRole: string
{
    case Subscriber = 'subscriber';
    case Author = 'author';
    case Editor = 'editor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Subscriber => 'Member',
            self::Author => 'Reporter',
            self::Editor => 'Editor',
            self::Admin => 'Administrator',
        };
    }

    public function canAccessAdmin(): bool
    {
        return match ($this) {
            self::Author, self::Editor, self::Admin => true,
            self::Subscriber => false,
        };
    }
}
