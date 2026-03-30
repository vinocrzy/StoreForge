#!/usr/bin/env pwsh
# Laravel Backend Setup Script
# This script automates the Laravel backend setup process

$ErrorActionPreference = "Stop"

# Add composer to PATH if exists
if (Test-Path "$env:USERPROFILE\bin\composer.bat") {
    $env:Path = "$env:USERPROFILE\bin;" + $env:Path
}

Write-Host "`n=========================================" -ForegroundColor Cyan
Write-Host "|   E-Commerce Platform - Laravel Backend Setup         |" -ForegroundColor Cyan
Write-Host "=========================================`n" -ForegroundColor Cyan

# Get script directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent (Split-Path -Parent $scriptDir)
$backendDir = Join-Path $projectRoot "platform\backend"
$backendCustomDir = Join-Path $projectRoot "platform\backend_custom"

# Check prerequisites
Write-Host "Checking prerequisites..." -ForegroundColor Yellow

# Check PHP
try {
    $phpVersion = php -r "echo PHP_VERSION;"
    Write-Host "[OK] PHP $phpVersion detected" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] PHP is not installed or not in PATH!" -ForegroundColor Red
    Write-Host "  Please install PHP 8.2+ from https://windows.php.net/download/" -ForegroundColor Yellow
    exit 1
}

# Check Composer
try {
    $composerVersion = composer --version 2>&1 | Select-String -Pattern "Composer version" | ForEach-Object { $_.Line }
    Write-Host "[OK] Composer detected: $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Composer is not installed!" -ForegroundColor Red
    Write-Host "`nPlease install Composer first:" -ForegroundColor Yellow
    Write-Host "  1. Visit: https://getcomposer.org/download/" -ForegroundColor Cyan
    Write-Host "  2. Download Composer-Setup.exe" -ForegroundColor Cyan
    Write-Host "  3. Run the installer" -ForegroundColor Cyan
    Write-Host "  4. Restart your terminal" -ForegroundColor Cyan
    Write-Host "  5. Run this script again`n" -ForegroundColor Cyan
    exit 1
}

Write-Host "`nAll prerequisites met!`n" -ForegroundColor Green

# Ask user what to do
Write-Host "Setup options:" -ForegroundColor Cyan
Write-Host "  [1] Fresh install (create new Laravel project)"
Write-Host "  [2] Install dependencies only (composer.json exists)"
Write-Host "  [3] Cancel"
$choice = Read-Host "`nSelect option (1-3)"

if ($choice -eq "3") {
    Write-Host "Setup cancelled." -ForegroundColor Yellow
    exit 0
}

# Navigate to platform directory
Set-Location (Join-Path $projectRoot "platform")

if ($choice -eq "1") {
    Write-Host "`n[1/10] Creating fresh Laravel 11 project..." -ForegroundColor Cyan
    
    # Backup existing backend if it exists
    if (Test-Path $backendDir) {
        Write-Host "  Backing up existing backend code..." -ForegroundColor Yellow
        if (Test-Path $backendCustomDir) {
            Remove-Item $backendCustomDir -Recurse -Force
        }
        Move-Item $backendDir $backendCustomDir
        Write-Host "  [OK] Backed up to backend_custom" -ForegroundColor Green
    }
    
    # Create fresh Laravel project
    Write-Host "  Creating Laravel project (this may take a few minutes)..." -ForegroundColor Yellow
    composer create-project laravel/laravel backend "11.*" --prefer-dist
    
    # Restore custom code if backup exists
    if (Test-Path $backendCustomDir) {
        Write-Host "  Restoring custom code..." -ForegroundColor Yellow
        
        # Copy app files
        if (Test-Path "$backendCustomDir\app") {
            Copy-Item -Path "$backendCustomDir\app\*" -Destination "$backendDir\app\" -Recurse -Force
            Write-Host "    [OK] Restored app/" -ForegroundColor Green
        }
        
        # Copy database files
        if (Test-Path "$backendCustomDir\database") {
            Copy-Item -Path "$backendCustomDir\database\*" -Destination "$backendDir\database\" -Recurse -Force
            Write-Host "    [OK] Restored database/" -ForegroundColor Green
        }
        
        # Copy tests
        if (Test-Path "$backendCustomDir\tests") {
            Copy-Item -Path "$backendCustomDir\tests\*" -Destination "$backendDir\tests\" -Recurse -Force
            Write-Host "    [OK] Restored tests/" -ForegroundColor Green
        }
        
        # Remove backup
        Remove-Item $backendCustomDir -Recurse -Force
        Write-Host "  [OK] Custom code restored" -ForegroundColor Green
    }
    
    Write-Host "[OK] Laravel project created`n" -ForegroundColor Green
}

# Navigate to backend directory
Set-Location $backendDir

if ($choice -eq "2") {
    Write-Host "`n[1/10] Installing Laravel dependencies..." -ForegroundColor Cyan
    composer install
    Write-Host "[OK] Dependencies installed`n" -ForegroundColor Green
}

# Setup environment
Write-Host "[2/10] Setting up environment..." -ForegroundColor Cyan
if (!(Test-Path .env)) {
    Copy-Item .env.example .env
    Write-Host "  [OK] Created .env file" -ForegroundColor Green
}
php artisan key:generate
Write-Host "[OK] Environment configured`n" -ForegroundColor Green

