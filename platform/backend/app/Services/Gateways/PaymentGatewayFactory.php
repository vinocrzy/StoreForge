<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\StoreSetting;

class PaymentGatewayFactory
{
    /**
     * Resolve the payment gateway for the current tenant.
     *
     * Reads 'payment_gateway' from store_settings (payments group).
     * Returns null if no gateway is configured (manual payment mode).
     *
     * @param  int|null  $storeId  Override store ID (for webhook contexts without tenant)
     * @return PaymentGatewayInterface|null
     *
     * @throws \RuntimeException If gateway is configured but keys are missing
     */
    public static function make(?int $storeId = null): ?PaymentGatewayInterface
    {
        $gateway = self::getSetting('payment_gateway', $storeId);

        if (!$gateway || $gateway === 'manual') {
            return null;
        }

        return match ($gateway) {
            'stripe' => self::createStripe($storeId),
            'razorpay' => self::createRazorpay($storeId),
            default => throw new \RuntimeException("Unsupported payment gateway: {$gateway}"),
        };
    }

    /**
     * Create the Stripe gateway instance.
     *
     * @param  int|null  $storeId
     * @return StripeGateway
     *
     * @throws \RuntimeException
     */
    private static function createStripe(?int $storeId): StripeGateway
    {
        $secretKey = self::getSetting('stripe_secret_key', $storeId);
        $publishableKey = self::getSetting('stripe_publishable_key', $storeId);
        $webhookSecret = self::getSetting('stripe_webhook_secret', $storeId);

        if (!$secretKey || !$publishableKey) {
            throw new \RuntimeException('Stripe API keys are not configured for this store.');
        }

        if (!$webhookSecret) {
            throw new \RuntimeException('Stripe webhook secret is not configured for this store.');
        }

        return new StripeGateway($secretKey, $publishableKey, $webhookSecret);
    }

    /**
     * Create the Razorpay gateway instance.
     *
     * @param  int|null  $storeId
     * @return RazorpayGateway
     *
     * @throws \RuntimeException
     */
    private static function createRazorpay(?int $storeId): RazorpayGateway
    {
        $keyId = self::getSetting('razorpay_key_id', $storeId);
        $keySecret = self::getSetting('razorpay_key_secret', $storeId);
        $webhookSecret = self::getSetting('razorpay_webhook_secret', $storeId);

        if (!$keyId || !$keySecret) {
            throw new \RuntimeException('Razorpay API keys are not configured for this store.');
        }

        if (!$webhookSecret) {
            throw new \RuntimeException('Razorpay webhook secret is not configured for this store.');
        }

        return new RazorpayGateway($keyId, $keySecret, $webhookSecret);
    }

    /**
     * Get a setting from the payments group for a store.
     *
     * @param  string    $key
     * @param  int|null  $storeId  If null, uses the current tenant context
     * @return string|null
     */
    private static function getSetting(string $key, ?int $storeId = null): ?string
    {
        $query = StoreSetting::withoutGlobalScope('store')
            ->where('group', 'payments')
            ->where('key', $key);

        if ($storeId) {
            $query->where('store_id', $storeId);
        } elseif (tenant()) {
            $query->where('store_id', tenant()->id);
        } else {
            return null;
        }

        return $query->value('value');
    }

    /**
     * Resolve a gateway by name for a specific store (for webhook handling).
     *
     * @param  string  $gatewayName  'stripe' or 'razorpay'
     * @param  int     $storeId
     * @return PaymentGatewayInterface
     *
     * @throws \RuntimeException
     */
    public static function makeForStore(string $gatewayName, int $storeId): PaymentGatewayInterface
    {
        return match ($gatewayName) {
            'stripe' => self::createStripe($storeId),
            'razorpay' => self::createRazorpay($storeId),
            default => throw new \RuntimeException("Unsupported payment gateway: {$gatewayName}"),
        };
    }
}
