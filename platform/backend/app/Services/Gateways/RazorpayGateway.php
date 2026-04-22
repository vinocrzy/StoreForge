<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class RazorpayGateway implements PaymentGatewayInterface
{
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    /**
     * @param string $keyId          Encrypted Razorpay key ID
     * @param string $keySecret      Encrypted Razorpay key secret
     * @param string $webhookSecret  Encrypted Razorpay webhook secret
     */
    public function __construct(string $keyId, string $keySecret, string $webhookSecret)
    {
        $this->keyId = Crypt::decryptString($keyId);
        $this->keySecret = Crypt::decryptString($keySecret);
        $this->webhookSecret = Crypt::decryptString($webhookSecret);
    }

    /**
     * Create a Razorpay Order for the given order.
     *
     * @param  Order  $order
     * @param  array  $options
     * @return array  ['razorpay_order_id' => string, 'key_id' => string]
     *
     * @throws \RuntimeException
     */
    public function createPaymentIntent(Order $order, array $options = []): array
    {
        try {
            $api = new \Razorpay\Api\Api($this->keyId, $this->keySecret);

            $razorpayOrder = $api->order->create([
                'amount' => (int) round($order->total * 100), // paise
                'currency' => strtoupper($order->currency ?? 'INR'),
                'receipt' => $order->order_number,
                'notes' => [
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'order_number' => $order->order_number,
                ],
            ]);

            return [
                'razorpay_order_id' => $razorpayOrder->id,
                'key_id' => $this->keyId,
                'amount' => $razorpayOrder->amount,
                'currency' => $razorpayOrder->currency,
                'gateway' => 'razorpay',
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay createPaymentIntent failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Razorpay order creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify a Razorpay payment by payment ID.
     *
     * @param  string  $paymentId  Razorpay payment ID (pay_xxx)
     * @return array
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $api = new \Razorpay\Api\Api($this->keyId, $this->keySecret);

            $payment = $api->payment->fetch($paymentId);

            return [
                'status' => $payment->status,
                'transaction_id' => $payment->id,
                'amount' => $payment->amount / 100,
                'currency' => $payment->currency,
                'metadata' => $payment->notes ? $payment->notes->toArray() : [],
                'payment_method' => $payment->method ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay verifyPayment failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Razorpay payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Refund a Razorpay payment.
     *
     * @param  string  $transactionId  Razorpay payment ID (pay_xxx)
     * @param  float   $amount         Amount to refund
     * @return array
     */
    public function refund(string $transactionId, float $amount): array
    {
        try {
            $api = new \Razorpay\Api\Api($this->keyId, $this->keySecret);

            $refund = $api->payment->fetch($transactionId)->refund([
                'amount' => (int) round($amount * 100),
            ]);

            return [
                'refund_id' => $refund->id,
                'status' => $refund->status ?? 'processed',
                'amount' => $refund->amount / 100,
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay refund failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Razorpay refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse a Razorpay webhook payload.
     *
     * @param  Request  $request
     * @return array
     */
    public function getWebhookPayload(Request $request): array
    {
        $body = json_decode($request->getContent(), true);

        $event = $body['event'] ?? '';
        $entity = $body['payload']['payment']['entity'] ?? [];

        return [
            'event' => $event,
            'payment_id' => $entity['id'] ?? null,
            'status' => $entity['status'] ?? null,
            'amount' => isset($entity['amount']) ? $entity['amount'] / 100 : 0,
            'currency' => $entity['currency'] ?? null,
            'metadata' => $entity['notes'] ?? [],
            'raw' => $body,
        ];
    }

    /**
     * Verify the Razorpay webhook signature.
     *
     * @param  Request  $request
     * @return bool
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Razorpay-Signature');

        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
