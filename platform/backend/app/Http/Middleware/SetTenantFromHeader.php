<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $storeId = $request->header('X-Store-ID');

        if (!$storeId) {
            return response()->json([
                'message' => 'X-Store-ID header is required',
            ], 400);
        }

        $store = Store::find($storeId);

        if (!$store) {
            return response()->json([
                'message' => 'Store not found',
            ], 404);
        }

        if (!$store->isActive()) {
            return response()->json([
                'message' => 'Store is not active',
            ], 403);
        }

        $user = $request->user();

        if ($user && !$user->hasAccessToStore($storeId)) {
            return response()->json([
                'message' => 'You do not have access to this store',
            ], 403);
        }

        app()->instance('tenant', $store);

        return $next($request);
    }
}
