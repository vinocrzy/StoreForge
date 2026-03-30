<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 * 
 * APIs for user authentication and token management
 */
class AuthController extends Controller
{
    /**
     * Login
     * 
     * Authenticate user and generate API token.
     * 
     * @bodyParam email string required User email address. Example: admin@store.com
     * @bodyParam password string required User password. Example: password123
     * @bodyParam device_name string Device name for token. Example: web-browser
     * 
     * @response 200 scenario="Success" {
     *   "user": {
     *     "id": 1,
     *     "name": "Admin User",
     *     "email": "admin@store.com",
     *     "status": "active"
     *   },
     *   "token": "1|abc123...",
     *   "stores": [
     *     {
     *       "id": 1,
     *       "name": "My Store",
     *       "role": "owner"
     *     }
     *   ]
     * }
     * 
     * @response 422 scenario="Invalid credentials" {
     *   "message": "The provided credentials are incorrect."
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->isActive()) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        $deviceName = $request->device_name ?? $request->userAgent() ?? 'unknown-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        $stores = $user->stores()->get()->map(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'role' => $store->pivot->role,
            ];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ],
            'token' => $token,
            'stores' => $stores,
        ]);
    }

    /**
     * Logout
     * 
     * Revoke current API token.
     * 
     * @authenticated
     * 
     * @response 200 scenario="Success" {
     *   "message": "Logged out successfully"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     * 
     * Get current user information with stores.
     * 
     * @authenticated
     * 
     * @response 200 scenario="Success" {
     *   "user": {
     *     "id": 1,
     *     "name": "Admin User",
     *     "email": "admin@store.com",
     *     "status": "active"
     *   },
     *   "stores": [
     *     {
     *       "id": 1,
     *       "name": "My Store",
     *       "role": "owner"
     *     }
     *   ]
     * }
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $stores = $user->stores()->get()->map(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'role' => $store->pivot->role,
            ];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'status' => $user->status,
            ],
            'stores' => $stores,
        ]);
    }

    /**
     * Revoke all tokens
     * 
     * Revoke all API tokens for current user (logout from all devices).
     * 
     * @authenticated
     * 
     * @response 200 scenario="Success" {
     *   "message": "All tokens revoked successfully"
     * }
     */
    public function revokeAllTokens(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'All tokens revoked successfully',
        ]);
    }
}
