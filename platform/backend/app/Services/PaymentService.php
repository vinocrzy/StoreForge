<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Services\Gateways\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create a payment intent via the store's configured gateway.
     *
     * @param  Order  $order
     * @return array  Gateway-specific client data (client_secret for Stripe, order_id for Razorpay)
     *
     * @throws \RuntimeException If no gateway configured or order is already paid
     */
    public function createPaymentIntent(Order $order): array
    {
        if ($order->payment_status === 'paid') {
            throw new \RuntimeException('Order is already paid.');
        }

        $gateway = PaymentGatewayFactory::make();

        if (!$gateway) {
            throw new \RuntimeException('No payment gateway configured for this store. Use manual payment.');
        }

        $result = $gateway->createPaymentIntent($order);

        // Store the pending payment intent reference
        Payment::create([
            'store_id' => $order->store_id,
            'order_id' => $order->id,
            'transaction_id' => $result['payment_intent_id'] ?? $result['razorpay_order_id'] ?? null,
            'gateway' => $result['gateway'],
            'payment_method' => 'online',
            'amount' => $order->total,
            'currency' => $order->currency ?? 'USD',
            'status' => 'pending',
            'metadata' => $result,
        ]);

        return $result;
    }

    /**
     * Handle an incoming webhook event from a payment gateway.
     *
     * This method is IDEMPOTENT — if the payment was already processed, it skips.
     *
     * @param  string  $gateway   'stripe' or 'razorpay'
     * @param  array   $payload   Normalised payload from getWebhookPayload()
     * @return void
     */
    public function handleWebhookEvent(string $gateway, array $payload): void
    {
        $event = $payload['event'] ?? '';
        $paymentId = $payload['payment_id'] ?? null;
        $metadata = $payload['metadata'] ?? [];
        $storeId = (int) ($metadata['store_id'] ?? 0);
        $orderId = (int) ($metadata['order_id'] ?? 0);

        if (!$storeId || !$orderId) {
            Log::warning("Webhook {$gateway}: missing store_id or order_id in metadata", $payload);
            return;
        }

        // Map gateway events to our internal statuses
        $isSucceeded = in_array($event, [
            'payment_intent.succeeded',   // Stripe
            'payment.captured',           // Razorpay
        ]);

        $isFailed = in_array($event, [
            'payment_intent.payment_failed', // Stripe
            'payment.failed',                // Razorpay
        ]);

        if (!$isSucceeded && !$isFailed) {
            Log::info("Webhook {$gateway}: unhandled event '{$event}'", ['payload_keys' => array_keys($payload)]);
            return;
        }

        // Find the order without tenant scope (webhook has no tenant context)
        $order = Order::withoutGlobalScope('store')
            ->where('id', $orderId)
            ->where('store_id', $storeId)
            ->first();

        if (!$order) {
            Log::warning("Webhook {$gateway}: order not found", ['order_id' => $orderId, 'store_id' => $storeId]);
            return;
        }

        // IDEMPOTENT: skip if already in final state
        if ($isSucceeded && $order->payment_status === 'paid') {
            Log::info("Webhook {$gateway}: order {$orderId} already paid, skipping");
            return;
        }

        DB::transaction(function () use ($order, $gateway, $payload, $paymentId, $isSucceeded) {
            if ($isSucceeded) {
                $this->markOrderPaid($order, $gateway, $paymentId, $payload);
            } else {
                $this->markPaymentFailed($order, $gateway, $paymentId, $payload);
            }
        });
    }

    /**
     * Process a refund through the store's payment gateway.
     *
     * @param  Order   $order
     * @param  float   $amount
     * @param  string  $reason
     * @return array   Refund result from gateway
     *
     * @throws \RuntimeException
     */
    public function processRefund(Order $order, float $amount, string $reason = ''): array
    {
        if ($order->payment_status !== 'paid') {
            throw new \RuntimeException('Order is not paid. Cannot process refund.');
        }

        // Find the completed payment to get the transaction ID and gateway
        $payment = Payment::where('order_id', $order->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$payment) {
            throw new \RuntimeException('No completed payment found for this order.');
        }

        if (!$payment->transaction_id) {
            throw new \RuntimeException('No transaction ID found. Cannot process gateway refund.');
        }

        if ($payment->gateway === 'manual') {
            throw new \RuntimeException('Manual payments cannot be refunded through a gateway.');
        }

        $gateway = PaymentGatewayFactory::make();

        if (!$gateway) {
            throw new \RuntimeException('No payment gateway configured for this store.');
        }

        $result = $gateway->refund($payment->transaction_id, $amount);

        // Record refund payment entry
        Payment::create([
            'store_id' => $order->store_id,
            'order_id' => $order->id,
            'transaction_id' => $result['refund_id'] ?? null,
            'gateway' => $payment->gateway,
            'payment_method' => $payment->payment_method,
            'amount' => -$amount, // negative for refund
            'currency' => $payment->currency,
            'status' => 'refunded',
            'metadata' => array_merge($result, ['reason' => $reason]),
            'processed_at' => now(),
        ]);

        // Update order payment status
        $totalPaid = Payment::where('order_id', $order->id)
            ->whereIn('status', ['completed', 'refunded'])
            ->sum('amount');

        if ($totalPaid <= 0) {
            $order->update(['payment_status' => 'refunded']);
        } else {
            $order->update(['payment_status' => 'partially_refunded']);
        }

        Log::info('Refund processed', [
            'order_id' => $order->id,
            'amount' => $amount,
            'gateway' => $payment->gateway,
            'refund_id' => $result['refund_id'] ?? null,
        ]);

        return $result;
    }

    /**
     * Mark an order as paid and create the completed payment record.
     *
     * @param  Order   $order
     * @param  string  $gateway
     * @param  string|null  $paymentId
     * @param  array   $payload
     * @return void
     */
    private function markOrderPaid(Order $order, string $gateway, ?string $paymentId, array $payload): void
    {
        // Update or create the payment record
        $payment = Payment::withoutGlobalScope('store')
            ->where('order_id', $order->id)
            ->where('gateway', $gateway)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $payment->update([
                'transaction_id' => $paymentId,
                'status' => 'completed',
                'metadata' => $payload,
                'processed_at' => now(),
            ]);
        } else {
            Payment::withoutGlobalScope('store')->create([
                'store_id' => $order->store_id,
                'order_id' => $order->id,
                'transaction_id' => $paymentId,
                'gateway' => $gateway,
                'payment_method' => 'online',
                'amount' => $payload['amount'] ?? $order->total,
                'currency' => $payload['currency'] ?? $order->currency ?? 'USD',
                'status' => 'completed',
                'metadata' => $payload,
                'processed_at' => now(),
            ]);
        }

        // Update order
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $gateway,
            'paid_at' => now(),
        ]);

        Log::info('Payment succeeded via webhook', [
            'order_id' => $order->id,
            'gateway' => $gateway,
            'payment_id' => $paymentId,
        ]);
    }

    /**
     * Mark a payment as failed.
     *
     * @param  Order   $order
     * @param  string  $gateway
     * @param  string|null  $paymentId
     * @param  array   $payload
     * @return void
     */
    private function markPaymentFailed(Order $order, string $gateway, ?string $paymentId, array $payload): void
    {
        $payment = Payment::withoutGlobalScope('store')
            ->where('order_id', $order->id)
            ->where('gateway', $gateway)
            ->where('status', 'pending')
            ->first();

        $failureReason = $payload['raw']['data']['object']['last_payment_error']['message']
            ?? $payload['raw']['payload']['payment']['entity']['error_description']
            ?? 'Payment failed';

        if ($payment) {
            $payment->update([
                'transaction_id' => $paymentId,
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'metadata' => $payload,
                'processed_at' => now(),
            ]);
        } else {
            Payment::withoutGlobalScope('store')->create([
                'store_id' => $order->store_id,
                'order_id' => $order->id,
                'transaction_id' => $paymentId,
                'gateway' => $gateway,
                'payment_method' => 'online',
                'amount' => $payload['amount'] ?? $order->total,
                'currency' => $payload['currency'] ?? $order->currency ?? 'USD',
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'metadata' => $payload,
                'processed_at' => now(),
            ]);
        }

        $order->update(['payment_status' => 'failed']);

        Log::warning('Payment failed via webhook', [
            'order_id' => $order->id,
            'gateway' => $gateway,
            'payment_id' => $paymentId,
            'reason' => $failureReason,
        ]);
    }
}
