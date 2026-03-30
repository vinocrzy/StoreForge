# Getting Started - Quick Setup Guide

**Updated**: March 30, 2026  
**Status**: ✅ Verified Working Setup

## Prerequisites

Before you begin, ensure you have the following installed:

### Required Software
- **PHP** 8.2 or higher (Tested: 8.2.12)
- **Composer** 2.x (Tested: 2.9.5)
- **Node.js** 18.x or higher
- **npm** or **yarn**
- **MySQL** 8.0+ or **PostgreSQL** 14+
- **Redis** 7.x (optional for development)
- **Git**

### Quick Check

```bash
php -v        # Should show 8.2+
composer --version  # Should show Composer version
node -v       # Should show 18+
npm -v
mysql --version
```

## Backend Setup (Laravel) - Verified Process

This is the actual tested process that works:

### Step 1: Prepare Environment

**Windows users with XAMPP**: Fix php.ini paths first:

```ini
; Edit C:\path\to\xampp\php\php.ini
extension_dir = "C:\soft\xampp\php\ext"  ; Use full path
browscap = "C:\soft\xampp\php\extras\browscap.ini"  ; Use full path

; Enable these extensions:
extension=zip      ; CRITICAL for fast installs
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_mysql
```

### Step 2: Install Composer Globally (if needed)

```powershell
# Download composer.phar
Invoke-WebRequest -Uri "https://getcomposer.org/download/latest-stable/composer.phar" -OutFile "composer.phar"

# Create global directory
New-Item -ItemType Directory -Force -Path "$env:USERPROFILE\bin"
Move-Item composer.phar "$env:USERPROFILE\bin\composer.phar" -Force

# Create batch wrapper
'@php "%USERPROFILE%\bin\composer.phar" %*' | Out-File -FilePath "$env:USERPROFILE\bin\composer.bat" -Encoding ASCII

# Test
php $env:USERPROFILE\bin\composer.phar --version
```

### Step 3: Create Laravel Project

```bash
cd platform

# Fast install with zip enabled (1-2 minutes)
composer create-project laravel/laravel backend "11.*" --prefer-dist --no-interaction

cd backend
```

**What you'll get**: Laravel 11.51.0 (or latest 11.x)
```

## ✅ What You Get After Backend Setup

After following the setup steps, your Laravel backend will have:

### Database structure (4 core migrations)
- `stores` - Store configuration with settings
- `users` - Users with soft deletes
- `store_user` - Many-to-many pivot with roles (owner/admin/manager/staff)
- `personal_access_tokens` - Sanctum API tokens

### Multi-tenancy system
- ✅ `HasTenantScope` trait - Automatic tenant filtering
- ✅ `TenantModel` base class - For all tenant-aware models
- ✅ `SetTenantFromHeader` middleware - Validates X-Store-ID header
- ✅ Helper functions: `tenant()`, `tenant_id()`, `has_tenant()`

### Authentication
- ✅ `AuthController` - Login, logout, get user, revoke tokens
- ✅ Scribe documentation annotations
- ✅ API routes configured

### Testing
- ✅ `TenantIsolationTest` - Security tests for multi-tenancy

### API Endpoints
```
POST   /api/v1/auth/login        - Login and get token
POST   /api/v1/auth/logout       - Revoke current token
GET    /api/v1/auth/me           - Get user info (requires X-Store-ID)
POST   /api/v1/auth/revoke-all   - Revoke all user tokens
```

**See QUICKSTART.md in project root for next steps!**

### Step 6: Install Laravel Horizon (Queue Monitoring)

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate

# Start Horizon
php artisan horizon

# Access dashboard at http://localhost:8000/horizon
```

### Step 7: Setup API Documentation (Recommended)

Automatically generate professional API documentation using Laravel Scribe:

```bash
# Quick setup (automated)
bash ../scripts/setup-api-docs.sh

# Or on Windows
..\scripts\setup-api-docs.bat
```

**What this sets up:**
- ✅ Laravel Scribe for auto-generating API docs
- ✅ Professional, interactive HTML documentation
- ✅ Try-it-out feature for testing endpoints
- ✅ Postman collection export
- ✅ OpenAPI specification export
- ✅ Git pre-commit hook for auto-updating docs

**Access documentation at:** `http://localhost:8000/docs`

**Quick reference for documenting your controllers:**
- See `docs/API-DOCS-QUICK-REFERENCE.md` for copy-paste templates
- See `docs/16-api-documentation-system.md` for complete guide
- See `app/Http/Controllers/Api/V1/ExampleController.php` for examples

## Admin Panel Setup (React)

### Step 1: Create React Project

```bash
cd c:/poc/e-com

# Create Vite + React + TypeScript project
npm create vite@latest admin-panel -- --template react-ts
cd admin-panel
```

### Step 2: Install Dependencies

```bash
# Install core dependencies
npm install

# Install UI library (Ant Design)
npm install antd @ant-design/icons

# Install routing
npm install react-router-dom

# Install state management
npm install @reduxjs/toolkit react-redux

# Install form handling
npm install react-hook-form zod @hookform/resolvers

# Install HTTP client
npm install axios

# Install utilities
npm install date-fns clsx
```

### Step 3: Configure Environment

Create `.env` file:

```env
VITE_API_URL=http://localhost:8000/api/v1
VITE_APP_NAME=E-Commerce Admin
```

### Step 4: Configure API Client

Create `src/api/axios.ts`:

```typescript
import axios from 'axios';

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add auth token to requests
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle auth errors
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

### Step 5: Start Development Server

```bash
npm run dev

# Admin panel will be available at http://localhost:5173
```

## Storefront Setup (Next.js)

### Step 1: Create Next.js Project

```bash
cd c:/poc/e-com

