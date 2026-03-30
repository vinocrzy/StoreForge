---
name: ecommerce-api-docs
description: 'Generate and maintain API documentation for Laravel controllers using Scribe. Use when: creating new API endpoints, updating existing endpoints, need to regenerate docs, setting up documentation for first time, or documenting controller methods with PHPDoc annotations.'
argument-hint: 'Specify controller name or "setup" for initial configuration'
---

# E-Commerce API Documentation Generator

## Purpose

Automate API documentation generation for the multi-tenant e-commerce platform using Laravel Scribe. Ensures all API endpoints are professionally documented and kept up-to-date.

## When to Use

- Creating new API controllers or endpoints
- Updating existing API endpoints
- Setting up API documentation for the first time
- Regenerating documentation after changes
- Need templates for documenting controller methods

## Prerequisites

- Laravel backend in `platform/backend/`
- Scribe should be installed (or run setup)

## Quick Actions

### 1. Setup Documentation System (First Time)

```bash
cd platform/backend
bash ../../scripts/setup-api-docs.sh  # Linux/Mac
../../scripts/setup-api-docs.bat      # Windows
```

This installs Laravel Scribe, configures it, creates example controllers, and sets up Git hooks.

### 2. Document a Controller

Use Scribe PHPDoc annotations in your controller:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * @group [Resource Name]
 * 
 * Brief description of what this resource manages
 */
class ResourceController extends Controller
{
    /**
     * List resources
     * 
     * Get a paginated list with filtering and sorting.
     * 
     * @authenticated
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * @queryParam search string Search term. Example: keyword
     * @queryParam status string Filter by status. Example: active
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id": 1, "name": "Item Name", "status": "active"}
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 100
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

    /**
     * Get resource details
     * 
     * @urlParam id integer required Resource ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": {"id": 1, "name": "Item Name"}
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Resource not found"
     * }
     */
    public function show(int $id)
    {
        // Implementation
    }

    /**
     * Create resource
     * 
     * @bodyParam name string required Resource name. Example: New Item
     * @bodyParam status string Status: active, draft. Example: active
     * 
     * @response 201 scenario="Created" {
     *   "data": {"id": 1, "name": "New Item"}
     * }
     * @response 422 scenario="Validation failed" {
     *   "message": "The given data was invalid",
     *   "errors": {"name": ["The name field is required"]}
     * }
     */
    public function store(Request $request)
    {
        // Implementation
    }

    /**
     * Update resource
     * 
     * @urlParam id integer required Resource ID. Example: 1
     * @bodyParam name string Resource name. Example: Updated Name
     * 
     * @response 200 scenario="Updated" {
     *   "data": {"id": 1, "name": "Updated Name"}
     * }
     */
    public function update(Request $request, int $id)
    {
        // Implementation
    }

    /**
     * Delete resource
     * 
     * @urlParam id integer required Resource ID. Example: 1
     * 
     * @response 204 scenario="Deleted"
     * @response 404 scenario="Not found" {
     *   "message": "Resource not found"
     * }
     */
    public function destroy(int $id)
    {
        // Implementation
    }
}
```

### 3. Generate Documentation

After documenting controllers:

```bash
cd platform/backend
php artisan scribe:generate
```

View at: `http://localhost:8000/docs`

### 4. Common Annotations

**Group endpoints:**
```php
/**
 * @group Products
 * 
 * APIs for managing products
 */
```

**Mark as authenticated:**
```php
/**
 * @authenticated
 */
```

**Query parameters:**
```php
/**
 * @queryParam page integer Page number. Example: 1
 * @queryParam filter string Filter value. Example: active
 */
```

**URL parameters:**
```php
/**
 * @urlParam id integer required Resource ID. Example: 123
 */
```

**Body parameters:**
```php
/**
 * @bodyParam name string required Item name. Example: Product Name
 * @bodyParam price number Price. Example: 29.99
 * @bodyParam tags array Tags. Example: ["electronics", "new"]
 */
```

