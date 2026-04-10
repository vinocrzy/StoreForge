---
name: ecommerce-backend-patterns
description: 'Laravel backend development patterns with focus on avoiding common pitfalls. Use when: creating APIs, handling file uploads, database operations, or debugging Laravel errors.'
argument-hint: 'Specify area: "file-upload", "eloquent", "api-response", "validation", or "debugging"'
---

# E-Commerce Backend Development Patterns (Laravel 11)

## Purpose

Critical patterns and pitfalls for Laravel 11 backend development, focusing on common errors that break frontend integration and data consistency issues.

## When to Use

- Creating new API endpoints (especially file uploads)
- Writing Eloquent models and services
- Database migrations and schema changes
- API response formatting
- Debugging integration issues with React frontend
- Reviewing code before committing

## CRITICAL: File Upload Handling

### ⚠️ Problem: Database Column vs Model Field Mismatch

**Always verify database schema BEFORE writing model code.**

```php
// ❌ WRONG - Using field that doesn't exist in DB
// Migration: create_product_images_table.php
$table->string('file_path');  // ✅ Database column
$table->string('alt_text');

// Service: ProductService.php
ProductImage::create([
    'url' => '/storage/image.jpg',  // ❌ Column 'url' doesn't exist!
    'alt_text' => 'Product image',
]);

// Error: SQLSTATE[HY000]: Integrity constraint violation: 19 NOT NULL constraint failed: product_images.file_path
```

```php
// ✅ CORRECT - Match database schema exactly
ProductImage::create([
    'file_path' => 'products/1/image.jpg',  // ✅ Matches DB column
    'alt_text' => 'Product image',
]);

// If you want 'url' for API responses, use accessor:
// Model: ProductImage.php
protected $appends = ['url'];

public function getUrlAttribute(): string
{
    return url('storage/' . $this->file_path);
}
```

### File Storage Best Practices

```php
// ✅ Store relative path in database, not full URL
public function uploadProductImages(int $productId, array $files): Collection
{
    $uploadedImages = new Collection(); // ✅ Eloquent Collection, not Support\Collection
    
    foreach ($files as $index => $file) {
        // Store file and get path
        $path = $file->store("products/{$productId}", 'public');
        // Result: "products/1/abc123.jpg"
        
        $image = ProductImage::create([
            'product_id' => $productId,
            'file_path' => $path,  // ✅ Store relative path
            'alt_text' => $product->name,
            'sort_order' => $index,
            'is_primary' => $index === 0,
        ]);
        
        $uploadedImages->push($image);
    }
    
    return $uploadedImages; // Eloquent\Collection
}

// Delete file using file_path
public function deleteProductImage(int $imageId): bool
{
    $image = ProductImage::findOrFail($imageId);
    
    // Delete physical file
    \Storage::disk('public')->delete($image->file_path); // ✅ Use file_path directly
    
    return $image->delete();
}
```

### File Upload Controller Pattern

```php
use Illuminate\Support\Facades\Validator;

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

    try {
        $images = $this->productService->uploadProductImages(
            $id,
            $request->file('images'), // ✅ Get uploaded files
            ['is_primary' => $request->boolean('is_primary', true)]
        );

        return response()->json([
            'data' => $images,
            'message' => 'Images uploaded successfully',
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Upload failed: ' . $e->getMessage(),
        ], 500);
    }
}
```

## CRITICAL: Collection Type Mismatches

### ⚠️ Problem: Support\Collection vs Eloquent\Collection

```php
// ❌ WRONG - Type mismatch
use Illuminate\Database\Eloquent\Collection;

public function uploadProductImages(int $productId, array $files): Collection
{
    $uploadedImages = collect(); // ✅ Returns Support\Collection
    // ...
    return $uploadedImages;
}

// Error: Return value must be of type Illuminate\Database\Eloquent\Collection, 
//        Illuminate\Support\Collection returned
```

