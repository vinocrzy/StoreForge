<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
     * Phone-first authentication: accepts phone (+12025551234) or email.
     * 
     * @bodyParam login string required User phone or email. Example: +12025551234
     * @bodyParam password string required User password. Example: password123
     * @bodyParam device_name string Device name for token. Example: web-browser
     * 
     * @response 200 scenario="Success" {
     *   "user": {
     *     "id": 1,
     *     "name": "Admin User",
     *     "email": "admin@store.com",
     *     "phone": "+12025551234",
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
            'login' => 'required|string',
            'password' => 'required',
            'device_name' => 'string|max:255',
        ]);

        // Determine if login is phone or email
        $loginField = preg_match('/^[\d\s\-\+\(\)]+$/', $request->login) ? 'phone' : 'email';
        $user = User::where($loginField, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
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
                'domain' => $store->domain,
                'status' => $store->status,
                'currency' => $store->currency ?? 'USD',
                'role' => $store->pivot->role,
                'created_at' => $store->created_at->toISOString(),
                'updated_at' => $store->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'roles' => $user->getRoleNames()->values(),
                'is_super_admin' => $user->hasRole('super-admin'),
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
                'roles' => $user->getRoleNames()->values(),
                'is_super_admin' => $user->hasRole('super-admin'),
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

    /**
     * Forgot Password
     * 
     * Send password reset link to user's email or phone.
     * 
     * @bodyParam login string required User phone number or email. Example: +12025551234
     * 
     * @response 200 scenario="Success" {
     *   "message": "Password reset link sent to your email/phone"
     * }
     * 
     * @response 404 scenario="User not found" {
     *   "message": "We couldn't find a user with that phone number or email"
     * }
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        // Detect if login is phone or email
        $loginField = preg_match('/^[\d\s\-\+\(\)]+$/', $request->login) ? 'phone' : 'email';
        
        $user = User::where($loginField, $request->login)->first();

        if (!$user) {
            return response()->json([
                'message' => 'We couldn\'t find a user with that phone number or email',
            ], 404);
        }

        // Generate password reset token
        $token = Password::broker()->createToken($user);

        // TODO: Send email/SMS with reset link containing the token
        // For now, we'll just return the token (REMOVE IN PRODUCTION!)
        
        return response()->json([
            'message' => 'Password reset link sent to your ' . ($loginField === 'phone' ? 'phone' : 'email'),
            // TEMPORARY: Remove this in production - only for testing
            'reset_token' => $token,
            'user' => [
                'phone' => $user->phone,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Reset Password
     * 
     * Reset user password using reset token.
     * 
     * @bodyParam token string required Password reset token. Example: abc123defg456
     * @bodyParam login string required User phone number or email. Example: +12025551234
     * @bodyParam password string required New password (min 8 characters). Example: newpassword123
     * @bodyParam password_confirmation string required Confirm new password. Example: newpassword123
     * 
     * @response 200 scenario="Success" {
     *   "message": "Password reset successfully"
     * }
     * 
     * @response 422 scenario="Invalid token" {
     *   "message": "Invalid or expired reset token"
     * }
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        // Detect if login is phone or email
        $loginField = preg_match('/^[\d\s\-\+\(\)]+$/', $request->login) ? 'phone' : 'email';
        
        $user = User::where($loginField, $request->login)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Verify reset token
        if (!Password::broker()->tokenExists($user, $request->token)) {
            return response()->json([
                'message' => 'Invalid or expired reset token',
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Delete the used token
        Password::broker()->deleteToken($user);

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully. Please login with your new password.',
        ]);
    }
}
