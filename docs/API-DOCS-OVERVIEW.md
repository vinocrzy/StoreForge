# ⚡ API Documentation - 2 Minute Overview

**Professional, auto-updating API documentation that stays in sync with your code.**

## What You Get

✅ **Beautiful Interactive Docs** - Professional HTML documentation with try-it-out feature  
✅ **Always Up-to-Date** - Auto-generates when you commit API changes  
✅ **Zero Maintenance** - Just add PHPDoc comments to your controllers  
✅ **Multi-Format Export** - Postman collections, OpenAPI/Swagger specs  
✅ **Client SDKs** - Generate TypeScript, PHP, Python client libraries  
✅ **Live Testing** - Test endpoints directly in the browser  

## Quick Setup (5 Minutes)

```bash
# Navigate to Laravel backend
cd platform/backend

# Run setup script
bash ../../scripts/setup-api-docs.sh

# Start server
php artisan serve

# View docs
# Open: http://localhost:8000/docs
```

Done! 🎉

## How It Works

1. **Add PHPDoc to controllers** - Document as you code
2. **Commit your changes** - Git hook auto-generates docs
3. **Documentation updates** - Always in sync with code

## Example: Document a Controller

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

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
     * Get paginated list of products with filtering.
     * 
     * @queryParam search string Search by name. Example: laptop
     * @queryParam status string Filter: active, draft. Example: active
     * 
     * @response 200 {
     *   "data": [{"id": 1, "name": "Product"}],
     *   "meta": {"total": 100}
     * }
     */
    public function index(Request $request)
    {
        // Your implementation
    }
}
```

**That's it!** Run `php artisan scribe:generate` and your docs are ready.

## Common Annotations (Copy-Paste)

```php
/**
 * @group [Group Name]                          // Group endpoints
 * @authenticated                               // Requires auth
 * 
 * @queryParam page integer Example: 1         // URL query params
 * @bodyParam name string required Example: X  // POST body params
 * @urlParam id integer required Example: 1    // URL path params
 * 
 * @response 200 {"data": {}}                  // Success response
 * @response 404 {"message": "Not found"}      // Error response
 */
```

## Generate Documentation

```bash
# Regenerate docs
php artisan scribe:generate

# Force regenerate (clear cache)
php artisan scribe:generate --force
```

## Access Your Docs

| Location | URL |
|----------|-----|
| **HTML Docs** | http://localhost:8000/docs |
| **Postman Collection** | public/docs/collection.json |
| **OpenAPI Spec** | public/docs/openapi.yaml |

## Next Steps

1. **Quick Reference** → [docs/API-DOCS-QUICK-REFERENCE.md](API-DOCS-QUICK-REFERENCE.md)  
   Copy-paste templates for common scenarios

2. **Complete Guide** → [docs/16-api-documentation-system.md](16-api-documentation-system.md)  
   Advanced features, CI/CD integration, versioning

3. **Example Controller** → [platform/backend/app/Http/Controllers/Api/V1/StoreController.php](../platform/backend/app/Http/Controllers/Api/V1/StoreController.php)  
   Real-world documented controller

## Benefits

### For You (Developer)
- 📝 Document while you code (PHPDoc comments)
- ⏱️ Save time - no manual documentation
- 🔄 Never outdated - auto-regenerates
- 🧪 Test endpoints in browser

### For Your Team
- 👁️ Everyone sees latest API
- 📋 Share Postman collections
- 🎯 Frontend devs know exactly what to call
- 📊 Product team can review capabilities

### For Clients
- 📚 Professional documentation
- 🔗 Easy API integration
- 💻 Generate client SDKs
- ✅ Try before implementing

## Pro Tips

✨ **Auto-update on commit** - Setup script installs Git hook  
✨ **Use realistic examples** - Better docs = happier developers  
✨ **Document errors** - Include 400, 404, 422 responses  
✨ **Group related endpoints** - Use `@group` annotation  
✨ **Test your docs** - Click "Try it out" button  

## Tools Installed

| Tool | Purpose |
|------|---------|
| **Laravel Scribe** | Auto-generates docs from code |
| **Git Hook** | Auto-regenerate on commit |
| **Example Controller** | Reference for documentation style |
| **Scribe Config** | Pre-configured for multi-tenant API |

## Files Created

```
platform/backend/
├── config/scribe.php                      # Scribe configuration
├── public/docs/                           # Generated documentation
│   ├── index.html                        # Interactive HTML docs
│   ├── collection.json                   # Postman collection
│   └── openapi.yaml                      # OpenAPI spec
├── app/Http/Controllers/Api/V1/
│   └── ExampleController.php             # Example documented controller
└── .git/hooks/pre-commit                 # Auto-generate hook
```

## Quick Checklist

Before committing API changes:

- [ ] Added PHPDoc comments
- [ ] Included `@group` annotation
- [ ] Documented all parameters
- [ ] Added realistic examples
- [ ] Included error responses
- [ ] Tested endpoint works
- [ ] Ran `php artisan scribe:generate` (or let Git hook do it)

## Support

**Questions?**
- 📖 Quick Reference: [API-DOCS-QUICK-REFERENCE.md](API-DOCS-QUICK-REFERENCE.md)
- 📚 Full Guide: [16-api-documentation-system.md](16-api-documentation-system.md)
- 🌐 Scribe Docs: https://scribe.knuckles.wtf

---

**Remember:** Good documentation is code that explains itself. With Scribe, you document once (as PHPDoc), and get:
- Interactive HTML docs
- Postman collections  
- OpenAPI specs
- Client SDKs

**All automatically updated when you commit!** 🚀
