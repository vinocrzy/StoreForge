@echo off
REM API Documentation Setup Script for Windows
REM Installs and configures Laravel Scribe for automatic API documentation

setlocal enabledelayedexpansion

echo =========================================
echo API Documentation Setup (Laravel Scribe)
echo =========================================
echo.

REM Check if we're in the Laravel backend directory
if not exist "artisan" (
    echo [ERROR] artisan file not found.
    echo         Please run this script from your Laravel backend directory.
    exit /b 1
)

REM Step 1: Install Scribe
echo [Step 1] Installing Scribe...
call composer require --dev knuckleswtf/scribe

if !errorlevel! neq 0 (
    echo [ERROR] Failed to install Scribe
    exit /b 1
)
echo [OK] Scribe installed successfully
echo.

REM Step 2: Publish configuration
echo [Step 2] Publishing configuration...
php artisan vendor:publish --tag=scribe-config --force

if !errorlevel! neq 0 (
    echo [ERROR] Failed to publish configuration
    exit /b 1
)
echo [OK] Configuration published to config\scribe.php
echo.

REM Step 3: Configure Scribe
echo [Step 3] Configuring Scribe...

if exist "config\scribe.php" (
    copy config\scribe.php config\scribe.php.backup >nul
    echo [OK] Original config backed up to config\scribe.php.backup
)

REM Create optimized Scribe configuration
(
echo ^<?php
echo.
echo return [
echo     'theme' =^> 'default',
echo.    
echo     'title' =^> env^('API_DOC_TITLE', 'E-Commerce Platform API'^),
echo.    
echo     'description' =^> 'Professional multi-tenant e-commerce API for building custom storefronts.',
echo.    
echo     'base_url' =^> env^('APP_URL', 'http://localhost:8000'^),
echo.    
echo     'routes' =^> [
echo         [
echo             'match' =^> [
echo                 'prefixes' =^> ['v1/*', 'api/v1/*'],
echo                 'domains' =^> ['*'],
echo             ],
echo             'include' =^> [],
echo             'exclude' =^> [
echo                 'v1/internal/*',
echo                 'api/v1/internal/*',
echo             ],
echo         ],
echo     ],
echo.    
echo     'type' =^> 'laravel',
echo.    
echo     'static' =^> [
echo         'output_path' =^> 'public/docs',
echo     ],
echo.    
echo     'laravel' =^> [
echo         'add_routes' =^> true,
echo         'docs_url' =^> '/docs',
echo     ],
echo.    
echo     'try_it_out' =^> [
echo         'enabled' =^> true,
echo         'base_url' =^> env^('SCRIBE_TRY_IT_OUT_URL', null^),
echo         'use_csrf' =^> false,
echo     ],
echo.    
echo     'auth' =^> [
echo         'enabled' =^> true,
echo         'default' =^> false,
echo         'in' =^> 'bearer',
echo         'name' =^> 'Authorization',
echo         'use_value' =^> env^('SCRIBE_AUTH_TOKEN'^),
echo         'placeholder' =^> 'YOUR_TOKEN_HERE',
echo         'extra_info' =^> 'Obtain your API token from the admin panel.',
echo     ],
echo.    
echo     'intro_text' =^> ^^^<^^^<^^^<INTRO
echo Welcome to the E-Commerce Platform API documentation.
echo.
echo ## Getting Started
echo.
echo 1. Obtain your Store ID and API credentials from the admin panel
echo 2. Include your Store ID in the `X-Store-ID` header for all requests
echo 3. Authenticate using Bearer token: `Authorization: Bearer YOUR_TOKEN`
echo.
echo ## Rate Limiting
echo.
echo - **Authenticated:** 60 requests/minute
echo - **Unauthenticated:** 10 requests/minute
echo.
echo ## Multi-Tenancy
echo.
echo All endpoints are tenant-aware. Include `X-Store-ID` header to scope data to your store.
echo INTRO,
echo.    
echo     'example_languages' =^> [
echo         'bash',
echo         'javascript',
echo         'php',
echo         'python',
echo     ],
echo.    
echo     'postman' =^> [
echo         'enabled' =^> true,
echo         'overrides' =^> [
echo             'info.version' =^> '1.0.0',
echo         ],
echo     ],
echo.    
echo     'openapi' =^> [
echo         'enabled' =^> true,
echo     ],
echo.    
echo     'groups' =^> [
echo         'order' =^> [
echo             'Authentication',
echo             'Store Management',
echo             'Products',
echo             'Categories',
echo             'Inventory',
echo             'Promotions',
echo             'Coupons',
echo             'Offers',
echo             'Cart',
echo             'Orders',
echo             'Customers',
echo             'Payments',
echo             'Shipping',
echo             'Analytics',
echo         ],
echo     ],
echo ];
) > config\scribe.php

