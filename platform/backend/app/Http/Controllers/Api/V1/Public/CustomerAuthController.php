<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * @group Public Storefront
 *
 * Customer registration and authentication for storefront accounts.
 * These are separate from admin user accounts.
 */
class CustomerAuthController extends Controller
{
    /**
     * Register customer
     *
     * Create a new customer account for this store. Returns an auth token on success.
     *
     * @bodyParam first_name string required First name. Example: Jane
     * @bodyParam last_name string required Last name. Example: Doe
     * @bodyParam phone string required Phone number in E.164 format (primary identifier, unique per store). Example: +12025551234
     * @bodyParam email string optional Email address (optional, unique per store if provided). Example: jane@example.com
     * @bodyParam password string required Password (min 8 characters). Example: secret12345
     *
     * @response 201 scenario="Created" {
     *   "data": {"id": 1, "first_name": "Jane", "last_name": "Doe", "email": "jane@example.com"},
     *   "token": "1|abc123xyz"
     * }
     * @response 422 scenario="Validation error" {"message": "The email has already been taken.", "errors": {}}
     */
    public function register(Request $request): JsonResponse
    {
        $storeId = tenant()->id;

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => "nullable|email|max:255|unique:customers,email,NULL,id,store_id,{$storeId}",
            'phone'      => "required|string|max:20|unique:customers,phone,NULL,id,store_id,{$storeId}",
            'password'   => ['required', Password::min(8)],
        ]);

        $customer = Customer::create([
            'store_id'   => $storeId,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email ?? null,
            'phone'      => $request->phone,
            'password'   => Hash::make($request->password),
            'status'     => 'active',
        ]);

        $token = $customer->createToken('storefront')->plainTextToken;

        return response()->json([
            'data'  => $this->formatCustomer($customer),
            'token' => $token,
        ], 201);
    }

    /**
     * Customer login
     *
     * Authenticate a customer using email or phone number and password.
     * Phone is the primary authentication method (E.164 format: +12025551234).
     *
     * @bodyParam login string required Email address or phone number. Example: jane@example.com
     * @bodyParam password string required Password. Example: secret12345
     *
     * @response 200 scenario="Success" {
     *   "data": {"id": 1, "first_name": "Jane", "email": "jane@example.com"},
     *   "token": "1|abc123xyz"
     * }
     * @response 401 scenario="Invalid credentials" {"message": "Invalid credentials."}
     * @response 403 scenario="Inactive account" {"message": "Account is inactive."}
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = preg_match('/^[\d\s\-\+\(\)]+$/', $request->login) ? 'phone' : 'email';

        $customer = Customer::withoutGlobalScope('store')
            ->where($loginField, $request->login)
            ->where('store_id', tenant()->id)
            ->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($customer->status !== 'active') {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        $customer->update(['last_login_at' => now()]);

        $token = $customer->createToken('storefront')->plainTextToken;

        return response()->json([
            'data'  => $this->formatCustomer($customer),
            'token' => $token,
        ]);
    }

    /**
     * Customer logout
     *
     * Revoke the current customer access token.
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {"message": "Logged out successfully."}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    private function formatCustomer(Customer $customer): array
    {
        return [
            'id'         => $customer->id,
            'first_name' => $customer->first_name,
            'last_name'  => $customer->last_name,
            'email'      => $customer->email,
            'phone'      => $customer->phone,
            'status'     => $customer->status,
        ];
    }
}
