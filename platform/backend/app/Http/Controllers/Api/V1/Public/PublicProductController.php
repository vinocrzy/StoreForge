<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Storefront
 *
 * Public product browsing APIs for storefronts. No authentication required.
 * Tenant is determined by the X-Store-ID header.
 */
class PublicProductController extends Controller
{
    /**
     * List products
     *
     * Retrieve paginated active products for the storefront.
     *
     * @queryParam search string Search by product name or description. Example: honey
     * @queryParam category_slug string Filter by category slug. Example: soaps
     * @queryParam is_featured boolean Filter featured products. Example: 1
     * @queryParam sort_by string Sort field: price, created_at, name. Example: created_at
     * @queryParam sort_order string Sort direction: asc, desc. Example: desc
     * @queryParam per_page integer Items per page (max 50). Example: 12
     *
     * @response 200 scenario="Success" {
     *   "data": [{"id": 1, "name": "Honey Lavender Soap", "slug": "honey-lavender-soap", "price": "12.99"}],
     *   "meta": {"current_page": 1, "per_page": 12, "total": 24}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['primaryImage', 'categories'])
            ->where('status', 'active');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categorySlug = $request->input('category_slug')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', (bool) $request->input('is_featured'));
        }

        $allowedSortFields = ['price', 'created_at', 'name'];
        $sortBy    = in_array($request->input('sort_by'), $allowedSortFields) ? $request->input('sort_by') : 'created_at';
        $sortOrder = $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage  = min((int) $request->input('per_page', 12), 50);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Get product by slug
     *
     * Retrieve a single active product with full details including images, variants, and categories.
     *
     * @urlParam slug string required Product slug. Example: honey-lavender-soap
     *
     * @response 200 scenario="Success" {
     *   "data": {"id": 1, "name": "Honey Lavender Soap", "slug": "honey-lavender-soap", "price": "12.99"}
     * }
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Product]."}
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['images', 'variants', 'categories'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return response()->json(['data' => $product]);
    }

    /**
     * List categories
     *
     * Retrieve all active top-level categories with their children for storefront navigation.
     *
     * @response 200 scenario="Success" {
     *   "data": [{"id": 1, "name": "Soaps", "slug": "soaps", "children": []}]
     * }
     */
    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Get category with products
     *
     * Retrieve a single category and its paginated active products.
     *
     * @urlParam slug string required Category slug. Example: soaps
     * @queryParam per_page integer Items per page (max 50). Example: 12
     *
     * @response 200 scenario="Success" {
     *   "data": {"id": 1, "name": "Soaps", "slug": "soaps"},
     *   "products": {"data": [], "meta": {}}
     * }
     */
    public function showCategory(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        $perPage  = min((int) $request->input('per_page', 12), 50);
        $products = Product::whereHas('categories', fn ($q) => $q->where('categories.slug', $slug))
            ->where('status', 'active')
            ->with('primaryImage')
            ->paginate($perPage);

        return response()->json([
            'data'     => $category,
            'products' => $products,
        ]);
    }

    /**
     * Featured products
     *
     * Retrieve featured products for homepage hero/featured sections.
     *
     * @response 200 scenario="Success" {
     *   "data": [{"id": 1, "name": "Honey Lavender Soap", "is_featured": true}]
     * }
     */
    public function featured(): JsonResponse
    {
        $products = Product::where('status', 'active')
            ->where('is_featured', true)
            ->with(['primaryImage', 'categories'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return response()->json(['data' => $products]);
    }
}
