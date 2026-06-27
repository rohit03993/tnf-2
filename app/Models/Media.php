<?php

namespace App\Models;

use App\Support\FrontendUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = ['disk', 'path', 'mime', 'size', 'alt'];

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if ($this->disk === 'public') {
            return '/storage/'.ltrim($this->path, '/');
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function absoluteUrl(): ?string
    {
        $path = $this->url();

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return FrontendUrl::to($path);
    }

    public function humanSize(): string
    {
        if (! $this->size) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, $unit === 0 ? 0 : 1).' '.$units[$unit];
    }

    public function isInUse(): bool
    {
        return Article::query()->where('featured_media_id', $this->id)->exists()
            || Video::query()->where('featured_media_id', $this->id)->exists()
            || EpaperEdition::query()->where('featured_media_id', $this->id)->exists()
            || Submission::query()->where('featured_media_id', $this->id)->exists();
    }

    public function deleteWithFile(): void
    {
        if ($this->isInUse()) {
            throw new \RuntimeException('This file is still attached to published content.');
        }

        if ($this->path && Storage::disk($this->disk)->exists($this->path)) {
            Storage::disk($this->disk)->delete($this->path);
        }

        $this->delete();
    }
}
