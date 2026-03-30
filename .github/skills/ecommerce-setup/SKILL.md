---
name: ecommerce-setup
description: 'Setup and configure the e-commerce platform development environment. Use when: initial project setup, creating new repositories, setting up Laravel backend, React admin panel, Next.js storefront, creating client storefronts, or troubleshooting environment issues.'
argument-hint: 'Specify "backend", "admin", "storefront", "client", or "full" for complete setup'
---

# E-Commerce Platform Setup

## Purpose

Comprehensive setup guide for the multi-tenant e-commerce platform including backend (Laravel), admin panel (React), storefront template (Next.js), and client storefront creation.

## When to Use

- Initial project setup (first time)
- Setting up new development environments
- Creating new client storefronts
- Onboarding new developers
- Troubleshooting environment issues
- Repository structure setup

## Architecture Overview

```
c:\poc\e-com\
├── platform/              # Git repo 1: Shared backend + admin
│   ├── backend/          # Laravel 11 API
│   └── admin-panel/      # React 18 TypeScript SPA
├── storefront-template/   # Git repo 2: Base template
│   └── ...               # Next.js 14 template
├── client-fashion/        # Git repo 3: Client storefront
├── client-electronics/    # Git repo 4: Another client
└── docs/                  # Documentation (in root)
```

## Prerequisites

Ensure you have installed:

- **PHP** 8.2+ (verified: 8.2.12 working)
- **Composer** 2.x (verified: 2.9.5 working)
- **Node.js** 18+
- **npm** or **yarn**
- **MySQL** 8.0+ or **PostgreSQL** 14+
- **Redis** 7+ (optional for development)
- **Git**

### Common PHP Configuration Issues (Windows/XAMPP)

**Fix extension paths in php.ini:**
```ini
; Change from:
extension_dir = "\xampp\php\ext"
; To (with full path):
extension_dir = "C:\soft\xampp\php\ext"

; Also update browscap:
browscap = "C:\soft\xampp\php\extras\browscap.ini"
```

**Enable required extensions:**
```ini
extension=zip          ; CRITICAL for fast Composer installs
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_mysql
```

**Install Composer globally:**
```powershell
# Download composer.phar
Invoke-WebRequest -Uri "https://getcomposer.org/download/latest-stable/composer.phar" -OutFile "composer.phar"

# Create global directory and install
New-Item -ItemType Directory -Force -Path "$env:USERPROFILE\bin"
Move-Item composer.phar "$env:USERPROFILE\bin\composer.phar" -Force

# Create batch wrapper
'@php "%USERPROFILE%\bin\composer.phar" %*' | Out-File -FilePath "$env:USERPROFILE\bin\composer.bat" -Encoding ASCII

# Add to PATH (current session)
$env:Path = "$env:USERPROFILE\bin;" + $env:Path

# Verify
php $env:USERPROFILE\bin\composer.phar --version
```

## Quick Setup Scripts

### 1. Setup Repository Structure

```bash
cd c:\poc\e-com
.\scripts\setup-repos.bat  # Windows
# or
bash scripts/setup-repos.sh  # Linux/Mac
```

This creates:
- `platform/` directory (for backend + admin)
- `storefront-template/` directory
- Helper scripts

### 2. Setup API Documentation

```bash
cd platform\backend
..\..\scripts\setup-api-docs.bat  # Windows
# or
bash ../../scripts/setup-api-docs.sh  # Linux/Mac
```

This installs and configures Laravel Scribe for automatic API documentation.

## Manual Setup Steps

### Backend Setup (Laravel)

#### Step 1: Create Laravel Project (Optimized)

```bash
cd platform

# Use --prefer-dist for faster zip downloads (requires extension=zip)
composer create-project laravel/laravel backend "11.*" --prefer-dist --no-interaction

# Or use php composer.phar if composer not in PATH
php $env:USERPROFILE\bin\composer.phar create-project laravel/laravel backend "11.*" --prefer-dist
```

**Expected output**: Laravel 11.x installed in 1-2 minutes with zip enabled.

#### Step 2: Install Core Packages

```bash
cd backend

# Install all at once for efficiency
composer require laravel/sanctum spatie/laravel-permission spatie/laravel-query-builder --no-interaction

# Install dev tools
composer require --dev knuckleswtf/scribe phpstan/phpstan laravel/pint --no-interaction

# Publish vendor configs
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --tag=scribe-config
```

#### Step 3: Configure Environment

```bash
# .env already created by Laravel installer
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="E-Commerce Platform"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=your_password_here

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:5173
SESSION_DOMAIN=localhost
```

#### Step 4: Configure Autoload for Helpers

Add to `composer.json` autoload section:

```json
"files": [
    "app/helpers.php"
]
```

Then run:
```bash
composer dump-autoload
```

#### Step 5: Register API Routes and Middleware

Edit `bootstrap/app.php` to add API routes and tenant middleware:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  // Add API routes
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\SetTenantFromHeader::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

#### Step 6: Setup Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate
```

#### Step 7: Verify Installation

```bash
# Test Laravel
php artisan --version
# Should show: Laravel Framework 11.x.x

