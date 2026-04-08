<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two stores with users for tenant isolation testing
        $this->store1 = Store::factory()->create();
        $this->store2 = Store::factory()->create();

        $this->user1 = User::factory()->create();
        $this->user1->stores()->attach($this->store1, ['role' => 'owner']);

        $this->user2 = User::factory()->create();
        $this->user2->stores()->attach($this->store2, ['role' => 'owner']);
    }

    /**
     * Test user can list products from their store only
     */
    public function test_user_can_list_products_from_their_store_only(): void
    {
        // Create products for both stores
        $product1 = Product::factory()->create(['store_id' => $this->store1->id, 'name' => 'Store 1 Product']);
        $product2 = Product::factory()->create(['store_id' => $this->store2->id, 'name' => 'Store 2 Product']);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product1->id)
            ->assertJsonPath('data.0.name', 'Store 1 Product')
            ->assertJsonMissing(['name' => 'Store 2 Product']);
    }

    /**
     * Test user can create product in their store
     */
    public function test_user_can_create_product(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store1->id]);

        $productData = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'Test product description',
            'price' => 99.99,
            'sku' => 'TEST-SKU-001',
            'category_id' => $category->id,
            'status' => 'active',
            'stock_quantity' => 100,
        ];

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->postJson('/api/v1/products', $productData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'New Product',
                    'slug' => 'new-product',
                    'price' => '99.99',
                    'store_id' => $this->store1->id,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'store_id' => $this->store1->id,
        ]);
    }

    /**
     * Test product creation validates required fields
     */
    public function test_product_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'price', 'category_id']);
    }

    /**
     * Test product SKU must be unique within store
     */
    public function test_product_sku_must_be_unique_within_store(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store1->id]);
        
        Product::factory()->create([
            'store_id' => $this->store1->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->postJson('/api/v1/products', [
                'name' => 'Test Product',
                'slug' => 'test-product',
                'price' => 49.99,
                'sku' => 'DUPLICATE-SKU',
                'category_id' => $category->id,
                'status' => 'active',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /**
     * Test user can view product details from their store
     */
    public function test_user_can_view_product_details(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store1->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'store_id' => $this->store1->id,
                ],
            ]);
    }

    /**
     * Test user cannot view product from another store
     */
    public function test_user_cannot_view_product_from_another_store(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertNotFound();
    }

    /**
     * Test user can update product in their store
     */
    public function test_user_can_update_product(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store1->id,
            'name' => 'Original Name',
            'price' => 49.99,
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->putJson("/api/v1/admin/products/{$product->id}", [
                'name' => 'Updated Name',
                'slug' => $product->slug,
                'price' => 59.99,
                'category_id' => $product->category_id,
                'status' => 'active',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => 'Updated Name',
                    'price' => '59.99',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'price' => 59.99,
        ]);
    }

    /**
     * Test user cannot update product from another store
     */
    public function test_user_cannot_update_product_from_another_store(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store2->id,
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->putJson("/api/v1/admin/products/{$product->id}", [
                'name' => 'Updated Name',
                'slug' => $product->slug,
                'price' => 59.99,
                'category_id' => $product->category_id,
                'status' => 'active',
            ]);

        $response->assertNotFound();

        // Verify product was not updated
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Test user can delete product from their store
     */
    public function test_user_can_delete_product(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store1->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertOk();

        // Verify soft delete
        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    /**
     * Test user cannot delete product from another store
     */
    public function test_user_cannot_delete_product_from_another_store(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertNotFound();

        // Verify product still exists
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test product search only returns results from current store
     */
    public function test_product_search_only_returns_results_from_current_store(): void
    {
        Product::factory()->create([
            'store_id' => $this->store1->id,
            'name' => 'Laptop Pro',
        ]);
        Product::factory()->create([
            'store_id' => $this->store2->id,
            'name' => 'Laptop Basic',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/products?search=Laptop');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop Pro');
    }

    /**
     * Test product filtering by status
     */
    public function test_product_filtering_by_status(): void
    {
        Product::factory()->create(['store_id' => $this->store1->id, 'status' => 'active']);
        Product::factory()->create(['store_id' => $this->store1->id, 'status' => 'active']);
        Product::factory()->create(['store_id' => $this->store1->id, 'status' => 'draft']);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/products?status=active');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test product pagination
     */
    public function test_product_pagination(): void
    {
        Product::factory()->count(25)->create(['store_id' => $this->store1->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->withHeader('X-Store-ID', $this->store1->id)
            ->getJson('/api/v1/products?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links' => ['first', 'last', 'next', 'prev'],
            ]);
    }
}