echo [OK] Scribe configured successfully
echo.

REM Step 4: Add .env variables
echo [Step 4] Adding environment variables...

findstr /C:"SCRIBE_TRY_IT_OUT_URL" .env >nul 2>&1
if !errorlevel! neq 0 (
    (
    echo.
    echo # API Documentation
    echo SCRIBE_TRY_IT_OUT_URL=${APP_URL}
    echo SCRIBE_AUTH_TOKEN=
    echo API_DOC_TITLE="E-Commerce Platform API"
    ) >> .env
    echo [OK] Added SCRIBE variables to .env
) else (
    echo [INFO] SCRIBE variables already exist in .env
)
echo.

REM Step 5: Create example controller
echo [Step 5] Creating example documented controller...

if not exist "app\Http\Controllers\Api\V1" mkdir app\Http\Controllers\Api\V1

(
echo ^<?php
echo.
echo namespace App\Http\Controllers\Api\V1;
echo.
echo use App\Http\Controllers\Controller;
echo use Illuminate\Http\Request;
echo.
echo /**
echo  * @group Example
echo  * 
echo  * Example API endpoints demonstrating Scribe documentation.
echo  */
echo class ExampleController extends Controller
echo {
echo     /**
echo      * Get example data
echo      * 
echo      * Returns example data to demonstrate API documentation.
echo      * 
echo      * @authenticated
echo      * 
echo      * @queryParam filter string Filter results. Example: active
echo      * @queryParam limit integer Maximum results to return. Example: 10
echo      * 
echo      * @response 200 scenario="Success" {
echo      *   "data": [
echo      *     {"id": 1, "name": "Example Item", "status": "active"}
echo      *   ],
echo      *   "meta": {
echo      *     "total": 1
echo      *   }
echo      * }
echo      */
echo     public function index^(Request $request^)
echo     {
echo         return response^(^)-^>json^([
echo             'data' =^> [
echo                 ['id' =^> 1, 'name' =^> 'Example Item', 'status' =^> 'active']
echo             ],
echo             'meta' =^> [
echo                 'total' =^> 1
echo             ]
echo         ]^);
echo     }
echo.
echo     /**
echo      * Get single item
echo      * 
echo      * @urlParam id integer required Item ID. Example: 1
echo      * 
echo      * @response 200 {"data": {"id": 1, "name": "Example Item"}}
echo      * @response 404 {"message": "Not found"}
echo      */
echo     public function show^($id^)
echo     {
echo         return response^(^)-^>json^([
echo             'data' =^> ['id' =^> ^(int^)$id, 'name' =^> 'Example Item']
echo         ]^);
echo     }
echo }
) > app\Http\Controllers\Api\V1\ExampleController.php

echo [OK] Created app\Http\Controllers\Api\V1\ExampleController.php
echo.

REM Step 6: Add example routes
echo [Step 6] Adding example routes...

if exist "routes\api.php" (
    findstr /C:"ExampleController" routes\api.php >nul 2>&1
    if !errorlevel! neq 0 (
        (
        echo.
        echo // Example API routes ^(for documentation^)
        echo Route::prefix^('v1'^)-^>group^(function ^(^) {
        echo     Route::get^('examples', [\App\Http\Controllers\Api\V1\ExampleController::class, 'index']^);
        echo     Route::get^('examples/{id}', [\App\Http\Controllers\Api\V1\ExampleController::class, 'show']^);
        echo }^);
        ) >> routes\api.php
        echo [OK] Added example routes to routes\api.php
    ) else (
        echo [INFO] Example routes already exist
    )
) else (
    echo [WARN] routes\api.php not found - skip adding routes
)
echo.

REM Step 7: Generate documentation
echo [Step 7] Generating initial documentation...
php artisan scribe:generate

if !errorlevel! neq 0 (
    echo [ERROR] Failed to generate documentation
    exit /b 1
)
echo [OK] Documentation generated successfully
echo.

REM Success summary
echo =========================================
echo Setup Complete!
echo =========================================
echo.
echo Documentation available at:
echo    http://localhost:8000/docs
echo.
echo Next steps:
echo    1. Start your Laravel server: php artisan serve
echo    2. Visit http://localhost:8000/docs
echo    3. Document your controllers using PHPDoc annotations
echo    4. Regenerate docs: php artisan scribe:generate
echo.
echo Resources:
echo    - Quick Reference: ..\docs\API-DOCS-QUICK-REFERENCE.md
echo    - Full Guide: ..\docs\16-api-documentation-system.md
echo    - Scribe Docs: https://scribe.knuckles.wtf
echo.
echo Tip: Check app\Http\Controllers\Api\V1\ExampleController.php
echo      for a documented controller example
echo.

pause
