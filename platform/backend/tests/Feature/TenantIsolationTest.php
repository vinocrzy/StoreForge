<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test middleware requires X-Store-ID header
     */
    public function test_middleware_requires_store_id_header(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'X-Store-ID header is required',
            ]);
    }

    /**
     * Test middleware blocks access to stores user doesn't belong to
     */
    public function test_middleware_blocks_unauthorized_store_access(): void
    {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $user = User::factory()->create();
        $user->stores()->attach($store1, ['role' => 'owner']);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Store-ID', $store2->id)
            ->getJson('/api/v1/auth/me');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have access to this store',
            ]);
    }

    /**
     * Test user can access authorized store
     */
    public function test_user_can_access_authorized_store(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();
        $user->stores()->attach($store, ['role' => 'owner']);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Store-ID', $store->id)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'status'],
                'stores',
            ]);
    }
}
