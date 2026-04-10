<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Str;

class CartService
{
    public function createCart(int $storeId, ?int $customerId = null): Cart
    {
        return Cart::withoutGlobalScope('store')->create([
            'store_id'    => $storeId,
            'customer_id' => $customerId,
            'token'       => Str::random(48),
            'items'       => [],
            'expires_at'  => now()->addDays(30),
        ]);
    }

    public function getByToken(string $token): ?Cart
    {
        return Cart::withoutGlobalScope('store')
            ->where('token', $token)
            ->where('store_id', tenant()->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function addItem(Cart $cart, int $productId, int $quantity, ?int $variantId = null): Cart
    {
        $product = Product::with('primaryImage')->findOrFail($productId);

        if ($product->track_inventory && $product->stock_quantity < $quantity) {
            abort(422, "Insufficient stock. Only {$product->stock_quantity} available.");
        }

        $items     = $cart->items ?? [];
        $itemKey   = $this->itemKey($productId, $variantId);
        $existingIndex = $this->findItemIndex($items, $itemKey);

        if ($existingIndex !== null) {
            $newQty = $items[$existingIndex]['quantity'] + $quantity;
            if ($product->track_inventory && $product->stock_quantity < $newQty) {
                abort(422, "Insufficient stock. Only {$product->stock_quantity} available.");
            }
            $items[$existingIndex]['quantity'] = $newQty;
            // Recalculate total_price
            $items[$existingIndex]['total_price'] = (float) $product->price * $newQty;
        } else {
            $items[] = [
                'id'            => $itemKey,
                'product_id'    => $product->id,
                'variant_id'    => $variantId,
                'product_name'  => $product->name,
                'product_slug'  => $product->slug,
                'product_sku'   => $product->sku,
                'product_image' => $product->primaryImage?->file_path,
                'unit_price'    => (float) $product->price,
                'quantity'      => $quantity,
                'total_price'   => (float) $product->price * $quantity,
            ];
        }

        $cart->items = array_values($items);
        $cart->save();

        return $cart;
    }

    public function updateItem(Cart $cart, string $itemId, int $quantity): Cart
    {
        $items = $cart->items ?? [];
        $index = $this->findItemIndex($items, $itemId);

        if ($index === null) {
            abort(404, 'Cart item not found.');
        }

        if ($quantity <= 0) {
            array_splice($items, $index, 1);
        } else {
            $product = Product::find($items[$index]['product_id']);
            if ($product && $product->track_inventory && $product->stock_quantity < $quantity) {
                abort(422, "Insufficient stock. Only {$product->stock_quantity} available.");
            }
            $items[$index]['quantity'] = $quantity;
            // Recalculate total_price for this item
            $unitPrice = $items[$index]['unit_price'] ?? $items[$index]['price'] ?? 0;
            $items[$index]['total_price'] = $unitPrice * $quantity;
        }

        $cart->items = array_values($items);
        $cart->save();

        return $cart;
    }

    public function removeItem(Cart $cart, string $itemId): Cart
    {
        $cart->items = array_values(
            array_filter($cart->items ?? [], fn ($item) => $item['id'] !== $itemId)
        );
        $cart->save();

        return $cart;
    }

    public function clear(Cart $cart): Cart
    {
        $cart->items = [];
        $cart->save();

        return $cart;
    }

    public function calculateTotals(Cart $cart): array
    {
        $items     = $cart->items ?? [];
        $subtotal  = array_sum(array_map(fn ($i) => ($i['unit_price'] ?? $i['price'] ?? 0) * $i['quantity'], $items));
        $itemCount = array_sum(array_column($items, 'quantity'));

        return [
            'item_count' => $itemCount,
            'subtotal'   => round($subtotal, 2),
            'total'      => round($subtotal, 2),
        ];
    }

    private function itemKey(int $productId, ?int $variantId): string
    {
        return $variantId ? "p{$productId}_v{$variantId}" : "p{$productId}";
    }

    private function findItemIndex(array $items, string $itemId): ?int
    {
        foreach ($items as $i => $item) {
            if ($item['id'] === $itemId) {
                return $i;
            }
        }

        return null;
    }
}