# Create Next.js project with TypeScript and Tailwind
npx create-next-app@latest storefront --typescript --tailwind --app --no-src-dir
cd storefront
```

### Step 2: Install Dependencies

```bash
# Install core dependencies
npm install

# Install state management
npm install zustand

# Install data fetching
npm install swr

# Install forms
npm install react-hook-form zod @hookform/resolvers

# Install UI components
npm install @headlessui/react @heroicons/react

# Install utilities
npm install clsx date-fns
```

### Step 3: Configure for Static Export

Edit `next.config.js`:

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true,
  },
  trailingSlash: true,
  env: {
    NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL,
    NEXT_PUBLIC_STORE_ID: process.env.NEXT_PUBLIC_STORE_ID,
  },
};

module.exports = nextConfig;
```

### Step 4: Configure Environment

Create `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_STORE_ID=1
```

### Step 5: Start Development Server

```bash
npm run dev

# Storefront will be available at http://localhost:3000
```

### Step 6: Build Static Export

```bash
npm run build

# Static files will be in 'out' directory
```

## Docker Setup (Optional but Recommended)

### Create Docker Compose Configuration

Create `docker-compose.yml` in project root:

```yaml
version: '3.8'

services:
  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: ecom-mysql
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: ecommerce
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - ecom-network

  # Redis
  redis:
    image: redis:7-alpine
    container_name: ecom-redis
    ports:
      - "6379:6379"
    networks:
      - ecom-network

  # Laravel Backend
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: ecom-backend
    ports:
      - "8000:8000"
    volumes:
      - ./backend:/var/www/html
    environment:
      DB_HOST: mysql
      DB_DATABASE: ecommerce
      DB_USERNAME: root
      DB_PASSWORD: root
      REDIS_HOST: redis
    depends_on:
      - mysql
      - redis
    networks:
      - ecom-network

volumes:
  mysql_data:

networks:
  ecom-network:
    driver: bridge
```

### Backend Dockerfile

Create `backend/Dockerfile`:

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader

# Generate key
RUN php artisan key:generate

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
```

### Start Docker Environment

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Run migrations
docker-compose exec backend php artisan migrate

# Stop services
docker-compose down
```

## Development Workflow

### Daily Development

1. **Start Services**
```bash
# Without Docker
cd backend && php artisan serve  # Terminal 1
cd admin-panel && npm run dev    # Terminal 2
cd storefront && npm run dev     # Terminal 3

# With Docker
docker-compose up -d
cd admin-panel && npm run dev
cd storefront && npm run dev
```

2. **Database Migrations**
```bash
# Create migration
php artisan make:migration create_products_table

# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback
```

3. **Testing**
```bash
# Backend tests
cd backend
php artisan test

# Frontend tests
cd admin-panel
npm run test
```

### Code Quality

```bash
# Backend - Laravel Pint (code style)
cd backend
./vendor/bin/pint

# Frontend - ESLint
cd admin-panel
npm run lint
npm run lint:fix
```

## Common Issues & Solutions

### Issue: Port Already in Use

```bash
# Check what's using the port
# Windows
netstat -ano | findstr :8000

# Kill process
taskkill /PID <PID> /F

# Or use different port
php artisan serve --port=8001
```

### Issue: Database Connection Failed

1. Verify MySQL is running
2. Check database credentials in `.env`
3. Ensure database exists
4. Test connection:
```bash
php artisan tinker
DB::connection()->getPdo();
```

### Issue: Redis Connection Failed

1. Verify Redis is running:
```bash
redis-cli ping
# Should return: PONG
```

2. Check Redis configuration in `.env`

### Issue: Permission Denied (Laravel)

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Useful Commands

### Laravel Artisan

```bash
# List all commands
php artisan list

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate resources
php artisan make:model Product -mfc  # Model, Migration, Factory, Controller
php artisan make:request CreateProductRequest
php artisan make:resource ProductResource

# Database
php artisan migrate:fresh --seed  # Fresh migrate with seeding
php artisan db:seed --class=ProductSeeder

# Queue
php artisan queue:work --tries=3
php artisan queue:failed  # List failed jobs
php artisan queue:retry all  # Retry failed jobs
```

### NPM Scripts

```bash
# Development
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Type checking
npm run type-check

# Linting
npm run lint
npm run lint:fix
```

## Next Steps

1. ✅ Set up development environment  
2. ⭐ Setup API documentation (see Step 7 above)
3. 📖 Review [System Architecture](docs/01-system-architecture.md)  
4. 📖 Read [Backend Architecture](docs/02-backend-architecture.md)  
5. 📖 Study [Database Schema](docs/03-database-schema.md)  
6. 📖 Learn [API Documentation System](docs/16-api-documentation-system.md)
7. 🔨 Start implementing Phase 1 features  
8. 📖 Follow [Development Roadmap](docs/10-development-roadmap.md)

## Support & Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [Next.js Documentation](https://nextjs.org/docs)
- [Ant Design Components](https://ant.design/components/overview)

### Community
- Laravel Discord
- React Community
- Stack Overflow

### Tools
- [API Documentation](http://localhost:8000/docs) - Interactive API docs (after setup)
- [Postman](https://www.postman.com/) - API testing
- [TablePlus](https://tableplus.com/) - Database GUI
- [Redis Commander](https://github.com/joeferner/redis-commander) - Redis GUI
- [Laravel Horizon](http://localhost:8000/horizon) - Queue monitoring

## Conclusion

You now have a fully configured development environment for all three components of the e-commerce platform:

1. ✅ Laravel Backend API
2. ✅ React Admin Panel
3. ✅ Next.js Storefront

Happy coding! 🚀
