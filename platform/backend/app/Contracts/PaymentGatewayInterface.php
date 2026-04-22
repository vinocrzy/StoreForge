<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent / order on the gateway.
     *
     * @param  Order  $order
     * @param  array  $options  Additional gateway-specific options
     * @return array  Gateway-specific response (client_secret, order_id, etc.)
     */
    public function createPaymentIntent(Order $order, array $options = []): array;

    /**
     * Verify a payment status on the gateway.
     *
     * @param  string  $paymentId  Gateway-specific payment identifier
     * @return array   ['status' => string, 'transaction_id' => string, ...]
     */
    public function verifyPayment(string $paymentId): array;

    /**
     * Process a refund through the gateway.
     *
     * @param  string  $transactionId  Original transaction / payment intent ID
     * @param  float   $amount         Amount to refund
     * @return array   ['refund_id' => string, 'status' => string, ...]
     */
    public function refund(string $transactionId, float $amount): array;

    /**
     * Parse the incoming webhook request into a normalised payload.
     *
     * @param  Request  $request
     * @return array    ['event' => string, 'payment_id' => string, 'metadata' => array, ...]
     */
    public function getWebhookPayload(Request $request): array;

    /**
     * Verify the webhook signature to ensure authenticity.
     *
     * @param  Request  $request
     * @return bool
     */
    public function verifyWebhookSignature(Request $request): bool;
}
