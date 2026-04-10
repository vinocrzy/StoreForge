<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Customer;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Storefront
 *
 * Guest/customer shopping cart. Cart is identified by a token returned on creation.
 * Pass the token in subsequent requests via `cart_token` or the URL.
 */
class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    /**
     * Create cart
     *
     * Create a new guest cart. Returns a unique token to identify the cart
     * on all subsequent requests.
     *
     * @response 201 scenario="Created" {
     *   "data": {"token": "abc123xyz", "items": [], "item_count": 0, "subtotal": 0, "total": 0}
     * }
     */
    public function create(Request $request): JsonResponse
    {
        $customerId = $request->user() instanceof Customer ? $request->user()->id : null;
        $cart       = $this->cartService->createCart(tenant()->id, $customerId);

        return response()->json(['data' => $this->formatCart($cart)], 201);
    }

    /**
     * Get cart
     *
     * Retrieve cart contents by token.
     *
     * @urlParam token string required Cart token. Example: abc123xyz
     *
     * @response 200 scenario="Success" {
     *   "data": {"token": "abc123xyz", "items": [], "item_count": 0, "subtotal": 0, "total": 0}
     * }
     * @response 404 scenario="Not found" {"message": "Cart not found or expired"}
     */
    public function show(string $token): JsonResponse
    {
        $cart = $this->cartService->getByToken($token);

        if (!$cart) {
            return response()->json(['message' => 'Cart not found or expired'], 404);
        }

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    /**
     * Add item to cart
     *
     * Add a product to the cart. If the product already exists in the cart, quantity is incremented.
     *
     * @urlParam token string required Cart token. Example: abc123xyz
     * @bodyParam product_id integer required Product ID. Example: 1
     * @bodyParam quantity integer required Quantity to add (1–100). Example: 1
     * @bodyParam variant_id integer Optional product variant ID. Example: 3
     *
     * @response 200 scenario="Success" {
     *   "data": {"token": "abc123xyz", "items": [{"id": "p1", "name": "Soap", "quantity": 1}], "item_count": 1, "subtotal": 12.99, "total": 12.99}
     * }
     */
    public function addItem(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:100',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        $cart = $this->cartService->getByToken($token);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found or expired'], 404);
        }

        $cart = $this->cartService->addItem(
            $cart,
            $request->integer('product_id'),
            $request->integer('quantity'),
            $request->integer('variant_id') ?: null
        );

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    /**
     * Update cart item
     *
     * Update the quantity of a specific item in the cart. Set quantity to 0 to remove the item.
     *
     * @urlParam token string required Cart token. Example: abc123xyz
     * @urlParam itemId string required Cart item ID (e.g. p1 for product 1). Example: p1
     * @bodyParam quantity integer required New quantity (0 removes item). Example: 3
     */
    public function updateItem(Request $request, string $token, string $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:100',
        ]);

        $cart = $this->cartService->getByToken($token);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found or expired'], 404);
        }

        $cart = $this->cartService->updateItem($cart, $itemId, $request->integer('quantity'));

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    /**
     * Remove cart item
     *
     * Remove a specific item from the cart by its item ID.
     *
     * @urlParam token string required Cart token. Example: abc123xyz
     * @urlParam itemId string required Cart item ID. Example: p1
     */
    public function removeItem(string $token, string $itemId): JsonResponse
    {
        $cart = $this->cartService->getByToken($token);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found or expired'], 404);
        }

        $cart = $this->cartService->removeItem($cart, $itemId);

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    /**
     * Clear cart
     *
     * Remove all items from the cart.
     *
     * @urlParam token string required Cart token. Example: abc123xyz
     */
    public function clear(string $token): JsonResponse
    {
        $cart = $this->cartService->getByToken($token);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found or expired'], 404);
        }

        $cart = $this->cartService->clear($cart);

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    private function formatCart(Cart $cart): array
    {
        $totals = $this->cartService->calculateTotals($cart);

        return [
            'token'      => $cart->token,
            'items'      => $cart->items ?? [],
            'item_count' => $totals['item_count'],
            'subtotal'   => $totals['subtotal'],
            'total'      => $totals['total'],
            'expires_at' => $cart->expires_at?->toIso8601String(),
        ];
    }
}
