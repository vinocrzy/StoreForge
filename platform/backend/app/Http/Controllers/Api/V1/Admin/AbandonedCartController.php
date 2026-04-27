<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Abandoned Carts (Admin)
 *
 * Endpoints for managing and analysing abandoned cart recovery.
 *
 * @authenticated
 */
class AbandonedCartController extends Controller
{
    /**
     * List abandoned carts
     *
     * Returns paginated abandoned carts with customer info, cart value, and recovery email stats.
     *
     * @queryParam per_page int Items per page (max 50). Example: 20
     * @queryParam recovered bool Filter: true = has placed order after abandonment. Example: false
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "token": "abc123",
     *       "customer_email": "jane@example.com",
     *       "cart_value": 89.99,
     *       "item_count": 2,
     *       "abandoned_at": "2026-04-27T10:00:00Z",
     *       "recovery_email_count": 1,
     *       "recovery_email_sent_at": "2026-04-27T10:15:00Z"
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 20, "total": 42}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 50);
        $storeId = tenant()->id;

        $query = Cart::withoutGlobalScope('store')
            ->where('store_id', $storeId)
            ->whereNotNull('abandoned_at')
            ->with('customer:id,first_name,last_name,email,phone')
            ->orderByDesc('abandoned_at');

        $carts = $query->paginate($perPage);

        $data = $carts->getCollection()->map(function (Cart $cart) {
            $items = $cart->items ?? [];
            $value = collect($items)->sum(fn($i) => $i['total_price'] ?? 0);

            return [
                'id'                     => $cart->id,
                'token'                  => $cart->token,
                'customer'               => $cart->customer ? [
                    'id'    => $cart->customer->id,
                    'name'  => trim(($cart->customer->first_name ?? '') . ' ' . ($cart->customer->last_name ?? '')),
                    'email' => $cart->customer->email,
                    'phone' => $cart->customer->phone,
                ] : null,
                'cart_value'             => round($value, 2),
                'item_count'             => count($items),
                'abandoned_at'           => $cart->abandoned_at,
                'recovery_email_count'   => $cart->recovery_email_count,
                'recovery_email_sent_at' => $cart->recovery_email_sent_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $carts->currentPage(),
                'per_page'     => $carts->perPage(),
                'total'        => $carts->total(),
                'last_page'    => $carts->lastPage(),
            ],
        ]);
    }

    /**
     * Abandoned cart analytics summary
     *
     * Returns aggregate stats: total abandoned, emails sent, carts still abandoned.
     *
     * @response 200 {
     *   "data": {
     *     "total_abandoned": 120,
     *     "emails_sent": 85,
     *     "pending_email": 35,
     *     "max_emails_reached": 62
     *   }
     * }
     */
    public function analytics(): JsonResponse
    {
        $storeId = tenant()->id;

        $total = DB::table('carts')
            ->where('store_id', $storeId)
            ->whereNotNull('abandoned_at')
            ->count();

        $emailsSent = DB::table('carts')
            ->where('store_id', $storeId)
            ->whereNotNull('abandoned_at')
            ->where('recovery_email_count', '>', 0)
            ->count();

        $pending = DB::table('carts')
            ->where('store_id', $storeId)
            ->whereNotNull('abandoned_at')
            ->where('recovery_email_count', 0)
            ->count();

        $maxEmailsReached = DB::table('carts')
            ->where('store_id', $storeId)
            ->whereNotNull('abandoned_at')
            ->where('recovery_email_count', '>=', 2)
            ->count();

        return response()->json([
            'data' => [
                'total_abandoned'    => $total,
                'emails_sent'        => $emailsSent,
                'pending_email'      => $pending,
                'max_emails_reached' => $maxEmailsReached,
            ],
        ]);
    }
}