```php
// ✅ CORRECT - Use proper Collection type
use Illuminate\Database\Eloquent\Collection;

public function uploadProductImages(int $productId, array $files): Collection
{
    $uploadedImages = new Collection(); // ✅ Eloquent\Collection
    
    foreach ($files as $file) {
        $image = ProductImage::create([/* ... */]);
        $uploadedImages->push($image); // ✅ Push Eloquent model
    }
    
    return $uploadedImages; // ✅ Type matches
}
```

**When to use which Collection**:
- **Eloquent\Collection**: For collections of Eloquent models (return type from queries)
- **Support\Collection**: For general array manipulation (`collect([1, 2, 3])`)

## CRITICAL: API Response Completeness

### ⚠️ Problem: Missing Fields in API Responses

Frontend breaks when expected fields are missing from API responses.

```php
// ❌ INCOMPLETE - Frontend expects more fields
public function login(Request $request)
{
    // ...
    $stores = $user->stores()->get()->map(function ($store) {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            // ❌ Missing: domain, status, currency, created_at, updated_at
        ];
    });

    return response()->json([
        'user' => $user,
        'token' => $token,
        'stores' => $stores,
    ]);
}

// Frontend error: Cannot read property 'currency' of undefined
```

```php
// ✅ COMPLETE - All fields frontend needs
public function login(Request $request)
{
    // ...
    $stores = $user->stores()->get()->map(function ($store) {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'domain' => $store->domain,
            'status' => $store->status,
            'currency' => $store->currency ?? 'USD',      // ✅ With fallback
            'created_at' => $store->created_at->toISOString(),
            'updated_at' => $store->updated_at->toISOString(),
        ];
    });

    return response()->json([
        'user' => $user,
        'token' => $token,
        'stores' => $stores,
    ]);
}
```

### Response Validation Checklist

Before writing any API endpoint:

1. **Check frontend TypeScript interface** - What fields does it expect?
```typescript
// Frontend: types/auth.ts
interface Store {
  id: number;
  name: string;
  domain: string;      // ⚠️ Backend must include
  status: string;      // ⚠️ Backend must include
  currency: string;    // ⚠️ Backend must include
  created_at: string;  // ⚠️ Backend must include
  updated_at: string;  // ⚠️ Backend must include
}
```

2. **Map backend response to match interface**
```php
// Backend: Match every field from TypeScript interface
$stores = $user->stores()->get()->map(function ($store) {
    return [
        'id' => $store->id,                          // ✅
        'name' => $store->name,                      // ✅
        'domain' => $store->domain,                  // ✅
        'status' => $store->status,                  // ✅
        'currency' => $store->currency ?? 'USD',     // ✅
        'created_at' => $store->created_at->toISOString(), // ✅
        'updated_at' => $store->updated_at->toISOString(), // ✅
    ];
});
```

3. **Test response in Scribe/Postman** before frontend integration

## CRITICAL: Code Formatting & Syntax

### ⚠️ Problem: Missing Line Breaks in Comments

```php
// ❌ WRONG - Variable on same line as comment
// Determine if we should set the first uploaded image as primary        $setPrimary = $options['is_primary'] ?? true;

// Error: Undefined variable $setPrimary (treated as part of comment)
```

```php
// ✅ CORRECT - Proper line break after comment
// Determine if we should set the first uploaded image as primary
$setPrimary = $options['is_primary'] ?? true;
```

### ⚠️ Problem: Array Syntax in JSON Responses

```php
// ❌ SYNTAX ERROR - Colon instead of arrow
return response()->json([
    'data' => $image,
    'message': 'Primary image updated',  // ❌ Colon instead of =>
]);

// Error: syntax error, unexpected token ":", expecting "]"
```

```php
// ✅ CORRECT - Use => for PHP arrays
return response()->json([
    'data' => $image,
    'message' => 'Primary image updated',  // ✅ Arrow operator
]);
```

## Common Eloquent Patterns

### Creating Related Models

```php
// ✅ Use relationship to auto-set foreign key
$product = Product::findOrFail($productId);

$image = $product->images()->create([
    'file_path' => $path,
    'alt_text' => 'Image',
    // product_id and store_id auto-set by relationship + global scope
]);
```

