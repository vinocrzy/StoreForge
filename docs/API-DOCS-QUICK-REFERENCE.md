# API Documentation Quick Reference

Quick copy-paste templates for documenting Laravel API controllers with Scribe.

## Installation (One-Time Setup)

```bash
composer require --dev knuckleswtf/scribe
php artisan vendor:publish --tag=scribe-config
php artisan scribe:generate
```

Access docs at: `http://localhost:8000/docs`

## Basic Controller Template

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * @group [Group Name]
 * 
 * [Brief description of this resource]
 */
class ResourceController extends Controller
{
    /**
     * List resources
     * 
     * [Detailed description]
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * 
     * @response 200 scenario="Success" {
     *   "data": [],
     *   "meta": {"current_page": 1, "total": 100}
     * }
     */
    public function index() { }

    /**
     * Get resource details
     * 
     * @urlParam id integer required Resource ID. Example: 1
     * 
     * @response 200 scenario="Success" {"data": {}}
     * @response 404 scenario="Not found" {"message": "Not found"}
     */
    public function show($id) { }

    /**
     * Create resource
     * 
     * @bodyParam name string required Resource name. Example: Example
     * @bodyParam status string Status value. Example: active
     * 
     * @response 201 scenario="Created" {"data": {}}
     * @response 422 scenario="Validation failed" {"errors": {}}
     */
    public function store() { }

    /**
     * Update resource
     * 
     * @urlParam id integer required Resource ID. Example: 1
     * @bodyParam name string Resource name. Example: Updated Name
     * 
     * @response 200 scenario="Updated" {"data": {}}
     */
    public function update($id) { }

    /**
     * Delete resource
     * 
     * @urlParam id integer required Resource ID. Example: 1
     * 
     * @response 204 scenario="Deleted"
     */
    public function destroy($id) { }
}
```

## Common Annotations

### Group Endpoints

```php
/**
 * @group Products
 * 
 * APIs for managing products in your store
 */
```

### URL Parameters

```php
/**
 * @urlParam id integer required The product ID. Example: 123
 * @urlParam slug string required Product slug. Example: wireless-mouse
 */
```

### Query Parameters

```php
/**
 * @queryParam page integer Page number. Example: 1
 * @queryParam per_page integer Items per page (max 100). Example: 20
 * @queryParam search string Search term. Example: laptop
 * @queryParam filter[status] string Filter by status. Example: active
 * @queryParam sort string Sort field: name, price, created_at. Example: price
 * @queryParam direction string Sort direction: asc, desc. Example: desc
 */
```

### Body Parameters

```php
/**
 * @bodyParam name string required Product name. Example: Wireless Mouse
 * @bodyParam price number required Price. Example: 29.99
 * @bodyParam status string Status: active, draft, archived. Example: active
 * @bodyParam tags array Product tags. Example: ["electronics", "new"]
 * @bodyParam tags.* string Tag name
 */
```

### Nested Objects

```php
/**
 * @bodyParam product object required Product data
 * @bodyParam product.name string required Product name
 * @bodyParam product.price number required Price
 * @bodyParam product.variants array Product variants
 * @bodyParam product.variants[].sku string Variant SKU
 * @bodyParam product.variants[].price number Variant price
 */
```

### Response Examples

```php
/**
 * @response 200 scenario="Success" {
 *   "data": {
 *     "id": 1,
 *     "name": "Product Name",
 *     "price": 29.99
 *   }
 * }
 * 
 * @response 404 scenario="Not found" {
 *   "message": "Product not found"
 * }
 * 
 * @response 422 scenario="Validation error" {
 *   "message": "The given data was invalid",
 *   "errors": {
 *     "name": ["The name field is required"],
 *     "price": ["The price must be a number"]
 *   }
 * }
 */
```

### Authentication

```php
/**
 * @authenticated
 * 
 * List products
 * 
 * This endpoint requires authentication using Bearer token.
 */
```

### Custom Headers

```php
/**
 * @header X-Store-ID required The store identifier. Example: 1
 * @header Accept application/json
 * @header Content-Type application/json
 */
```

## Complete Endpoint Example

```php
/**
 * @group Products
 * 
 * APIs for managing products
 */
