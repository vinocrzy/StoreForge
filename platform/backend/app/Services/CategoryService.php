<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Get all categories (optionally as tree structure)
     */
    public function getCategories(bool $onlyActive = false, bool $asTree = false): Collection
    {
        $query = Category::query();

        if ($onlyActive) {
            $query->active();
        }

        if ($asTree) {
            // Get root categories with children
            return $query->roots()->with('children')->get();
        }

        return $query->orderBy('sort_order')->get();
    }

    /**
     * Get single category by ID
     */
    public function getCategory(int $id): Category
    {
        return Category::with(['parent', 'children', 'products'])->findOrFail($id);
    }

    /**
     * Create a new category
     */
    public function createCategory(array $data): Category
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure unique slug
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        return Category::create($data);
    }

    /**
     * Update existing category
     */
    public function updateCategory(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);

        // Check for circular reference (category can't be its own parent)
        if (!empty($data['parent_id']) && $data['parent_id'] == $id) {
            throw new \InvalidArgumentException('Category cannot be its own parent');
        }

        // Check if parent_id is a descendant (would create circular reference)
        if (!empty($data['parent_id']) && $this->isDescendant($id, $data['parent_id'])) {
            throw new \InvalidArgumentException('Cannot set parent to a descendant category');
        }

        // Update slug if name changed
        if (!empty($data['name']) && $data['name'] !== $category->name) {
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
                $data['slug'] = $this->ensureUniqueSlug($data['slug'], $id);
            }
        }

        $category->update($data);

        return $category->load(['parent', 'children']);
    }

    /**
     * Delete category (soft delete)
     */
    public function deleteCategory(int $id, bool $deleteChildren = false): bool
    {
        $category = Category::findOrFail($id);

        if ($deleteChildren) {
            // Delete all children recursively
            $this->deleteChildrenRecursively($category);
        } else {
            // Move children to parent level (or root if no parent)
            Category::where('parent_id', $id)->update(['parent_id' => $category->parent_id]);
        }

        return $category->delete();
    }

    /**
     * Get category tree structure
     */
    public function getCategoryTree(): Collection
    {
        return Category::roots()->with(['children' => function ($query) {
            $query->orderBy('sort_order');
        }])->get();
    }

    /**
     * Reorder categories
     */
    public function reorderCategories(array $categoryOrders): void
    {
        foreach ($categoryOrders as $order) {
            Category::where('id', $order['id'])->update(['sort_order' => $order['sort_order']]);
        }
    }

    /**
     * Move category to different parent
     */
    public function moveCategory(int $categoryId, ?int $newParentId): Category
    {
        $category = Category::findOrFail($categoryId);

        // Check for circular reference
        if ($newParentId && $this->isDescendant($categoryId, $newParentId)) {
            throw new \InvalidArgumentException('Cannot move category to a descendant');
        }

        $category->update(['parent_id' => $newParentId]);

        return $category->load(['parent', 'children']);
    }

    /**
     * Check if a category is a descendant of another
     */
    private function isDescendant(int $ancestorId, int $descendantId): bool
    {
        $category = Category::find($descendantId);

        while ($category && $category->parent_id) {
            if ($category->parent_id == $ancestorId) {
                return true;
            }
            $category = $category->parent;
        }

        return false;
    }

    /**
     * Delete all children recursively
     */
    private function deleteChildrenRecursively(Category $category): void
    {
        foreach ($category->children as $child) {
            $this->deleteChildrenRecursively($child);
            $child->delete();
        }
    }

    /**
     * Ensure slug is unique for this store
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $count = 1;

        while (true) {
            $query = Category::where('slug', $slug);
            
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
