<?php

namespace Tests\Feature\Api;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can login with email
     */
    public function test_user_can_login_with_email(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->stores()->attach($store, ['role' => 'owner']);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'phone'],
                'stores',
            ]);
    }

    /**
     * Test user can login with phone number
     */
    public function test_user_can_login_with_phone(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'phone' => '+12025551234',
            'password' => Hash::make('password123'),
        ]);
        $user->stores()->attach($store, ['role' => 'owner']);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '+12025551234',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'phone'],
                'stores',
            ]);
    }

    /**
     * Test login fails with wrong password
     */
    public function test_login_fails_with_wrong_password(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->stores()->attach($store, ['role' => 'owner']);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    /**
     * Test login fails with non-existent user
     */
    public function test_login_fails_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    /**
     * Test login validation requires login field
     */
    public function test_login_validation_requires_login_field(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    }

    /**
     * Test login validation requires password field
     */
    public function test_login_validation_requires_password_field(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test authenticated user can access their profile
     */
    public function test_authenticated_user_can_access_profile(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();
        $user->stores()->attach($store, ['role' => 'owner']);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Store-ID', $store->id)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ])
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'phone', 'status'],
                'stores',
            ]);
    }

    /**
     * Test user can logout
     */
    public function test_user_can_logout(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();
        $user->stores()->attach($store, ['role' => 'owner']);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Store-ID', $store->id)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        // Verify token is deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * Test unauthenticated request returns 401
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    /**
     * Test inactive user cannot login
     */
    public function test_inactive_user_cannot_login(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
        ]);
        $user->stores()->attach($store, ['role' => 'owner']);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Your account is inactive. Please contact support.',
            ]);
    }
}
