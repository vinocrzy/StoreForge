<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Store;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets tenant context from X-Store-ID header for public (unauthenticated) storefront routes.
 * Does not check admin user access — suitable for customer-facing APIs.
 */
class SetPublicTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $storeId = $request->header('X-Store-ID');

        if (!$storeId) {
            return response()->json(['message' => 'X-Store-ID header is required'], 400);
        }

        $store = Store::find($storeId);

        if (!$store) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        if (!$store->isActive()) {
            return response()->json(['message' => 'Store is not active'], 403);
        }

        app()->instance('tenant', $store);

        return $next($request);
    }
}
