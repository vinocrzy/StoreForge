<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @group Product Images
 * 
 * Manage product images
 * 
 * @authenticated
 */
class ProductImageController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Upload product images
     * 
     * Upload one or more images for a product. Maximum 10 images per product.
     * 
     * @urlParam id integer required Product ID. Example: 1
     * @bodyParam images file[] required Array of image files. Max 5MB each.
     * @bodyParam is_primary boolean Set first image as primary. Example: true
     * 
     * @response 201 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "product_id": 1,
     *       "url": "http://localhost:8000/storage/products/1/image1.jpg",
     *       "alt_text": null,
     *       "sort_order": 0,
     *       "is_primary": true
     *     }
     *   ],
     *   "message": "Images uploaded successfully"
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid",
     *   "errors": {
     *     "images.0": ["The image must be a file of type: jpeg, jpg, png, gif, webp."]
     *   }
     * }
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
            'is_primary' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $images = $this->productService->uploadProductImages(
            $id,
            $request->file('images'),
            ['is_primary' => $request->boolean('is_primary', true)]
        );

        return response()->json([
            'data' => $images,
            'message' => 'Images uploaded successfully',
        ], 201);
    }

    /**
     * Delete product image
     * 
     * Delete a specific product image.
     * 
     * @urlParam id integer required Product ID. Example: 1
     * @urlParam imageId integer required Image ID. Example: 5
     * 
     * @response 200 {
     *   "message": "Image deleted successfully"
     * }
     * 
     * @response 404 {
     *   "message": "Image not found"
     * }
     */
    public function destroy(int $id, int $imageId): JsonResponse
    {
        $this->productService->deleteProductImage($id, $imageId);

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }

    /**
     * Set primary image
     * 
     * Set a specific image as the primary product image.
     * 
     * @urlParam id integer required Product ID. Example: 1
     * @urlParam imageId integer required Image ID. Example: 5
     * 
     * @response 200 {
     *   "data": {
     *     "id": 5,
     *     "product_id": 1,
     *     "url": "http://localhost:8000/storage/products/1/image5.jpg",
     *     "is_primary": true
     *   },
     *   "message": "Primary image updated"
     * }
     */
    public function setPrimary(int $id, int $imageId): JsonResponse
    {
        $image = $this->productService->setPrimaryImage($id, $imageId);

        return response()->json([
            'data' => $image,
            'message' => 'Primary image updated',
        ]);
    }
}
