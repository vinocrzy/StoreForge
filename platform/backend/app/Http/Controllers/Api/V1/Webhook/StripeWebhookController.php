<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Gateways\PaymentGatewayFactory;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group Webhooks
 *
 * Stripe webhook handler. This endpoint is called by Stripe to notify
 * about payment events. No authentication required — signature verification
 * is used instead.
 *
 * @unauthenticated
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * Handle Stripe webhook
     *
     * Processes Stripe webhook events. Verifies the Stripe-Signature header
     * before processing. Handles `payment_intent.succeeded` and
     * `payment_intent.payment_failed` events.
     *
     * @unauthenticated
     * @hideFromAPIDocumentation
     *
     * @response 200 {"status": "ok"}
     * @response 400 {"error": "Invalid signature"}
     */
    public function handle(Request $request): JsonResponse
    {
        // We need to find the store_id to get the webhook secret.
        // Stripe embeds metadata in the PaymentIntent; we peek at the raw body
        // to extract store_id before full verification.
        $storeId = $this->extractStoreIdFromPayload($request);

        if (!$storeId) {
            Log::warning('Stripe webhook: could not extract store_id from payload');
            return response()->json(['error' => 'Missing store_id in metadata'], 400);
        }

        try {
            $gateway = PaymentGatewayFactory::makeForStore('stripe', $storeId);
        } catch (\RuntimeException $e) {
            Log::error('Stripe webhook: failed to create gateway', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Gateway configuration error'], 500);
        }

        // Verify signature
        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning('Stripe webhook: invalid signature', ['store_id' => $storeId]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            $payload = $gateway->getWebhookPayload($request);

            $this->paymentService->handleWebhookEvent('stripe', $payload);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
            // Return 200 to prevent Stripe from retrying on business logic errors
            return response()->json(['status' => 'error', 'message' => 'Processing failed'], 200);
        }
    }

    /**
     * Extract store_id from the raw webhook payload without full verification.
     *
     * @param  Request  $request
     * @return int|null
     */
    private function extractStoreIdFromPayload(Request $request): ?int
    {
        $body = json_decode($request->getContent(), true);

        $metadata = $body['data']['object']['metadata'] ?? [];

        return isset($metadata['store_id']) ? (int) $metadata['store_id'] : null;
    }
}