class ProductController extends Controller
{
    /**
     * List products
     * 
     * Get a paginated list of products with filtering and sorting options.
     * 
     * @authenticated
     * 
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * @queryParam search string Search by name or SKU. Example: laptop
     * @queryParam category_id integer Filter by category. Example: 5
     * @queryParam status string Filter by status: active, draft, archived. Example: active
     * @queryParam min_price number Minimum price. Example: 10.00
     * @queryParam max_price number Maximum price. Example: 100.00
     * @queryParam sort string Sort field. Example: price
     * @queryParam direction string Sort direction: asc, desc. Example: desc
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "store_id": 1,
     *       "name": "Wireless Mouse",
     *       "slug": "wireless-mouse",
     *       "sku": "WM-001",
     *       "price": 29.99,
     *       "status": "active",
     *       "inventory_quantity": 150,
     *       "images": [
     *         {"url": "https://cdn.example.com/image.jpg"}
     *       ]
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 156,
     *     "last_page": 8
     *   }
     * }
     * 
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated"
     * }
     */
    public function index(Request $request)
    {
        // Implementation
    }
}
```

## Error Responses Template

Copy-paste standard error responses:

```php
/**
 * @response 400 scenario="Bad Request" {
 *   "message": "Invalid request parameters"
 * }
 * @response 401 scenario="Unauthenticated" {
 *   "message": "Unauthenticated"
 * }
 * @response 403 scenario="Forbidden" {
 *   "message": "This action is unauthorized"
 * }
 * @response 404 scenario="Not Found" {
 *   "message": "Resource not found"
 * }
 * @response 422 scenario="Validation Error" {
 *   "message": "The given data was invalid",
 *   "errors": {
 *     "field": ["Error message"]
 *   }
 * }
 * @response 429 scenario="Rate Limited" {
 *   "message": "Too many requests"
 * }
 * @response 500 scenario="Server Error" {
 *   "message": "Internal server error"
 * }
 */
```

## Pagination Response Template

```php
/**
 * @response 200 {
 *   "data": [],
 *   "meta": {
 *     "current_page": 1,
 *     "per_page": 20,
 *     "total": 100,
 *     "last_page": 5,
 *     "from": 1,
 *     "to": 20
 *   },
 *   "links": {
 *     "first": "https://api.com/endpoint?page=1",
 *     "last": "https://api.com/endpoint?page=5",
 *     "next": "https://api.com/endpoint?page=2",
 *     "prev": null
 *   }
 * }
 */
```

## File Upload Template

```php
/**
 * Upload product image
 * 
 * @bodyParam image file required Image file (max 5MB, jpg/png). Example: /path/to/image.jpg
 * @bodyParam position integer Image position. Example: 1
 * @bodyParam alt string Alt text. Example: Product front view
 * 
 * @response 201 scenario="Uploaded" {
 *   "data": {
 *     "id": 1,
 *     "url": "https://cdn.example.com/products/image.jpg",
 *     "position": 1,
 *     "alt": "Product front view"
 *   }
 * }
 * 
 * @response 422 scenario="Invalid file" {
 *   "errors": {
 *     "image": ["The image must be a file of type: jpeg, png"]
 *   }
 * }
 */
```

## Bulk Operations Template

```php
/**
 * Bulk update products
 * 
 * @bodyParam products array required Array of product updates
 * @bodyParam products[].id integer required Product ID
 * @bodyParam products[].status string New status
 * @bodyParam products[].price number New price
 * 
 * @response 200 scenario="Success" {
 *   "data": {
 *     "updated": 15,
 *     "failed": 2,
 *     "errors": [
 *       {"id": 5, "error": "Product not found"}
 *     ]
 *   }
 * }
 */
```

## Generate Documentation

```bash
# Generate docs
php artisan scribe:generate

# Force regenerate (clear cache)
php artisan scribe:generate --force

# View docs
open http://localhost:8000/docs
```

## Auto-Generate on Git Commit

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash
if git diff --cached --name-only | grep -E '^app/Http/Controllers/Api/'; then
    php artisan scribe:generate --no-interaction
    git add public/docs
fi
```

Make executable:
```bash
chmod +x .git/hooks/pre-commit
```

## Configuration Shortcuts

**Enable try-it-out:**
```php
// config/scribe.php
'try_it_out' => ['enabled' => true],
```

**Multi-language examples:**
```php
'example_languages' => ['bash', 'javascript', 'php', 'python'],
```

**Export formats:**
```php
'postman' => ['enabled' => true],
'openapi' => ['enabled' => true],
```

## Testing Documentation

```php
// Test in Postman
public/docs/collection.json

// Test with curl
curl https://api.yourplatform.com/v1/products \
  -H "Authorization: Bearer token" \
  -H "X-Store-ID: 1"
```

## Common Gotchas

❌ **Don't:**
- Use `@param` (use `@bodyParam`, `@queryParam`, `@urlParam`)
- Forget `required` keyword for required fields
- Miss response status codes
- Skip authentication annotation

✅ **Do:**
- Add realistic examples
- Document all query parameters
- Include error scenarios
- Test documented endpoints
- Update when response changes

## Quick Checklist

Before committing controller changes:

- [ ] Added `@group` annotation
- [ ] Documented all parameters
- [ ] Included example values
- [ ] Added success response
- [ ] Added error responses
- [ ] Marked authentication if needed
- [ ] Tested endpoint works
- [ ] Ran `php artisan scribe:generate`

## Resources

- **Full Docs**: [docs/16-api-documentation-system.md](docs/16-api-documentation-system.md)
- **Scribe Docs**: https://scribe.knuckles.wtf
- **View Docs**: http://localhost:8000/docs

---

**Pro tip**: Keep this file open in a side window while coding! 🚀
