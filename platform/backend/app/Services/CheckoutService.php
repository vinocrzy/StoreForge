<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(private CartService $cartService) {}

    public function processCheckout(Cart $cart, array $data, ?Customer $customer = null): Order
    {
        $items = $cart->items ?? [];

        if (empty($items)) {
            abort(422, 'Cart is empty.');
        }

        return DB::transaction(function () use ($cart, $data, $customer, $items) {
            // Verify stock and availability for all items before creating the order
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || $product->status !== 'active') {
                    abort(422, "Product '{$item['product_name']}' is no longer available.");
                }
                if ($product->track_inventory && $product->stock_quantity < $item['quantity']) {
                    abort(422, "Insufficient stock for '{$item['product_name']}'.");
                }
            }

            // For guest checkout, create or find a guest customer record
            if (!$customer) {
                $customer = $this->resolveGuestCustomer($data);
            }

            $totals = $this->cartService->calculateTotals($cart);

            $order = Order::create([
                'store_id'        => tenant()->id,
                'customer_id'     => $customer->id,
                'order_number'    => $this->generateOrderNumber(),
                'status'          => 'pending',
                'payment_status'  => 'pending',
                'currency'        => 'USD',
                'subtotal'        => $totals['subtotal'],
                'discount_amount' => 0,
                'shipping_amount' => 0,
                'tax_amount'      => 0,
                'total'           => $totals['total'],
                'customer_note'   => $data['note'] ?? null,
                'payment_method'  => $data['payment_method'],
                'ip_address'      => request()->ip(),
                'user_agent'      => request()->userAgent(),
                'placed_at'       => now(),
            ]);

            // Create order items and decrement stock
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'         => $order->id,
                    'product_id'       => $item['product_id'],
                    'variant_id'       => $item['variant_id'] ?? null,
                    'quantity'         => $item['quantity'],
                    'price'            => $item['unit_price'],
                    'discount_amount'  => 0,
                    'tax_amount'       => 0,
                    'total'            => $item['total_price'],
                    'product_snapshot' => [
                        'name'  => $item['product_name'],
                        'sku'   => $item['product_sku'],
                        'image' => $item['product_image'],
                    ],
                ]);

                // Decrement stock if tracked
                $product = Product::find($item['product_id']);
                if ($product && $product->track_inventory) {
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }

            // Clear cart after successful checkout
            $this->cartService->clear($cart);

            return $order->load('items');
        });
    }

    private function resolveGuestCustomer(array $data): Customer
    {
        $storeId = tenant()->id;

        // Reuse existing customer if phone or email matches (phone is primary identifier)
        $existing = Customer::withoutGlobalScope('store')
            ->where('store_id', $storeId)
            ->where(function ($query) use ($data) {
                $query->where('phone', $data['phone'])
                      ->orWhere('email', $data['email'] ?? null);
            })
            ->first();

        if ($existing) {
            // Update customer details if changed
            $existing->update([
                'first_name' => $data['first_name'] ?? $existing->first_name,
                'last_name'  => $data['last_name'] ?? $existing->last_name,
                'email'      => $data['email'] ?? $existing->email,
            ]);
            return $existing;
        }

        return Customer::create([
            'store_id'   => $storeId,
            'first_name' => $data['first_name'] ?? 'Guest',
            'last_name'  => $data['last_name'] ?? '',
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'password'   => Hash::make(Str::random(16)),
            'status'     => 'active',
        ]);
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(Str::random(8));
    }
}
