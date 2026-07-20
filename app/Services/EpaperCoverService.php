<?php

namespace App\Services;

use App\Models\EpaperEdition;
use App\Models\Media;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class EpaperCoverService
{
    /**
     * Generate a JPEG cover from PDF page 1 when no featured image exists yet.
     * Best-effort only — viewer and archive still work via PDF.js if this fails.
     */
    public function ensureCover(EpaperEdition $edition): bool
    {
        return $this->diagnose($edition) === null && $this->generateCover($edition);
    }

    /**
     * Resolve or render a JPEG for a specific PDF page (1-based).
     * Used for clip OG previews when pages_json has no raster for that page.
     */
    public function ensurePageImage(EpaperEdition $edition, int $pageNumber): ?string
    {
        if ($pageNumber < 1) {
            return null;
        }

        $edition = $edition->fresh(['featuredMedia']);

        if (! $edition?->pdf_path) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($edition->pdf_path)) {
            return null;
        }

        if ($pageNumber === 1) {
            if ($url = $edition->featuredMedia?->url()) {
                return $url;
            }

            $coverPath = "epaper/covers/{$edition->id}.jpg";

            if ($disk->exists($coverPath)) {
                return '/storage/'.$coverPath;
            }
        }

        $renderPath = "epaper/renders/{$edition->id}/page-{$pageNumber}.jpg";

        if ($disk->exists($renderPath)) {
            return '/storage/'.$renderPath;
        }

        if (! $this->hasRenderer()) {
            return null;
        }

        $disk->makeDirectory("epaper/renders/{$edition->id}");

        $pdfPath = $disk->path($edition->pdf_path);
        $outputPath = $disk->path($renderPath);
        $tempPath = $outputPath.'.tmp.jpg';
        $rendered = false;

        foreach ($this->renderers() as $renderer) {
            if ($this->{$renderer}($pdfPath, $tempPath, $pageNumber)) {
                $rendered = true;
                break;
            }
        }

        if (! $rendered || ! is_file($tempPath)) {
            return null;
        }

        if (is_file($outputPath)) {
            @unlink($outputPath);
        }

        rename($tempPath, $outputPath);

        return '/storage/'.$renderPath;
    }

    public function diagnose(EpaperEdition $edition): ?string
    {
        $edition = $edition->fresh(['featuredMedia']);

        if (! $edition?->pdf_path) {
            return 'no_pdf_path';
        }

        if ($edition->featuredMedia?->url()) {
            return 'already_has_cover';
        }

        if (! Storage::disk('public')->exists($edition->pdf_path)) {
            return 'pdf_file_missing';
        }

        if (! $this->hasRenderer()) {
            return 'no_renderer';
        }

        return null;
    }

    public function hasRenderer(): bool
    {
        if (extension_loaded('imagick')) {
            return true;
        }

        return $this->findBinary(['pdftoppm', 'pdftoppm.exe']) !== null
            || $this->findBinary(['gswin64c', 'gswin32c', 'gs', 'gs.exe']) !== null;
    }

    protected function generateCover(EpaperEdition $edition): bool
    {
        $edition = $edition->fresh(['featuredMedia']);

        if (! $edition?->pdf_path) {
            return false;
        }

        $disk = Storage::disk('public');
        $pdfPath = $disk->path($edition->pdf_path);
        $coverPath = "epaper/covers/{$edition->id}.jpg";
        $coverFullPath = $disk->path($coverPath);

        $disk->makeDirectory('epaper/covers');

        $tempPath = $coverFullPath.'.tmp.jpg';
        $rendered = false;

        foreach ($this->renderers() as $renderer) {
            if ($this->{$renderer}($pdfPath, $tempPath, 1)) {
                $rendered = true;
                break;
            }
        }

        if (! $rendered || ! is_file($tempPath)) {
            return false;
        }

        if (is_file($coverFullPath)) {
            @unlink($coverFullPath);
        }

        rename($tempPath, $coverFullPath);

        $media = Media::query()->updateOrCreate(
            ['path' => $coverPath],
            [
                'disk' => 'public',
                'mime' => 'image/jpeg',
                'size' => filesize($coverFullPath) ?: null,
                'alt' => $edition->title,
            ],
        );

        $edition->update(['featured_media_id' => $media->id]);

        return true;
    }

    /** @return list<string> */
    protected function renderers(): array
    {
        return ['renderWithImagick', 'renderWithPdftoppm', 'renderWithGhostscript'];
    }

    protected function renderWithImagick(string $pdfPath, string $outputPath, int $pageNumber = 1): bool
    {
        if (! extension_loaded('imagick')) {
            return false;
        }

        try {
            $imagick = new \Imagick;
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath.'['.max(0, $pageNumber - 1).']');
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(85);
            $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $imagick->writeImage($outputPath);
            $imagick->clear();
            $imagick->destroy();

            return is_file($outputPath);
        } catch (\Throwable $exception) {
            Log::debug('ePaper cover: Imagick failed', ['error' => $exception->getMessage()]);

            return false;
        }
    }

    protected function renderWithPdftoppm(string $pdfPath, string $outputPath, int $pageNumber = 1): bool
    {
        $binary = $this->findBinary(['pdftoppm', 'pdftoppm.exe']);

        if (! $binary) {
            return false;
        }

        $prefix = preg_replace('/\.jpg$/i', '', $outputPath);

        if (! is_string($prefix)) {
            return false;
        }

        try {
            $result = Process::timeout(120)->run([
                $binary,
                '-jpeg',
                '-f', (string) $pageNumber,
                '-l', (string) $pageNumber,
                '-singlefile',
                '-r', '150',
                $pdfPath,
                $prefix,
            ]);

            if (! $result->successful()) {
                Log::debug('ePaper cover: pdftoppm failed', ['error' => $result->errorOutput()]);

                return false;
            }

            if (is_file($outputPath)) {
                return true;
            }

            if (is_file($prefix.'-1.jpg')) {
                return rename($prefix.'-1.jpg', $outputPath);
            }

            return false;
        } catch (\Throwable $exception) {
            Log::debug('ePaper cover: pdftoppm exception', ['error' => $exception->getMessage()]);

            return false;
        }
    }

    protected function renderWithGhostscript(string $pdfPath, string $outputPath, int $pageNumber = 1): bool
    {
        $binary = $this->findBinary(['gswin64c', 'gswin32c', 'gs', 'gs.exe']);

        if (! $binary) {
            return false;
        }

        try {
            $result = Process::timeout(120)->run([
                $binary,
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                '-sDEVICE=jpeg',
                '-dFirstPage='.$pageNumber,
                '-dLastPage='.$pageNumber,
                '-r150',
                '-dJPEGQ=85',
                '-sOutputFile='.$outputPath,
                $pdfPath,
            ]);

            if (! $result->successful()) {
                Log::debug('ePaper cover: Ghostscript failed', ['error' => $result->errorOutput()]);

                return false;
            }

            return is_file($outputPath);
        } catch (\Throwable $exception) {
            Log::debug('ePaper cover: Ghostscript exception', ['error' => $exception->getMessage()]);

            return false;
        }
    }

    /** @param list<string> $candidates */
    protected function findBinary(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            try {
                $result = Process::timeout(5)->run([$candidate, '--version']);

                if ($result->successful()) {
                    return $candidate;
                }

                $result = Process::timeout(5)->run([$candidate, '-v']);

                if ($result->successful()) {
                    return $candidate;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
