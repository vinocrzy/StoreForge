<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        // Define category hierarchy (parent => children)
        $categoryStructure = [
            'Electronics' => ['Laptops', 'Smartphones', 'Cameras', 'Audio', 'Electronics Accessories'],
            'Clothing' => ['Men', 'Women', 'Kids', 'Shoes', 'Fashion Accessories'],
            'Home & Garden' => ['Furniture', 'Decor', 'Kitchen', 'Outdoor', 'Storage'],
            'Sports' => ['Fitness', 'Outdoor Sports', 'Team Sports', 'Athletic Wear'],
            'Books' => ['Fiction', 'Non-Fiction', 'Children Books', 'Educational'],
        ];

        foreach ($stores as $store) {
            $sortOrder = 0;

            foreach ($categoryStructure as $parentName => $children) {
                // Create parent category
                $parent = Category::create([
                    'store_id' => $store->id,
                    'parent_id' => null,
                    'name' => $parentName,
                    'slug' => Str::slug($parentName),
                    'description' => "Browse our collection of {$parentName}",
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]);

                // Create child categories
                $childSortOrder = 0;
                foreach ($children as $childName) {
                    Category::create([
                        'store_id' => $store->id,
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'slug' => Str::slug($childName),
                        'description' => "Shop {$childName} in {$parentName}",
                        'sort_order' => $childSortOrder++,
                        'is_active' => true,
                    ]);
                }
            }

            $categoryCount = Category::where('store_id', $store->id)->count();
            echo "[OK] Created {$categoryCount} categories for {$store->name}\n";
        }
    }
}
