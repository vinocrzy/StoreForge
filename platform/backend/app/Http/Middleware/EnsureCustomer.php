<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Customer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated entity is a Customer (not an admin User).
 * Must be used after auth:sanctum middleware.
 */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user instanceof Customer)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