# Install core packages
Write-Host "[3/10] Installing Laravel Sanctum..." -ForegroundColor Cyan
composer require laravel/sanctum
Write-Host "[OK] Sanctum installed`n" -ForegroundColor Green

Write-Host "[4/10] Installing Spatie Permission..." -ForegroundColor Cyan
composer require spatie/laravel-permission
Write-Host "[OK] Permission package installed`n" -ForegroundColor Green

Write-Host "[5/10] Installing API utilities..." -ForegroundColor Cyan
composer require spatie/laravel-query-builder
Write-Host "[OK] Query builder installed`n" -ForegroundColor Green

Write-Host "[6/10] Installing development tools..." -ForegroundColor Cyan
composer require --dev laravel/pint phpstan/phpstan
Write-Host "[OK] Dev tools installed`n" -ForegroundColor Green

Write-Host "[7/10] Installing API documentation (Scribe)..." -ForegroundColor Cyan
composer require --dev knuckleswtf/scribe
Write-Host "[OK] Scribe installed`n" -ForegroundColor Green

# Publish vendor files
Write-Host "[8/10] Publishing vendor configurations..." -ForegroundColor Cyan
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --quiet
Write-Host "  [OK] Sanctum published" -ForegroundColor Green

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --quiet
Write-Host "  [OK] Permission published" -ForegroundColor Green

php artisan vendor:publish --tag=scribe-config --quiet
Write-Host "  [OK] Scribe published" -ForegroundColor Green

Write-Host "[OK] Vendor files published`n" -ForegroundColor Green

# Configure helpers autoload
Write-Host "[9/10] Configuring autoload..." -ForegroundColor Cyan

$composerJson = Get-Content composer.json -Raw | ConvertFrom-Json
if (!$composerJson.autoload.files) {
    $composerJson.autoload | Add-Member -MemberType NoteProperty -Name "files" -Value @("app/helpers.php")
} elseif ($composerJson.autoload.files -notcontains "app/helpers.php") {
    $composerJson.autoload.files += "app/helpers.php"
}
$composerJson | ConvertTo-Json -Depth 10 | Set-Content composer.json

composer dump-autoload --quiet
Write-Host "[OK] Autoload configured`n" -ForegroundColor Green

# Create routes/api.php if it doesn't exist
Write-Host "[10/10] Configuring API routes..." -ForegroundColor Cyan
$apiRoutesContent = @"
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/revoke-all', [AuthController::class, 'revokeAllTokens']);
    
    // Tenant-scoped routes
    Route::middleware(['tenant'])->group(function () {
        // Product routes will be added here
        // Route::apiResource('products', ProductController::class);
    });
});
"@

$apiRoutesContent | Out-File -FilePath "routes\api.php" -Encoding UTF8 -Force
Write-Host "[OK] API routes configured`n" -ForegroundColor Green

# Summary
Write-Host "`n=========================================" -ForegroundColor Green
Write-Host "|              Setup Complete! [OK]                         |" -ForegroundColor Green
Write-Host "=========================================`n" -ForegroundColor Green

Write-Host "Next steps:`n" -ForegroundColor Cyan

Write-Host "1. Configure database in .env file:" -ForegroundColor Yellow
Write-Host "   DB_CONNECTION=mysql" -ForegroundColor Gray
Write-Host "   DB_HOST=127.0.0.1" -ForegroundColor Gray
Write-Host "   DB_PORT=3306" -ForegroundColor Gray
Write-Host "   DB_DATABASE=ecommerce" -ForegroundColor Gray
Write-Host "   DB_USERNAME=root" -ForegroundColor Gray
Write-Host "   DB_PASSWORD=your_password`n" -ForegroundColor Gray

Write-Host "2. Create database:" -ForegroundColor Yellow
Write-Host "   mysql -u root -p -e `"CREATE DATABASE ecommerce;`"`n" -ForegroundColor Gray

Write-Host "3. Run migrations:" -ForegroundColor Yellow
Write-Host "   php artisan migrate`n" -ForegroundColor Gray

Write-Host "4. Run tests:" -ForegroundColor Yellow
Write-Host "   php artisan test`n" -ForegroundColor Gray

Write-Host "5. Start development server:" -ForegroundColor Yellow
Write-Host "   php artisan serve`n" -ForegroundColor Gray

Write-Host "6. Generate API documentation:" -ForegroundColor Yellow
Write-Host "   php artisan scribe:generate`n" -ForegroundColor Gray

Write-Host "Resources:" -ForegroundColor Cyan
Write-Host "  • Backend: http://localhost:8000" -ForegroundColor Gray
Write-Host "  • API Docs: http://localhost:8000/docs" -ForegroundColor Gray
Write-Host "  • Setup Guide: SETUP.md" -ForegroundColor Gray
Write-Host "  • Progress: ..\..\PROGRESS.md" -ForegroundColor Gray
Write-Host "  • Documentation: ..\..\docs\`n" -ForegroundColor Gray

Write-Host "For issues, check SETUP.md or docs/02-backend-architecture.md`n" -ForegroundColor Yellow

