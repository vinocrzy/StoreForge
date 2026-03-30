#!/bin/bash

# API Documentation Setup Script
# Installs and configures Laravel Scribe for automatic API documentation

set -e  # Exit on error

echo "========================================="
echo "API Documentation Setup (Laravel Scribe)"
echo "========================================="
echo ""

# Check if we're in the Laravel backend directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found."
    echo "   Please run this script from your Laravel backend directory."
    exit 1
fi

# Step 1: Install Scribe
echo "📦 Step 1: Installing Scribe..."
composer require --dev knuckleswtf/scribe

if [ $? -eq 0 ]; then
    echo "✅ Scribe installed successfully"
else
    echo "❌ Failed to install Scribe"
    exit 1
fi

echo ""

# Step 2: Publish configuration
echo "⚙️  Step 2: Publishing configuration..."
php artisan vendor:publish --tag=scribe-config --force

if [ $? -eq 0 ]; then
    echo "✅ Configuration published to config/scribe.php"
else
    echo "❌ Failed to publish configuration"
    exit 1
fi

echo ""

# Step 3: Configure Scribe
echo "🔧 Step 3: Configuring Scribe..."

# Backup original config if exists
if [ -f "config/scribe.php" ]; then
    cp config/scribe.php config/scribe.php.backup
    echo "📋 Original config backed up to config/scribe.php.backup"
fi

# Update config with our settings
cat > config/scribe.php << 'EOF'
<?php