### Updating with Conditionals

```php
// Set first image as primary if no primary exists
$hasExistingPrimary = $product->images()->where('is_primary', true)->exists();

$image = $product->images()->create([
    'file_path' => $path,
    'is_primary' => !$hasExistingPrimary && $setPrimary && $index === 0,
]);
```

### Using Accessors (Computed Properties)

```php
// Model: ProductImage.php
protected $appends = ['url']; // ✅ Include in JSON

public function getUrlAttribute(): string
{
    if (!$this->file_path) {
        return '';
    }
    return url('storage/' . $this->file_path);
}

// API Response: { "file_path": "products/1/image.jpg", "url": "http://example.com/storage/products/1/image.jpg" }
```

## Error Handling Best Practices

### Service Layer Exceptions

```php
public function uploadProductImages(int $productId, array $files, array $options = []): Collection
{
    $product = Product::findOrFail($productId); // ✅ Throws 404 if not found
    
    // Check max images limit
    $existingCount = $product->images()->count();
    if ($existingCount + count($files) > 10) {
        throw new \Exception('Maximum 10 images allowed per product');
    }
    
    // ... rest of logic
}
```

### Controller Error Handling

```php
public function store(Request $request, int $id): JsonResponse
{
    try {
        $images = $this->productService->uploadProductImages(
            $id,
            $request->file('images'),
            ['is_primary' => $request->boolean('is_primary')]
        );

        return response()->json([
            'data' => $images,
            'message' => 'Images uploaded successfully',
        ], 201);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Product not found',
        ], 404);
    } catch (\Exception $e) {
        \Log::error('Image upload failed', [
            'product_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return response()->json([
            'message' => 'Upload failed: ' . $e->getMessage(),
        ], 500);
    }
}
```

## Pre-Commit Checklist

Before committing ANY backend code:

- [ ] **Verify database schema** matches model `$fillable` fields
- [ ] **Run migrations** if created: `php artisan migrate`
- [ ] **Check Collection types** - Eloquent vs Support
- [ ] **Compare API response** to frontend TypeScript interface
- [ ] **Test in Scribe/Postman** before frontend integration
- [ ] **Check syntax** - Line breaks after comments, `=>` not `:`
- [ ] **Verify file paths** - Relative paths in DB, not full URLs
- [ ] **Run `php artisan scribe:generate`** if API changed
- [ ] **Test manually** with actual file uploads/API calls
- [ ] **Check error handling** - Try invalid inputs

## Common Error Messages → Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| `NOT NULL constraint failed: table.column` | Using wrong column name | Check migration, use correct `$fillable` field |
| `Return value must be of type Eloquent\Collection, Support\Collection returned` | Used `collect()` instead of `new Collection()` | Use `new Collection()` for Eloquent models |
| `Undefined variable $var` | Variable on same line as comment | Add line break after comment |
| `syntax error, unexpected token ":"` | Used `:` instead of `=>` in array | Use `=>` for PHP arrays |
| `The given data was invalid` (file upload) | FormData key mismatch | Match backend validation (`images.*`) with frontend (`images[]`) |

## Testing File Uploads

```bash
# Test with curl (multipart/form-data)
curl -X POST http://localhost:8000/api/v1/products/1/images \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Store-ID: 1" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.jpg" \
  -F "is_primary=1"

# Expected response
{
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "file_path": "products/1/abc123.jpg",
      "url": "http://localhost:8000/storage/products/1/abc123.jpg",
      "is_primary": true
    }
  ],
  "message": "Images uploaded successfully"
}
```

## Resources

- **Laravel Docs**: https://laravel.com/docs/11.x
- **Storage Docs**: https://laravel.com/docs/11.x/filesystem
- **Eloquent Collections**: https://laravel.com/docs/11.x/eloquent-collections
- **Validation Rules**: https://laravel.com/docs/11.x/validation#available-validation-rules
