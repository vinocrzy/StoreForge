<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Products
 * 
 * Manage products for the authenticated store. All operations are automatically scoped to the current tenant.
 * 
 * @authenticated
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * List products
     * 
     * Get a paginated list of products with optional filtering and sorting.
     * Products are automatically scoped to the authenticated store.
     * 
     * @queryParam search string Search products by name, SKU, or description. Example: laptop
     * @queryParam status string Filter by status: active, draft, archived. Example: active
     * @queryParam is_featured boolean Filter featured products. Example: 1
     * @queryParam category_id integer Filter by category ID. Example: 5
     * @queryParam stock_status string Filter by stock: in_stock, out_of_stock, low_stock. Example: in_stock
     * @queryParam sort_by string Sort field. Example: created_at
     * @queryParam sort_order string Sort direction: asc, desc. Example: desc
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Premium Laptop Pro",
     *       "slug": "premium-laptop-pro",
     *       "sku": "LAP-001",
     *       "price": "999.99",
     *       "status": "active",
     *       "stock_quantity": 50,
     *       "is_featured": true
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 100
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'status', 'is_featured', 'category_id',
            'stock_status', 'sort_by', 'sort_order'
        ]);

        $perPage = min($request->input('per_page', 20), 100);
        
        $products = $this->productService->getProducts($filters, $perPage);

        return response()->json($products);
    }

    /**
     * Export products CSV
     *
     * Export filtered products as a CSV file for reporting or migration.
     *
     * @queryParam search string Search products by name, SKU, or description. Example: laptop
     * @queryParam status string Filter by status: active, draft, archived. Example: active
     * @queryParam is_featured boolean Filter featured products. Example: 1
     * @queryParam category_id integer Filter by category ID. Example: 5
     * @queryParam stock_status string Filter by stock: in_stock, out_of_stock, low_stock. Example: in_stock
     * @queryParam sort_by string Sort field. Example: created_at
     * @queryParam sort_order string Sort direction: asc, desc. Example: desc
     *
     * @response 200 scenario="CSV file download"
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'search', 'status', 'is_featured', 'category_id',
            'stock_status', 'sort_by', 'sort_order'
        ]);

        $products = $this->productService->getProductsForExport($filters);

        $filename = 'products_export_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'id',
                'name',
                'slug',
                'sku',
                'price',
                'compare_price',
                'status',
                'is_featured',
                'track_inventory',
                'stock_quantity',
                'low_stock_threshold',
                'categories',
                'created_at',
                'updated_at',
            ]);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->id,
                    $product->name,
                    $product->slug,
                    $product->sku,
                    $product->price,
                    $product->compare_price,
                    $product->status,
                    $product->is_featured ? '1' : '0',
                    $product->track_inventory ? '1' : '0',
                    $product->stock_quantity,
                    $product->low_stock_threshold,
                    $product->categories->pluck('name')->implode(', '),
                    $product->created_at,
                    $product->updated_at,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get product details
     * 
     * Retrieve a single product with all related data (categories, images, variants).
     * 
     * @urlParam id integer required Product ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Premium Laptop Pro",
     *     "slug": "premium-laptop-pro",
     *     "sku": "LAP-001",
     *     "description": "High-performance laptop...",
     *     "price": "999.99",
     *     "compare_price": "1299.99",
     *     "status": "active",
     *     "stock_quantity": 50,
     *     "categories": [],
     *     "images": [],
     *     "variants": []
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Product not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);

        return response()->json(['data' => $product]);
    }

    /**
     * Create product
     * 
     * Create a new product for the authenticated store.
     * 
     * @bodyParam name string required Product name. Example: Premium Laptop Pro
     * @bodyParam slug string Product slug (auto-generated if not provided). Example: premium-laptop-pro
     * @bodyParam sku string Product SKU. Example: LAP-001
     * @bodyParam description string Product description.
     * @bodyParam short_description string Short description (max 500 chars).
     * @bodyParam price number required Product price. Example: 999.99
     * @bodyParam compare_price number Compare at price (for showing discounts). Example: 1299.99
     * @bodyParam cost_price number Cost price (internal). Example: 600.00
     * @bodyParam track_inventory boolean Track inventory for this product. Example: true
     * @bodyParam stock_quantity integer Stock quantity. Example: 50
     * @bodyParam low_stock_threshold integer Low stock alert threshold. Example: 5
     * @bodyParam weight number Product weight. Example: 2.5
     * @bodyParam weight_unit string Weight unit: kg, g, lb, oz. Example: kg
     * @bodyParam dimensions object Product dimensions.
     * @bodyParam dimensions.length number Length. Example: 30
     * @bodyParam dimensions.width number Width. Example: 20
     * @bodyParam dimensions.height number Height. Example: 2
     * @bodyParam dimensions.unit string Dimension unit: cm, m, in, ft. Example: cm
     * @bodyParam status string Product status: draft, active, archived. Example: active
     * @bodyParam is_featured boolean Featured product. Example: false
     * @bodyParam meta_title string SEO title. Example: Buy Premium Laptop Pro
     * @bodyParam meta_description string SEO description.
     * @bodyParam category_ids array Category IDs to assign. Example: [1, 2, 3]
     * 
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "name": "Premium Laptop Pro",
     *     "slug": "premium-laptop-pro",
     *     "price": "999.99",
     *     "status": "active"
     *   }
     * }
     * 
     * @response 422 scenario="Validation failed" {
     *   "message": "The given data was invalid",
     *   "errors": {
     *     "name": ["The name field is required"],
     *     "price": ["The price field is required"]
     *   }
     * }
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return response()->json(['data' => $product], 201);
    }

    /**
     * Update product
     * 
     * Update an existing product. Only provided fields will be updated.
     * 
     * @urlParam id integer required Product ID. Example: 1
     * @bodyParam name string Product name. Example: Updated Laptop Pro
     * @bodyParam price number Product price. Example: 899.99
     * @bodyParam status string Product status: draft, active, archived. Example: active
     * @bodyParam category_ids array Category IDs to assign. Example: [1, 2]
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 1,
     *     "name": "Updated Laptop Pro",
     *     "price": "899.99"
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Product not found"
     * }
     */
    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());

        return response()->json(['data' => $product]);
    }

    /**
     * Delete product
     * 
     * Soft delete a product. The product will be archived and can be restored later.
     * 
     * @urlParam id integer required Product ID. Example: 1
     * 
     * @response 204 scenario="Deleted"
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Product not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);

        return response()->json(null, 204);
    }

    /**
     * Update stock
     * 
     * Update product stock quantity. Supports set, increment, and decrement operations.
     * 
     * @urlParam id integer required Product ID. Example: 1
     * @bodyParam quantity integer required Quantity to set/add/subtract. Example: 10
     * @bodyParam operation string Operation: set, increment, decrement. Example: increment
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 1,
     *     "stock_quantity": 60
     *   }
     * }
     */
    public function updateStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'operation' => 'required|in:set,increment,decrement',
        ]);

        $product = $this->productService->updateStock(
            $id,
            $request->input('quantity'),
            $request->input('operation')
        );

        return response()->json(['data' => $product]);
    }
}
