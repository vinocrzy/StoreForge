<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Categories
 * 
 * Manage product categories for the authenticated store. Supports hierarchical categories with parent-child relationships.
 * 
 * @authenticated
 */
class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * List categories
     * 
     * Get all categories. Can return as a flat list or hierarchical tree structure.
     * 
     * @queryParam only_active boolean Show only active categories. Example: 1
     * @queryParam as_tree boolean Return as hierarchical tree. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Electronics",
     *       "slug": "electronics",
     *       "parent_id": null,
     *       "is_active": true,
     *       "children": []
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active');
        $asTree = $request->boolean('as_tree');

        $categories = $this->categoryService->getCategories($onlyActive, $asTree);

        return response()->json(['data' => $categories]);
    }

    /**
     * Get category details
     * 
     * Retrieve a single category with parent, children, and products.
     * 
     * @urlParam id integer required Category ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Electronics",
     *     "slug": "electronics",
     *     "description": "Electronic products",
     *     "parent": null,
     *     "children": [],
     *     "products": []
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Category not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategory($id);

        return response()->json(['data' => $category]);
    }

    /**
     * Create category
     * 
     * Create a new category. Can be a root category or child of another category.
     * 
     * @bodyParam name string required Category name. Example: Electronics
     * @bodyParam slug string Category slug (auto-generated if not provided). Example: electronics
     * @bodyParam description string Category description.
     * @bodyParam image string Image URL.
     * @bodyParam parent_id integer Parent category ID (for subcategories). Example: 1
     * @bodyParam sort_order integer Sort order. Example: 0
     * @bodyParam is_active boolean Active status. Example: true
     * 
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "name": "Electronics",
     *     "slug": "electronics",
     *     "parent_id": null,
     *     "is_active": true
     *   }
     * }
     * 
     * @response 422 scenario="Validation failed" {
     *   "message": "The given data was invalid",
     *   "errors": {
     *     "name": ["The name field is required"]
     *   }
     * }
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return response()->json(['data' => $category], 201);
    }

    /**
     * Update category
     * 
     * Update an existing category. Can change parent relationship.
     * 
     * @urlParam id integer required Category ID. Example: 1
     * @bodyParam name string Category name. Example: Updated Electronics
     * @bodyParam parent_id integer Parent category ID. Example: 2
     * @bodyParam is_active boolean Active status. Example: false
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 1,
     *     "name": "Updated Electronics",
     *     "parent_id": 2
     *   }
     * }
     * 
     * @response 400 scenario="Circular reference" {
     *   "message": "Category cannot be its own parent"
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Category not found"
     * }
     */
    public function update(CategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());
            return response()->json(['data' => $category]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete category
     * 
     * Soft delete a category. Children can be deleted or moved to parent level.
     * 
     * @urlParam id integer required Category ID. Example: 1
     * @queryParam delete_children boolean Delete all child categories. Example: 0
     * 
     * @response 204 scenario="Deleted"
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Category not found"
     * }
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleteChildren = $request->boolean('delete_children');
        
        $this->categoryService->deleteCategory($id, $deleteChildren);

        return response()->json(null, 204);
    }

    /**
     * Get category tree
     * 
     * Get complete category hierarchy as a tree structure.
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Electronics",
     *       "children": [
     *         {
     *           "id": 2,
     *           "name": "Laptops",
     *           "children": []
     *         }
     *       ]
     *     }
     *   ]
     * }
     */
    public function tree(): JsonResponse
    {
        $tree = $this->categoryService->getCategoryTree();

        return response()->json(['data' => $tree]);
    }

    /**
     * Reorder categories
     * 
     * Update sort order for multiple categories at once.
     * 
     * @bodyParam categories array required Array of category orders. Example: [{"id": 1, "sort_order": 0}, {"id": 2, "sort_order": 1}]
     * @bodyParam categories.*.id integer required Category ID. Example: 1
     * @bodyParam categories.*.sort_order integer required New sort order. Example: 0
     * 
     * @response 200 scenario="Updated" {
     *   "message": "Categories reordered successfully"
     * }
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        $this->categoryService->reorderCategories($request->input('categories'));

        return response()->json(['message' => 'Categories reordered successfully']);
    }

    /**
     * Move category
     * 
     * Move a category to a different parent.
     * 
     * @urlParam id integer required Category ID to move. Example: 5
     * @bodyParam parent_id integer New parent category ID (null for root). Example: 2
     * 
     * @response 200 scenario="Moved" {
     *   "data": {
     *     "id": 5,
     *     "name": "Laptops",
     *     "parent_id": 2
     *   }
     * }
     * 
     * @response 400 scenario="Invalid move" {
     *   "message": "Cannot move category to a descendant"
     * }
     */
    public function move(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        try {
            $category = $this->categoryService->moveCategory($id, $request->input('parent_id'));
            return response()->json(['data' => $category]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
