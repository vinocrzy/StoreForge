# Quick Start Guide

## ✅ Status: Backend Setup Complete!

Your Laravel 11 backend is installed and configured with multi-tenancy support.

---

## 📋 What's Been Set Up

### ✅ Installed Packages
- **Laravel 11.51.0** - Latest Laravel framework
- **Laravel Sanctum** - API authentication
- **Spatie Permission** - Role & permission management
- **Spatie Query Builder** - Advanced query filtering
- **Scribe** - API documentation generator

### ✅ Created Files  
**Migrations** (4):
- `2024_01_01_000001_create_stores_table.php`
- `2024_01_01_000002_create_users_table.php`
- `2024_01_01_000003_create_store_user_table.php`
- `2024_01_01_000004_create_personal_access_tokens_table.php`

**Models** (3):
- `Store.php` - Store model with settings management
- `User.php` - Extended with store relationships & access control
- `TenantModel.php` - Base class for tenant-aware models

**Middleware** (1):
- `SetTenantFromHeader.php` - Validates tenant context from X-Store-ID header

**Controllers** (1):
- `AuthController.php` - Login, logout, user info, token management (with Scribe docs)

**Tests** (1):
- `TenantIsolationTest.php` - Tenant middleware security tests

**Helpers**:
- `tenant()`, `tenant_id()`, `has_tenant()` - Global tenant context functions

**Traits**:
- `HasTenantScope.php` - Automatic tenant filtering for models

**Routes**:
- API routes configured in `routes/api.php`
- Middleware registered in `bootstrap/app.php`

---

## 🚀 Next Steps

### 1. Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### 2. Create Database

```powershell
# MySQL
mysql -u root -p -e "CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Or PostgreSQL
psql -U postgres -c "CREATE DATABASE ecommerce;"
```

### 3. Run Migrations

```powershell
cd C:\poc\e-com\platform\backend
php artisan migrate
```

This will create:
- ✅ `stores` table
- ✅ `users` table
- ✅ `store_user` pivot table
- ✅ `personal_access_tokens` table (Sanctum)
- ✅ `sessions` table
- ✅ `password_reset_tokens` table
- ✅ Spatie permission tables (roles, permissions)

### 4. Create Test Data (Optional)

```powershell
php artisan tinker
```

```php
// Create a store
$store = \App\Models\Store::create([
    'name' => 'My Store',
    'slug' => 'my-store',
    'email' => 'admin@mystore.com',
    'status' => 'active',
]);

// Create a user
$user = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@mystore.com',
    'password' => bcrypt('password'),
    'status' => 'active',
]);

// Attach user to store as owner
$user->stores()->attach($store->id, ['role' => 'owner']);
```

### 5. Start Development Server

```powershell
php artisan serve
```

Backend available at: **http://localhost:8000**

### 6. Test API Endpoints

**Login:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@mystore.com",
    "password": "password"
  }'
```

Response:
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@mystore.com",
    "status": "active"
  },
  "token": "1|xxxxx...",
  "stores": [
    {
      "id": 1,
      "name": "My Store",
      "slug": "my-store",
      "role": "owner"
    }
  ]
}
```

**Get User Info (with tenant context):**
```bash
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-Store-ID: 1"
```

### 7. Run Tests

```powershell
php artisan test
```

Expected tests:
- ✅ Middleware requires X-Store-ID header
- ✅ Middleware blocks unauthorized store access
- ✅ User can access authorized store

### 8. Generate API Documentation

```powershell
php artisan scribe:generate
```

View docs at: **http://localhost:8000/docs**

---

## 📁 Project Structure

```
platform/backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   └── AuthController.php       # Auth endpoints
│   │   └── Middleware/
│   │       └── SetTenantFromHeader.php  # Tenant middleware
│   ├── Models/
│   │   ├── Concerns/
│   │   │   └── HasTenantScope.php       # Tenant scope trait
│   │   ├── Store.php                     # Store model
│   │   ├── TenantModel.php              # Base tenant model
│   │   └── User.php                      # User model
│   └── helpers.php                       # Helper functions
├── database/
│   ├── factories/
│   │   └── StoreFactory.php             # Store factory
│   └── migrations/                       # 4 migration files
├── routes/
│   └── api.php                          # API routes
├── tests/
│   └── Feature/
│       └── TenantIsolationTest.php      # Security tests
└── .env                                  # Environment config
```

---

## 🔐 Security Notes

**Multi-Tenancy** is CRITICAL:
- All API requests (except login) MUST include `X-Store-ID` header
- Middleware validates user has access to requested store
- Global scopes automatically filter data by `store_id`
- Tests verify no cross-tenant data leakage

**Authentication**:
- Using Laravel Sanctum token-based auth
- Tokens created on login, revoked on logout
- User must be active (`status='active'`)

---

## 📚 Documentation

- **Progress Tracker**: [PROGRESS.md](PROGRESS.md)
- **Setup Guide**: [platform/backend/SETUP.md](platform/backend/SETUP.md)
- **System Architecture**: [docs/01-system-architecture.md](docs/01-system-architecture.md)
- **Multi-Tenancy**: [docs/07-multi-tenancy.md](docs/07-multi-tenancy.md)
- **API Documentation**: [docs/16-api-documentation-system.md](docs/16-api-documentation-system.md)

---

## ⏭️ What's Next

Continue with **Phase 1** remaining tasks:
1. Configure Redis for caching/queues
2. Install Laravel Horizon
3. Create database seeders
4. Implement password reset flow
5. Complete tenant isolation testing

Then move to **Phase 2: Core E-Commerce Features**:
- Products management
- Inventory tracking
- Order processing
- Customer management

See [PROGRESS.md](PROGRESS.md) for full roadmap.

---

## 🆘 Need Help?

- Check [SETUP.md](platform/backend/SETUP.md) for detailed setup instructions
- Review [PHP-UPGRADE-REQUIRED.md](PHP-UPGRADE-REQUIRED.md) for PHP configuration
- See [.github/skills/](. github/skills/) for Copilot skills
- Read documentation in [docs/](docs/) folder

**Backend is ready to go!** 🎉
