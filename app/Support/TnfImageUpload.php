<?php

namespace App\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

class TnfImageUpload
{
    public static function maxKb(): int
    {
        return max(1, (int) config('tnf.max_image_kb', 150));
    }

    public static function maxBytes(): int
    {
        return self::maxKb() * 1024;
    }

    /** @return list<string> */
    public static function validationRules(bool $required = false): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:jpeg,jpg,png,webp,gif',
            'max:'.self::maxKb(),
        ];

        return array_values(array_filter($rules));
    }

    public static function validationMessage(): string
    {
        return 'Each image must be '.self::maxKb().' KB or smaller.';
    }

    public static function helperText(?string $extra = null): string
    {
        $text = 'JPEG, PNG, WebP, or GIF — max '.self::maxKb().' KB. Compress before upload if needed.';

        return $extra ? $text.' '.$extra : $text;
    }

    public static function applyTo(FileUpload $field, ?string $extraHelp = null): FileUpload
    {
        return $field
            ->maxSize(self::maxKb())
            ->helperText(self::helperText($extraHelp));
    }

    public static function logoField(FileUpload $field): FileUpload
    {
        return $field
            ->image()
            ->maxSize(2048)
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->helperText(
                'Upload your full brand logo (horizontal PNG or JPG, max 2 MB). '
                .'No cropping — we keep the original shape and resize it for header, footer, and sign-in.',
            );
    }

    public static function storedFileWithinLimit(string $disk, string $path): bool
    {
        if (! Storage::disk($disk)->exists($path)) {
            return false;
        }

        return Storage::disk($disk)->size($path) <= self::maxBytes();
    }
}