return [
    'theme' => 'default',
    
    'title' => env('API_DOC_TITLE', 'E-Commerce Platform API'),
    
    'description' => 'Professional multi-tenant e-commerce API for building custom storefronts.',
    
    'base_url' => env('APP_URL', 'http://localhost:8000'),
    
    'routes' => [
        [
            'match' => [
                'prefixes' => ['v1/*', 'api/v1/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => [
                'v1/internal/*',
                'api/v1/internal/*',
            ],
        ],
    ],
    
    'type' => 'laravel',
    
    'static' => [
        'output_path' => 'public/docs',
    ],
    
    'laravel' => [
        'add_routes' => true,
        'docs_url' => '/docs',
    ],
    
    'try_it_out' => [
        'enabled' => true,
        'base_url' => env('SCRIBE_TRY_IT_OUT_URL', null),
        'use_csrf' => false,
    ],
    
    'auth' => [
        'enabled' => true,
        'default' => false,
        'in' => 'bearer',
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_TOKEN'),
        'placeholder' => 'YOUR_TOKEN_HERE',
        'extra_info' => 'Obtain your API token from the admin panel.',
    ],
    
    'intro_text' => <<<INTRO
Welcome to the E-Commerce Platform API documentation.

## Getting Started

1. Obtain your Store ID and API credentials from the admin panel
2. Include your Store ID in the `X-Store-ID` header for all requests
3. Authenticate using Bearer token: `Authorization: Bearer YOUR_TOKEN`

## Rate Limiting

- **Authenticated:** 60 requests/minute
- **Unauthenticated:** 10 requests/minute

## Multi-Tenancy

All endpoints are tenant-aware. Include `X-Store-ID` header to scope data to your store.
INTRO,
    
    'example_languages' => [
        'bash',
        'javascript',
        'php',
        'python',
    ],
    
    'postman' => [
        'enabled' => true,
        'overrides' => [
            'info.version' => '1.0.0',
        ],
    ],
    
    'openapi' => [
        'enabled' => true,
    ],
    
    'groups' => [
        'order' => [
            'Authentication',
            'Store Management',
            'Products',
            'Categories',
            'Inventory',
            'Promotions',
            'Coupons',
            'Offers',
            'Cart',
            'Orders',
            'Customers',
            'Payments',
            'Shipping',
            'Analytics',
        ],
    ],
];
EOF

echo "✅ Scribe configured successfully"

echo ""

# Step 4: Add .env variables
echo "📝 Step 4: Adding environment variables..."

if ! grep -q "SCRIBE_TRY_IT_OUT_URL" .env 2>/dev/null; then
    cat >> .env << EOF

# API Documentation
SCRIBE_TRY_IT_OUT_URL=\${APP_URL}
SCRIBE_AUTH_TOKEN=
API_DOC_TITLE="E-Commerce Platform API"
EOF
    echo "✅ Added SCRIBE variables to .env"
else
    echo "ℹ️  SCRIBE variables already exist in .env"
fi

echo ""

# Step 5: Create example controller
echo "📄 Step 5: Creating example documented controller..."

mkdir -p app/Http/Controllers/Api/V1

cat > app/Http/Controllers/Api/V1/ExampleController.php << 'EOF'
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @group Example
 * 
 * Example API endpoints demonstrating Scribe documentation.
 */
class ExampleController extends Controller
{
    /**
     * Get example data
     * 
     * Returns example data to demonstrate API documentation.
     * 
     * @authenticated
     * 
     * @queryParam filter string Filter results. Example: active
     * @queryParam limit integer Maximum results to return. Example: 10
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id": 1, "name": "Example Item", "status": "active"}
     *   ],
     *   "meta": {
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request)
    {
        return response()->json([
            'data' => [
                ['id' => 1, 'name' => 'Example Item', 'status' => 'active']
            ],
            'meta' => [
                'total' => 1
            ]
        ]);
    }

    /**
     * Get single item
     * 
     * @urlParam id integer required Item ID. Example: 1
     * 
     * @response 200 {"data": {"id": 1, "name": "Example Item"}}
     * @response 404 {"message": "Not found"}
     */
    public function show($id)
    {
        return response()->json([
            'data' => ['id' => (int)$id, 'name' => 'Example Item']
        ]);
    }
}
EOF

echo "✅ Created app/Http/Controllers/Api/V1/ExampleController.php"

echo ""

# Step 6: Add example route
echo "🛤️  Step 6: Adding example routes..."

if [ -f "routes/api.php" ]; then
    if ! grep -q "ExampleController" routes/api.php 2>/dev/null; then
        cat >> routes/api.php << 'EOF'

// Example API routes (for documentation)
Route::prefix('v1')->group(function () {
    Route::get('examples', [\App\Http\Controllers\Api\V1\ExampleController::class, 'index']);
    Route::get('examples/{id}', [\App\Http\Controllers\Api\V1\ExampleController::class, 'show']);
});
EOF
        echo "✅ Added example routes to routes/api.php"
    else
        echo "ℹ️  Example routes already exist"
    fi
else
    echo "⚠️  routes/api.php not found - skip adding routes"
fi

echo ""

# Step 7: Generate documentation
echo "🚀 Step 7: Generating initial documentation..."
php artisan scribe:generate

if [ $? -eq 0 ]; then
    echo "✅ Documentation generated successfully"
else
    echo "❌ Failed to generate documentation"
    exit 1
fi

echo ""

# Step 8: Setup Git hook (optional)
echo "🎣 Step 8: Setting up Git hook for auto-generation..."

if [ -d ".git" ]; then
    cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
# Auto-generate API docs when API controllers change

if git diff --cached --name-only | grep -E '^app/Http/Controllers/Api/'; then
    echo "🔄 Regenerating API documentation..."
    php artisan scribe:generate --no-interaction
    
    # Add generated docs to commit
    git add public/docs
    
    echo "✅ API documentation updated"
fi
EOF
    
    chmod +x .git/hooks/pre-commit
    echo "✅ Git pre-commit hook installed"
    echo "   Docs will auto-generate when API controllers change"
else
    echo "ℹ️  Not a git repository - skipping hook setup"
fi

echo ""

# Success summary
echo "========================================="
echo "✨ Setup Complete!"
echo "========================================="
echo ""
echo "📚 Documentation available at:"
echo "   http://localhost:8000/docs"
echo ""
echo "🔧 Next steps:"
echo "   1. Start your Laravel server: php artisan serve"
echo "   2. Visit http://localhost:8000/docs"
echo "   3. Document your controllers using PHPDoc annotations"
echo "   4. Regenerate docs: php artisan scribe:generate"
echo ""
echo "📖 Resources:"
echo "   - Quick Reference: docs/API-DOCS-QUICK-REFERENCE.md"
echo "   - Full Guide: docs/16-api-documentation-system.md"
echo "   - Scribe Docs: https://scribe.knuckles.wtf"
echo ""
echo "💡 Tip: Check app/Http/Controllers/Api/V1/ExampleController.php"
echo "   for a documented controller example"
echo ""
