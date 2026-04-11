<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limit to the demo store only — client stores (Honey Bee, Storefront Template)
        // have their own dedicated catalog seeders and must not be polluted with generic data.
        $demoStoreSlug = env('DEMO_STORE_SLUG', 'demo-store');
        $stores = Store::where('slug', $demoStoreSlug)->get();

        if ($stores->isEmpty()) {
            $this->command->warn('[ProductSeeder] Demo store not found. Skipping.');
            return;
        }

        foreach ($stores as $store) {
            // Get all categories for this store
            $categories = Category::where('store_id', $store->id)->get();

            if ($categories->isEmpty()) {
                echo "[WARN] No categories found for {$store->name}. Skipping products.\n";
                continue;
            }

            // Create 30 products per store
            for ($i = 1; $i <= 30; $i++) {
                $name = $this->generateProductName();
                $price = fake()->randomFloat(2, 10, 500);

                $product = Product::create([
                    'store_id' => $store->id,
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
                    'sku' => strtoupper(fake()->unique()->bothify('??##??##')),
                    'description' => fake()->paragraphs(3, true),
                    'short_description' => fake()->sentence(),
                    'price' => $price,
                    'compare_price' => fake()->boolean(30) ? $price + fake()->randomFloat(2, 10, 100) : null,
                    'cost_price' => $price * 0.6,
                    'track_inventory' => true,
                    'stock_quantity' => fake()->numberBetween(0, 100),
                    'low_stock_threshold' => 5,
                    'weight' => fake()->randomFloat(2, 0.1, 10),
                    'weight_unit' => 'kg',
                    'dimensions' => [
                        'length' => fake()->numberBetween(10, 50),
                        'width' => fake()->numberBetween(10, 50),
                        'height' => fake()->numberBetween(10, 50),
                        'unit' => 'cm',
                    ],
                    'status' => fake()->randomElement(['active', 'active', 'active', 'draft']), // 75% active
                    'is_featured' => fake()->boolean(15), // 15% featured
                    'meta_title' => $name,
                    'meta_description' => fake()->sentence(),
                ]);

                // Attach to 1-3 random categories
                $randomCategories = $categories->random(fake()->numberBetween(1, min(3, $categories->count())));
                $product->categories()->attach($randomCategories->pluck('id'));

                // Create 1-4 product images
                $imageCount = fake()->numberBetween(1, 4);
                for ($j = 0; $j < $imageCount; $j++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                        'file_path' => "products/" . fake()->uuid() . '.jpg',
                        'alt_text' => $product->name,
                        'sort_order' => $j,
                        'is_primary' => $j === 0, // First image is primary
                    ]);
                }

                // 40% chance of having variants (e.g., different sizes/colors)
                if (fake()->boolean(40)) {
                    $variantCount = fake()->numberBetween(2, 5);
                    $colors = ['Red', 'Blue', 'Green', 'Black', 'White'];
                    $sizes = ['Small', 'Medium', 'Large', 'XL'];

                    for ($v = 0; $v < $variantCount; $v++) {
                        $color = $colors[array_rand($colors)];
                        $size = $sizes[array_rand($sizes)];

                        ProductVariant::create([
                            'product_id' => $product->id,
                            'store_id' => $store->id,
                            'name' => "$color / $size",
                            'sku' => $product->sku . '-' . strtoupper($color[0] . $size[0]),
                            'price' => null, // Use product price
                            'compare_price' => null,
                            'stock_quantity' => fake()->numberBetween(0, 30),
                            'attributes' => [
                                'color' => $color,
                                'size' => $size,
                            ],
                            'image' => null,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            $productCount = Product::where('store_id', $store->id)->count();
            echo "[OK] Created {$productCount} products for {$store->name}\n";
        }
    }

    /**
     * Generate realistic product name
     */
    private function generateProductName(): string
    {
        $adjectives = ['Premium', 'Professional', 'Modern', 'Classic', 'Deluxe', 'Ultimate', 'Advanced', 'Smart'];
        $products = [
            'Laptop', 'Smartphone', 'Camera', 'Headphones', 'Watch', 'Tablet',
            'T-Shirt', 'Jeans', 'Jacket', 'Sneakers', 'Backpack',
            'Coffee Maker', 'Blender', 'Vacuum', 'Chair', 'Desk', 'Lamp',
            'Yoga Mat', 'Dumbbell', 'Bicycle', 'Tennis Racket',
        ];
        $descriptors = ['Pro', 'Plus', 'Max', 'Elite', 'Series X', '2024'];

        $adj = $adjectives[array_rand($adjectives)];
        $prod = $products[array_rand($products)];
        $desc = fake()->boolean(60) ? ' ' . $descriptors[array_rand($descriptors)] : '';

        return $adj . ' ' . $prod . $desc;
    }
}