# List routes
php artisan route:list

# Test autoload
php artisan tinker
>>> tenant()
>>> exit
```

#### Step 8: Start Development Server

```bash
# Start Laravel server
php artisan serve
# Backend available at: http://localhost:8000

# In separate terminal: Start queue worker (optional)
php artisan queue:work
```

## Actual Implementation Structure (Completed)

After setup, your backend should have:

### Database Migrations (4 files)
- `2024_01_01_000001_create_stores_table.php` - Store configuration
- `2024_01_01_000002_create_users_table.php` - Users with soft deletes
- `2024_01_01_000003_create_store_user_table.php` - Many-to-many pivot with roles
- `2024_01_01_000004_create_personal_access_tokens_table.php` - Sanctum tokens

### Models (3 + trait)
- `app/Models/Store.php` - Store with settings management
- `app/Models/User.php` - User with HasApiTokens, SoftDeletes, store relationships
- `app/Models/TenantModel.php` - Base class for all tenant-aware models
- `app/Models/Concerns/HasTenantScope.php` - Tenant global scope trait

### Middleware
- `app/Http/Middleware/SetTenantFromHeader.php` - Validates X-Store-ID header

### Controllers
- `app/Http/Controllers/Api/V1/AuthController.php` - Login, logout, me, revoke tokens

### Helpers
- `app/helpers.php` - `tenant()`, `tenant_id()`, `has_tenant()` functions

### Routes
- `routes/api.php` - API routes with tenant middleware group

### Tests
- `tests/Feature/TenantIsolationTest.php` - Security tests

### Factories
- `database/factories/StoreFactory.php` - Store test data generation

## Common Issues & Solutions

### Issue: Composer not found after installation

**Solution:**
```powershell
# Add to current session PATH
$env:Path = "$env:USERPROFILE\bin;" + $env:Path

# Or use direct path
php $env:USERPROFILE\bin\composer.phar <command>
```

### Issue: PHP extensions not loading

**Symptoms**: Warnings about missing extensions (bz2, curl, mbstring, etc.)

**Solution**: Check php.ini paths are absolute:
```ini
extension_dir = "C:\full\path\to\xampp\php\ext"  # Not "\xampp\php\ext"
```

### Issue: Slow Composer installs

**Solution**: Enable zip extension in php.ini:
```ini
extension=zip
```

### Issue: Artisan commands fail with "vendor/autoload.php not found"

**Solution**: Wait for `composer install` to fully complete, or run:
```bash
composer install --no-scripts
composer dump-autoload
```
php artisan horizon
```

**Backend available at**: `http://localhost:8000`
**API Docs**: `http://localhost:8000/docs`
**Horizon Dashboard**: `http://localhost:8000/horizon`

### Admin Panel Setup (React)

#### Step 1: Create React Project

```bash
cd platform
npm create vite@latest admin-panel -- --template react-ts
cd admin-panel
```

#### Step 2: Install Dependencies

```bash
# Core dependencies
npm install

# UI Library
npm install antd @ant-design/icons

# Routing
npm install react-router-dom

# State Management
npm install @reduxjs/toolkit react-redux

# API Client
npm install axios

# Form Handling
npm install react-hook-form zod @hookform/resolvers

# Utilities
npm install date-fns clsx
```

#### Step 3: Configure Environment

Create `.env.local`:

```env
VITE_API_URL=http://localhost:8000/api/v1
VITE_APP_NAME=E-Commerce Admin
```

#### Step 4: Start Admin Panel

```bash
npm run dev
```

**Admin Panel available at**: `http://localhost:5173`

### Storefront Template Setup (Next.js)

#### Step 1: Create Next.js Project

```bash
cd c:\poc\e-com
npx create-next-app@latest storefront-template --typescript --tailwind --app --src-dir
cd storefront-template
```

#### Step 2: Install Dependencies

```bash
# UI Components
npm install @headlessui/react @heroicons/react

# API Client
npm install axios

# Utilities
npm install clsx
```

#### Step 3: Configure Environment

Create `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_STORE_ID=1
NEXT_PUBLIC_STORE_NAME=Template Store
```

#### Step 4: Configure Static Export

Edit `next.config.js`:

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true,
  },
};

module.exports = nextConfig;
```

#### Step 5: Start Storefront

```bash
npm run dev
```

**Storefront available at**: `http://localhost:3000`

## Create Client Storefront

Use the helper script to create a new client storefront:

```bash
# From project root
cd c:\poc\e-com

# Windows
scripts\create-client-store.bat "Fashion Store" 1

# Linux/Mac
bash scripts/create-client-store.sh "Fashion Store" 1
```

This will:
1. Copy storefront template
2. Initialize new Git repository
3. Configure `.env.local` with store ID and name
4. Create initial commit

Then:

```bash
cd client-fashion-store
npm install
npm run dev
```

## Verification Checklist

After setup, verify everything works:

