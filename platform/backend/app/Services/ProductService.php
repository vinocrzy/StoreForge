<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Get paginated products with optional filtering and search
     */
    public function getProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::query()->with(['categories', 'primaryImage', 'variants']);

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by featured
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        //  Filter by stock status
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $query->inStock();
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $query->where('track_inventory', true)->where('stock_quantity', 0);
            } elseif ($filters['stock_status'] === 'low_stock') {
                $query->where('track_inventory', true)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->where('stock_quantity', '>', 0);
            }
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get single product by ID with relationships
     */
    public function getProduct(int $id): Product
    {
        return Product::with(['categories', 'images', 'variants'])->findOrFail($id);
    }

    /**
     * Create a new product
     */
    public function createProduct(array $data): Product
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure unique slug
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        $product = Product::create($data);

        // Attach categories if provided
        if (!empty($data['category_ids'])) {
            $product->categories()->sync($data['category_ids']);
        }

        return $product->load(['categories', 'images']);
    }

    /**
     * Update existing product
     */
    public function updateProduct(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);

        // Update slug if name changed
        if (!empty($data['name']) && $data['name'] !== $product->name) {
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
                $data['slug'] = $this->ensureUniqueSlug($data['slug'], $id);
            }
        }

        $product->update($data);

        // Update categories if provided
        if (isset($data['category_ids'])) {
            $product->categories()->sync($data['category_ids']);
        }

        return $product->load(['categories', 'images']);
    }

    /**
     * Delete product (soft delete)
     */
    public function deleteProduct(int $id): bool
    {
        $product = Product::findOrFail($id);
        return $product->delete();
    }

    /**
     * Update product stock quantity
     */
    public function updateStock(int $id, int $quantity, string $operation = 'set'): Product
    {
        $product = Product::findOrFail($id);

        if ($operation === 'set') {
            $product->stock_quantity = $quantity;
        } elseif ($operation === 'increment') {
            $product->stock_quantity += $quantity;
        } elseif ($operation === 'decrement') {
            $product->stock_quantity = max(0, $product->stock_quantity - $quantity);
        }

        $product->save();

        return $product;
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(): Collection
    {
       return Product::where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->get();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts(): Collection
    {
        return Product::where('track_inventory', true)
            ->where('stock_quantity', 0)
            ->get();
    }

    /**
     * Ensure slug is unique for this store
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $count = 1;

        while (true) {
            $query = Product::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
