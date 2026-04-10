<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Build product query with common filters.
     */
    private function buildProductsQuery(array $filters = [])
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

        return $query;
    }

    /**
     * Get paginated products with optional filtering and search
     */
    public function getProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->buildProductsQuery($filters);

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get all products for CSV export.
     */
    public function getProductsForExport(array $filters = []): Collection
    {
        $query = $this->buildProductsQuery($filters);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
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
     * Bulk update product status
     *
     * @param array $ids Product IDs
     * @param string $status New status
     * @return int Number of updated products
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return Product::whereIn('id', $ids)->update(['status' => $status]);
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

    /**
     * Upload product images
     */
    public function uploadProductImages(int $productId, array $files, array $options = []): Collection
    {
        $product = Product::findOrFail($productId);
        $uploadedImages = new Collection();

        // Check max images limit
        $existingImagesCount = $product->images()->count();
        if ($existingImagesCount + count($files) > 10) {
            throw new \Exception('Maximum 10 images allowed per product');
        }

        // Determine if we should set the first uploaded image as primary
        $setPrimary = $options['is_primary'] ?? true;
        $hasExistingPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($files as $index => $file) {
            // Store image in products/{product_id}/ directory
            $path = $file->store("products/{$productId}", 'public');
            
            // Create database record
            $image = $product->images()->create([
                'file_path' => $path,
                'alt_text' => $product->name,
                'sort_order' => $existingImagesCount + $index,
                'is_primary' => !$hasExistingPrimary && $setPrimary && $index === 0,
            ]);

            $uploadedImages->push($image);
        }

        return $uploadedImages;
    }

    /**
     * Delete product image
     */
    public function deleteProductImage(int $productId, int $imageId): bool
    {
        $product = Product::findOrFail($productId);
        $image = $product->images()->findOrFail($imageId);

        // Delete file from storage
        \Storage::disk('public')->delete($image->file_path);

        // If this was the primary image, set another image as primary
        if ($image->is_primary) {
            $nextImage = $product->images()->where('id', '!=', $imageId)->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }

        return $image->delete();
    }

    /**
     * Set primary image
     */
    public function setPrimaryImage(int $productId, int $imageId)
    {
        $product = Product::findOrFail($productId);
        $image = $product->images()->findOrFail($imageId);

        // Remove primary flag from all images
        $product->images()->update(['is_primary' => false]);

        // Set new primary image
        $image->update(['is_primary' => true]);

        return $image->fresh();
    }
}