### Backend (Laravel)
- [ ] `php artisan serve` starts without errors
- [ ] Visit `http://localhost:8000` - see Laravel welcome
- [ ] Visit `http://localhost:8000/docs` - see API documentation
- [ ] Visit `http://localhost:8000/horizon` - see Horizon dashboard
- [ ] Database connection works: `php artisan migrate:status`
- [ ] Redis connection works: `php artisan tinker` → `Cache::get('test')`

### Admin Panel (React)
- [ ] `npm run dev` starts without errors
- [ ] Visit `http://localhost:5173` - see admin panel
- [ ] Console shows no errors
- [ ] Can make API calls to backend

### Storefront (Next.js)
- [ ] `npm run dev` starts without errors
- [ ] Visit `http://localhost:3000` - see storefront
- [ ] `npm run build` completes successfully
- [ ] Static export generates `out/` directory

## Common Issues

### Port Already in Use

**Backend (8000):**
```bash
# Find process
netstat -ano | findstr :8000

# Kill process (Windows)
taskkill /PID <PID> /F

# Or use different port
php artisan serve --port=8001
```

**Admin Panel (5173):**
```bash
# Kill process
taskkill /PID <PID> /F

# Or change port in vite.config.ts
server: { port: 5174 }
```

### Database Connection Failed

```bash
# Test MySQL connection
mysql -u root -p

# Check MySQL service is running (Windows)
net start MySQL80

# Verify credentials in .env
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Redis Connection Failed

```bash
# Install Redis (Windows - use WSL or Redis for Windows)
# Or use array driver for local development
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

### Composer Memory Issues

```bash
# Increase memory limit
php -d memory_limit=-1 C:\path\to\composer.phar install
```

### Node/NPM Issues

```bash
# Clear cache
npm cache clean --force

# Delete node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

## Directory Structure After Setup

```
c:\poc\e-com\
├── .github\
│   ├── copilot-instructions.md    # Project guidelines
│   └── skills\                     # Copilot skills
│       ├── ecommerce-api-docs\
│       ├── ecommerce-setup\
│       └── ecommerce-tenancy\
├── docs\                           # All documentation
│   ├── 01-system-architecture.md
│   ├── 02-backend-architecture.md
│   ├── 03-database-schema.md
│   ├── 04-api-design.md
│   ├── 11-getting-started.md
│   ├── 16-api-documentation-system.md
│   └── ...
├── platform\                       # Shared platform (Git repo)
│   ├── backend\                    # Laravel API
│   │   ├── app\
│   │   ├── config\
│   │   ├── database\
│   │   ├── routes\
│   │   └── public\docs\           # Generated API docs
│   └── admin-panel\               # React Admin
│       ├── src\
│       ├── public\
│       └── package.json
├── storefront-template\           # Template (Git repo)
│   ├── src\
│   ├── public\
│   └── package.json
├── client-*/                      # Client stores (separate Git repos)
├── scripts\                       # Setup scripts
│   ├── setup-repos.bat
│   ├── setup-repos.sh
│   ├── setup-api-docs.bat
│   ├── setup-api-docs.sh
│   ├── create-client-store.bat
│   └── create-client-store.sh
└── README.md                      # Project overview
```

## Next Steps After Setup

1. **Review Documentation**
   - [System Architecture](../../docs/01-system-architecture.md)
   - [Backend Architecture](../../docs/02-backend-architecture.md)
   - [Database Schema](../../docs/03-database-schema.md)
   - [API Design](../../docs/04-api-design.md)

2. **Start Development**
   - [Implementation Priority](../../docs/13-implementation-priority.md)
   - [Development Roadmap](../../docs/10-development-roadmap.md)

3. **Learn Key Concepts**
   - [Multi-Tenancy Strategy](../../docs/07-multi-tenancy.md)
   - [Security Guidelines](../../docs/09-security.md)
   - [API Documentation System](../../docs/16-api-documentation-system.md)

## Development Workflow

### Daily Workflow

```bash
# Start backend
cd platform/backend
php artisan serve          # Terminal 1
php artisan queue:work     # Terminal 2
php artisan horizon        # Terminal 3 (optional)

# Start admin panel
cd platform/admin-panel
npm run dev                # Terminal 4

# Start storefront (if working on it)
cd storefront-template
npm run dev                # Terminal 5
```

### Before Committing

```bash
# Backend
cd platform/backend
php artisan test                    # Run tests
php artisan scribe:generate         # Update API docs
./vendor/bin/phpstan analyse        # Static analysis

# Frontend
cd platform/admin-panel
npm test                            # Run tests
npm run lint                        # Lint code
npm run type-check                  # TypeScript check
```

## Reference Documentation

- **Getting Started**: [docs/11-getting-started.md](../../docs/11-getting-started.md)
- **Repository Structure**: [docs/15-repository-structure.md](../../docs/15-repository-structure.md)
- **Business Model**: [docs/12-business-model-strategy.md](../../docs/12-business-model-strategy.md)

## Support

For issues or questions:
- Check documentation in `docs/` folder
- Review GitHub Copilot skills in `.github/skills/`
- Follow setup scripts in `scripts/` folder

---

**Tip**: Keep this skill handy for onboarding new team members or setting up new development machines!
