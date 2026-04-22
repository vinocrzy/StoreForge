<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $publishableKey;
    private string $webhookSecret;

    /**
     * @param string $secretKey       Encrypted Stripe secret key
     * @param string $publishableKey  Encrypted Stripe publishable key
     * @param string $webhookSecret   Encrypted Stripe webhook secret
     */
    public function __construct(string $secretKey, string $publishableKey, string $webhookSecret)
    {
        $this->secretKey = Crypt::decryptString($secretKey);
        $this->publishableKey = Crypt::decryptString($publishableKey);
        $this->webhookSecret = Crypt::decryptString($webhookSecret);
    }

    /**
     * Create a Stripe PaymentIntent for the given order.
     *
     * @param  Order  $order
     * @param  array  $options
     * @return array  ['client_secret' => string, 'payment_intent_id' => string]
     *
     * @throws \RuntimeException
     */
    public function createPaymentIntent(Order $order, array $options = []): array
    {
        try {
            \Stripe\Stripe::setApiKey($this->secretKey);

            $params = [
                'amount' => (int) round($order->total * 100), // cents
                'currency' => strtolower($order->currency ?? 'usd'),
                'metadata' => [
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'order_number' => $order->order_number,
                ],
            ];

            if (!empty($options['payment_method_types'])) {
                $params['payment_method_types'] = $options['payment_method_types'];
            } else {
                $params['automatic_payment_methods'] = ['enabled' => true];
            }

            $intent = \Stripe\PaymentIntent::create($params);

            return [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'gateway' => 'stripe',
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe createPaymentIntent failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Stripe payment creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment status on Stripe.
     *
     * @param  string  $paymentId  Stripe PaymentIntent ID (pi_xxx)
     * @return array
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            \Stripe\Stripe::setApiKey($this->secretKey);

            $intent = \Stripe\PaymentIntent::retrieve($paymentId);

            return [
                'status' => $intent->status,
                'transaction_id' => $intent->id,
                'amount' => $intent->amount / 100,
                'currency' => $intent->currency,
                'metadata' => $intent->metadata->toArray(),
                'payment_method' => $intent->payment_method,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe verifyPayment failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Stripe payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Refund a Stripe payment.
     *
     * @param  string  $transactionId  Stripe PaymentIntent ID
     * @param  float   $amount         Amount to refund
     * @return array
     */
    public function refund(string $transactionId, float $amount): array
    {
        try {
            \Stripe\Stripe::setApiKey($this->secretKey);

            $refund = \Stripe\Refund::create([
                'payment_intent' => $transactionId,
                'amount' => (int) round($amount * 100),
            ]);

            return [
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Stripe refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse a Stripe webhook payload.
     *
     * @param  Request  $request
     * @return array
     */
    public function getWebhookPayload(Request $request): array
    {
        $event = $this->constructEvent($request);

        $paymentIntent = $event->data->object;

        return [
            'event' => $event->type,
            'payment_id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'amount' => ($paymentIntent->amount ?? 0) / 100,
            'currency' => $paymentIntent->currency ?? null,
            'metadata' => isset($paymentIntent->metadata) ? $paymentIntent->metadata->toArray() : [],
            'raw' => $event->toArray(),
        ];
    }

    /**
     * Verify the Stripe webhook signature.
     *
     * @param  Request  $request
     * @return bool
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        try {
            $this->constructEvent($request);
            return true;
        } catch (\Exception $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Construct and verify the Stripe event from the webhook request.
     *
     * @param  Request  $request
     * @return \Stripe\Event
     *
     * @throws \Stripe\Exception\SignatureVerificationException
     * @throws \UnexpectedValueException
     */
    private function constructEvent(Request $request): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $request->getContent(),
            $request->header('Stripe-Signature'),
            $this->webhookSecret
        );
    }
}
