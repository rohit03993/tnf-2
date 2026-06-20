<?php

namespace App\Services;

use App\Models\EpaperEdition;
use App\Support\TnfSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PdfClient
{
    public function isConfigured(): bool
    {
        return filled(TnfSetting::get('pdf_service_url'));
    }

    /** @return array{job_id: string|null, response: array<string, mixed>|null} */
    public function enqueue(EpaperEdition $edition): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('PDF_SERVICE_URL is not configured in .env');
        }

        if (! $edition->pdf_path) {
            throw new \RuntimeException('Edition has no PDF file uploaded.');
        }

        $sourceUrl = StorageUrl::publicAsset($edition->pdf_path);

        if (! $sourceUrl) {
            throw new \RuntimeException('Could not build a public URL for the uploaded PDF. Run php artisan storage:link.');
        }

        $externalId = $this->externalId($edition);
        $idempotencyKey = $externalId.'-'.Str::uuid();

        $response = Http::timeout(30)
            ->withHeaders($this->requestHeaders())
            ->post(rtrim((string) TnfSetting::get('pdf_service_url'), '/').'/pdf/process', [
                'source_url' => $sourceUrl,
                'external_id' => $externalId,
                'idempotency_key' => $idempotencyKey,
                'callback_url' => url('/api/v1/internal/pdf-job-complete'),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('PDF service error: '.$response->body());
        }

        $body = $response->json() ?? [];

        return [
            'job_id' => $body['job_id'] ?? $body['id'] ?? null,
            'response' => $body,
        ];
    }

    public function externalId(EpaperEdition $edition): string
    {
        return 'edition-'.$edition->id;
    }

    /** @return array<string, string> */
    protected function requestHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($secret = TnfSetting::get('pdf_service_secret')) {
            $headers['X-Service-Secret'] = $secret;
        }

        return $headers;
    }

    /** @return array{ok: bool, message: string, status: int|null} */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'PDF service URL is not configured.',
                'status' => null,
            ];
        }

        $baseUrl = rtrim((string) TnfSetting::get('pdf_service_url'), '/');

        foreach (['/health', '/'] as $path) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders($this->requestHeaders())
                    ->get($baseUrl.$path);

                if ($response->successful()) {
                    return [
                        'ok' => true,
                        'message' => 'PDF service responded on '.$path,
                        'status' => $response->status(),
                    ];
                }
            } catch (\Throwable $exception) {
                if ($path === '/') {
                    return [
                        'ok' => false,
                        'message' => $exception->getMessage(),
                        'status' => null,
                    ];
                }
            }
        }

        return [
            'ok' => false,
            'message' => 'PDF service did not respond successfully.',
            'status' => null,
        ];
    }
}
