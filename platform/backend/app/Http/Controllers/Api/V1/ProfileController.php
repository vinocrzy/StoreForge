<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Profile
 *
 * Manage the authenticated user's profile and credentials.
 *
 * @authenticated
 */
class ProfileController extends Controller
{
    /**
     * Get profile
     *
     * Retrieve the authenticated user's profile information.
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "phone": "+12025551234",
     *     "avatar_url": null,
     *     "status": "active",
     *     "roles": ["admin"]
     *   }
     * }
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'data' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'avatar_url' => $user->avatar_url,
                'status'     => $user->status,
                'roles'      => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Update profile
     *
     * Update the authenticated user's name, email, or phone.
     *
     * @bodyParam name string optional Full name. Example: John Doe
     * @bodyParam email string optional Email address. Example: john@example.com
     * @bodyParam phone string optional Phone number in E.164 format. Example: +12025551234
     *
     * @response 200 {
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "phone": "+12025551234"
     *   }
     * }
     * @response 422 { "message": "The email has already been taken.", "errors": {} }
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:30|unique:users,phone,' . $user->id,
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'avatar_url' => $user->avatar_url,
                'status'     => $user->status,
                'roles'      => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Change password
     *
     * Update the authenticated user's password.
     *
     * @bodyParam current_password string required The current password. Example: oldpassword123
     * @bodyParam password string required The new password (min 8 characters). Example: newpassword456
     * @bodyParam password_confirmation string required Must match the new password. Example: newpassword456
     *
     * @response 200 { "message": "Password changed successfully" }
     * @response 422 { "message": "The current password is incorrect.", "errors": {} }
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Password changed successfully']);
    }
}
