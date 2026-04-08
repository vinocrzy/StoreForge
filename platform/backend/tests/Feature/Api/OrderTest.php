<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store1 = Store::factory()->create();
        $this->store2 = Store::factory()->create();

        $this->user1 = User::factory()->create();
        $this->user1->stores()->attach($this->store1, ['role' => 'owner']);

        $this->user2 = User::factory()->create();
        $this->user2->stores()->attach($this->store2, ['role' => 'owner']);
    }

    /**
     * Test user can list orders from their store only
     */
    public function test_user_can_list_orders_from_their_store_only(): void
    {
        $customer1 = Customer::factory()->create(['store_id' => $this->store1->id]);
        $customer2 = Customer::factory()->create(['store_id' => $this->store2->id]);

        $order1 = Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer1->id,
            'order_number' => 'ORD-001',
        ]);
        $order2 = Order::factory()->create([
            'store_id' => $this->store2->id,
            'customer_id' => $customer2->id,
            'order_number' => 'ORD-002',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/admin/orders');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $order1->id)
            ->assertJsonMissing(['order_number' => 'ORD-002']);
    }

    /**
     * Test user can view order details from their store
     */
    public function test_user_can_view_order_details(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store1->id]);
        $order = Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $order->id,
                    'store_id' => $this->store1->id,
                ],
            ]);
    }

    /**
     * Test user cannot view order from another store
     */
    public function test_user_cannot_view_order_from_another_store(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store2->id]);
        $order = Order::factory()->create([
            'store_id' => $this->store2->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertNotFound();
    }

    /**
     * Test user can update order status
     */
    public function test_user_can_update_order_status(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store1->id]);
        $order = Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'order_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->postJson("/api/v1/admin/orders/{$order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $order->id,
                    'order_status' => 'confirmed',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'confirmed',
        ]);
    }

    /**
     * Test user cannot update order status from another store
     */
    public function test_user_cannot_update_order_status_from_another_store(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store2->id]);
        $order = Order::factory()->create([
            'store_id' => $this->store2->id,
            'customer_id' => $customer->id,
            'order_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->postJson("/api/v1/admin/orders/{$order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertNotFound();

        // Verify status was not updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'pending',
        ]);
    }

    /**
     * Test user can mark order as paid
     */
    public function test_user_can_mark_order_as_paid(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store1->id]);
        $order = Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->postJson("/api/v1/admin/orders/{$order->id}/mark-as-paid", [
                'payment_method' => 'cash',
                'payment_notes' => 'Paid in cash at store',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $order->id,
                    'payment_status' => 'paid',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Test order filtering by status
     */
    public function test_order_filtering_by_status(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store1->id]);
        
        Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'order_status' => 'pending',
        ]);
        Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'order_status' => 'confirmed',
        ]);
        Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'order_status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/admin/orders?status=confirmed');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test order filtering by payment status
     */
    public function test_order_filtering_by_payment_status(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store1->id]);
        
        Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);
        Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/admin/orders?payment_status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.payment_status', 'pending');
    }

    /**
     * Test order search by order number
     */
    public function test_order_search_by_order_number(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store1->id]);
        
        Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-2026-001',
        ]);
        Order::factory()->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-2026-002',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/admin/orders?search=ORD-2026-001');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-2026-001');
    }

    /**
     * Test order statistics are scoped to store
     */
    public function test_order_statistics_are_scoped_to_store(): void
    {
        $customer1 = Customer::factory()->create(['store_id' => $this->store1->id]);
        $customer2 = Customer::factory()->create(['store_id' => $this->store2->id]);

        // Store 1 orders
        Order::factory()->count(5)->create([
            'store_id' => $this->store1->id,
            'customer_id' => $customer1->id,
            'total_amount' => 100,
        ]);

        // Store 2 orders
        Order::factory()->count(10)->create([
            'store_id' => $this->store2->id,
            'customer_id' => $customer2->id,
            'total_amount' => 200,
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/admin/orders/statistics');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'total_orders' => 5,
                    'total_revenue' => 500, // 5 * 100
                ],
            ]);
    }

    /**
     * Test order validation requires customer_id
     */
    public function test_order_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->postJson('/api/v1/admin/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'items']);
    }
}