**Response examples:**
```php
/**
 * @response 200 scenario="Success" {
 *   "data": {"id": 1, "name": "Item"}
 * }
 * @response 404 scenario="Not found" {
 *   "message": "Not found"
 * }
 */
```

**Custom headers:**
```php
/**
 * @header X-Store-ID required Store identifier. Example: 1
 */
```

## Complete Workflow

### Step 1: Create or Update Controller

Create controller in `platform/backend/app/Http/Controllers/Api/V1/`:

```bash
php artisan make:controller Api/V1/ProductController --api
```

### Step 2: Add Documentation

Add PHPDoc annotations to every public method following the templates above.

**Required for each endpoint:**
- `@group` annotation (controller level)
- Method description
- All parameters documented (`@queryParam`, `@bodyParam`, `@urlParam`)
- At least one `@response` with realistic data
- Error responses (404, 422, etc.)
- `@authenticated` if endpoint requires auth

### Step 3: Generate Docs

```bash
php artisan scribe:generate
```

### Step 4: Verify

1. Open `http://localhost:8000/docs`
2. Find your endpoint in the docs
3. Click "Try it out" and test
4. Verify response format matches reality

### Step 5: Commit

Git hook will auto-regenerate docs on commit if controllers changed.

## Reference Files

- **Quick Reference**: [docs/API-DOCS-QUICK-REFERENCE.md](../../docs/API-DOCS-QUICK-REFERENCE.md) - Copy-paste templates
- **Complete Guide**: [docs/16-api-documentation-system.md](../../docs/16-api-documentation-system.md) - Full documentation system
- **2-Min Overview**: [docs/API-DOCS-OVERVIEW.md](../../docs/API-DOCS-OVERVIEW.md) - Quick start guide
- **Example Controller**: [platform/backend/app/Http/Controllers/Api/V1/StoreController.php](../../platform/backend/app/Http/Controllers/Api/V1/StoreController.php) - Real-world example

## Standard Error Responses

Copy-paste these for consistency:

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
 */
```

## Tips

✅ **Use realistic examples** - Actual data from your system
✅ **Document all parameters** - Don't skip query params
✅ **Include error scenarios** - 404, 422, 403, etc.
✅ **Test "Try it out"** - Ensure it works in the docs
✅ **Keep descriptions clear** - One-line summaries

❌ **Don't skip authentication** - Mark endpoints requiring auth
❌ **Don't forget headers** - Document X-Store-ID requirement
❌ **Don't use generic responses** - Use real data structures
❌ **Don't skip validation errors** - Document 422 responses

## Checklist Before Committing

- [ ] Added `@group` annotation
- [ ] Documented all parameters
- [ ] Included realistic examples
- [ ] Added success response (200/201)
- [ ] Added error responses (404, 422, etc.)
- [ ] Marked `@authenticated` if needed
- [ ] Documented required headers
- [ ] Ran `php artisan scribe:generate`
- [ ] Tested endpoint in docs UI
- [ ] Verified response matches actual API

## Troubleshooting

**Docs not updating?**
```bash
php artisan scribe:generate --force
```

**Missing endpoints?**
- Check route prefix matches config (v1/* or api/v1/*)
- Verify controller is in correct namespace
- Check `config/scribe.php` route matching

**Try-it-out not working?**
- Check CORS settings in backend
- Verify `SCRIBE_TRY_IT_OUT_URL` in .env
- Ensure auth token is valid

## Output Files

After generation:
- `public/docs/index.html` - Interactive HTML docs
- `public/docs/collection.json` - Postman collection (import ready)
- `public/docs/openapi.yaml` - OpenAPI specification

## Automation

Documentation auto-regenerates on:
- Git commit (if controllers changed) - via pre-commit hook
- CI/CD deployment - via GitHub Actions
- Manual: `php artisan scribe:generate`

---

**Remember**: Documentation is code. Keep it up-to-date with every API change!
