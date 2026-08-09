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
        $challenge = $whatsApp->verifyWebhookChallenge(
            $request->query('hub_mode'),
            $request->query('hub_verify_token'),
            $request->query('hub_challenge'),
        );

        if ($challenge === null) {
            return response('Forbidden', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
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
}
