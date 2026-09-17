<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ApplyPaymentUpdate;
use App\Http\Controllers\Controller;
use App\Services\Payment\GatewayRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * The gateway's notification endpoint (M2.6, docs/04 § 5).
 *
 * Runs on the api stack: no session, no CSRF, no auth. The gateway is
 * authenticated by its signature and nothing else.
 *
 * Order of business, and the reasoning for it:
 *   1. Reject a body that is not JSON at all — 400, no exception leaked.
 *   2. Resolve the driver named in the URL; an unknown one is a 404.
 *   3. Verify the signature before touching the database. docs/04 § 5 is
 *      explicit about this, and it overrides the playbook's suggestion to log
 *      the payload first: writing an unverified body to payments.raw_payload
 *      would let anyone who guesses a gateway_ref overwrite the evidence a
 *      dispute rests on.
 *   4. Hand the parsed update to ApplyPaymentUpdate, which does the locking.
 *   5. Return 200 — including for a replay, or the gateway retries forever.
 */
final class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $gateway,
        GatewayRegistry $registry,
        ApplyPaymentUpdate $applyUpdate,
    ): JsonResponse {
        try {
            // A gateway posting a truncated body is a broken delivery, not an
            // attack; 400 tells it to send again without raising a 500.
            // Laravel hands back an empty bag rather than throwing, so the
            // body is decoded here to tell "no fields" from "not JSON".
            if ($request->isJson() && $request->getContent() !== '') {
                json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $exception) {
            Log::warning('Webhook pembayaran dengan JSON rusak.', [
                'gateway' => $gateway,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Malformed JSON.'], 400);
        }

        $driver = $registry->driver($gateway);

        if ($driver === null) {
            Log::warning('Webhook pembayaran untuk gateway tidak dikenal.', ['gateway' => $gateway]);

            return response()->json(['message' => 'Unknown gateway.'], 404);
        }

        if (! $driver->verifyWebhook($request)) {
            Log::warning('Tanda tangan webhook pembayaran tidak sah.', [
                'gateway' => $gateway,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $update = $driver->parseWebhook($request);
        $applied = $applyUpdate($update, $gateway);

        if (! $applied) {
            Log::warning('Webhook pembayaran dengan referensi tidak dikenal.', [
                'gateway' => $gateway,
                'reference' => $update->reference,
            ]);

            return response()->json(['message' => 'Unknown payment reference.'], 404);
        }

        return response()->json(['message' => 'OK']);
    }
}
