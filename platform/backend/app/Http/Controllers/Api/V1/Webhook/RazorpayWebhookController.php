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
 * Razorpay webhook handler. This endpoint is called by Razorpay to notify
 * about payment events. No authentication required — signature verification
 * is used instead.
 *
 * @unauthenticated
 */
class RazorpayWebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * Handle Razorpay webhook
     *
     * Processes Razorpay webhook events. Verifies the X-Razorpay-Signature header
     * before processing. Handles `payment.captured` and `payment.failed` events.
     *
     * @unauthenticated
     * @hideFromAPIDocumentation
     *
     * @response 200 {"status": "ok"}
     * @response 400 {"error": "Invalid signature"}
     */
    public function handle(Request $request): JsonResponse
    {
        $storeId = $this->extractStoreIdFromPayload($request);

        if (!$storeId) {
            Log::warning('Razorpay webhook: could not extract store_id from payload');
            return response()->json(['error' => 'Missing store_id in metadata'], 400);
        }

        try {
            $gateway = PaymentGatewayFactory::makeForStore('razorpay', $storeId);
        } catch (\RuntimeException $e) {
            Log::error('Razorpay webhook: failed to create gateway', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Gateway configuration error'], 500);
        }

        // Verify signature
        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning('Razorpay webhook: invalid signature', ['store_id' => $storeId]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            $payload = $gateway->getWebhookPayload($request);

            $this->paymentService->handleWebhookEvent('razorpay', $payload);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Razorpay webhook processing failed', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
            // Return 200 to prevent Razorpay from retrying on business logic errors
            return response()->json(['status' => 'error', 'message' => 'Processing failed'], 200);
        }
    }

    /**
     * Extract store_id from the raw webhook payload.
     *
     * @param  Request  $request
     * @return int|null
     */
    private function extractStoreIdFromPayload(Request $request): ?int
    {
        $body = json_decode($request->getContent(), true);

        $notes = $body['payload']['payment']['entity']['notes'] ?? [];

        return isset($notes['store_id']) ? (int) $notes['store_id'] : null;
    }
}
