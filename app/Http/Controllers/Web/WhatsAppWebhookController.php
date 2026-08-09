<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppCloudService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request, WhatsAppCloudService $whatsApp): SymfonyResponse
    {
        [$mode, $token, $challenge] = $this->hubParams($request);

        $verified = $whatsApp->verifyWebhookChallenge($mode, $token, $challenge);

        if ($verified === null) {
            return response('Forbidden', 403);
        }

        return response($verified, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, WhatsAppCloudService $whatsApp): Response
    {
        $raw = $request->getContent();

        if (! $whatsApp->signatureIsValid($request->header('X-Hub-Signature-256'), $raw)) {
            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        if (is_array($payload)) {
            $whatsApp->handleWebhookPayload($payload);
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Meta sends hub.mode / hub.verify_token / hub.challenge.
     * PHP may expose them as hub_mode; Laravel dot-query access can nest incorrectly.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    protected function hubParams(Request $request): array
    {
        $query = $request->query();

        $mode = $query->get('hub_mode')
            ?? $query->get('hub.mode')
            ?? data_get($query->all(), 'hub.mode');

        $token = $query->get('hub_verify_token')
            ?? $query->get('hub.verify_token')
            ?? data_get($query->all(), 'hub.verify_token');

        $challenge = $query->get('hub_challenge')
            ?? $query->get('hub.challenge')
            ?? data_get($query->all(), 'hub.challenge');

        if (($mode === null || $token === null || $challenge === null) && filled($request->server->get('QUERY_STRING'))) {
            parse_str((string) $request->server->get('QUERY_STRING'), $raw);

            $mode ??= $raw['hub_mode'] ?? $raw['hub.mode'] ?? null;
            $token ??= $raw['hub_verify_token'] ?? $raw['hub.verify_token'] ?? null;
            $challenge ??= $raw['hub_challenge'] ?? $raw['hub.challenge'] ?? null;
        }

        return [
            is_string($mode) ? $mode : null,
            is_string($token) ? $token : null,
            is_string($challenge) ? $challenge : (is_numeric($challenge) ? (string) $challenge : null),
        ];
    }
}
