# Production Configuration Guide

## Overview

Comprehensive guide for configuring the multi-tenant e-commerce platform for production deployment. This covers environment variables, database optimization, caching, queues, and security hardening.

**Goals**:
- Secure production environment
- Optimize performance
- Enable scalability
- Ensure reliability

---

## 1. Environment Configuration

### Production `.env` Template

```env
# Application
APP_NAME="E-Commerce Platform"
APP_ENV=production
APP_KEY=base64:GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
APP_VERSION=1.0.0

# Frontend URLs
ADMIN_URL=https://admin.yourdomain.com
STOREFRONT_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_prod
DB_USERNAME=ecommerce_user
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Database Connection Pool
DB_CONNECTION_LIMIT=100
DB_POOL_SIZE=10

# Redis Cache
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=REDIS_PASSWORD_HERE
REDIS_PORT=6379
REDIS_DB=0

# Redis Queue
REDIS_QUEUE_CONNECTION=queue
REDIS_QUEUE_DB=1

# Redis Session
REDIS_SESSION_CONNECTION=session
REDIS_SESSION_DB=2

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=ecom_cache

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Queue
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database

# File Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_AWS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_AWS_SECRET
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_ENDPOINT=
AWS_URL=https://your-bucket-name.s3.amazonaws.com

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK

# Monitoring
TELESCOPE_ENABLED=false
SENTRY_LARAVEL_DSN=https://xxxxx@xxxxx.ingest.sentry.io/xxxxx
SENTRY_TRACES_SAMPLE_RATE=0.1

# Security
SANCTUM_STATEFUL_DOMAINS=admin.yourdomain.com,yourdomain.com
SESSION_DOMAIN=.yourdomain.com

# API Rate Limiting
API_RATE_LIMIT=60
API_RATE_LIMIT_GUEST=10

# CORS
CORS_ALLOWED_ORIGINS=https://admin.yourdomain.com,https://yourdomain.com
CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Store-ID,X-Requested-With

# Scribe API Documentation
SCRIBE_AUTH_ENABLED=true
```

### Environment-Specific Configurations

#### Development (`APP_ENV=local`)
```env
APP_DEBUG=true
LOG_LEVEL=debug
TELESCOPE_ENABLED=true
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

#### Staging (`APP_ENV=staging`)
```env
APP_DEBUG=false
LOG_LEVEL=info
TELESCOPE_ENABLED=true
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### Production (`APP_ENV=production`)
```env
APP_DEBUG=false
LOG_LEVEL=error
TELESCOPE_ENABLED=false
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 2. Database Optimization

### Indexes

**Critical Indexes** (must be present):

```sql
-- Stores
CREATE INDEX idx_stores_status ON stores(status);

-- Users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_status ON users(status);

-- Products (Multi-tenant)
CREATE INDEX idx_products_store_id ON products(store_id);
CREATE INDEX idx_products_store_status ON products(store_id, status);
CREATE INDEX idx_products_store_category ON products(store_id, category_id);
CREATE INDEX idx_products_sku ON products(store_id, sku);
CREATE INDEX idx_products_slug ON products(store_id, slug);
CREATE FULLTEXT INDEX idx_products_search ON products(name, description);

-- Categories
CREATE INDEX idx_categories_store_id ON categories(store_id);
CREATE INDEX idx_categories_store_parent ON categories(store_id, parent_id);

-- Orders (Multi-tenant)
CREATE INDEX idx_orders_store_id ON orders(store_id);
CREATE INDEX idx_orders_store_customer ON orders(store_id, customer_id);
CREATE INDEX idx_orders_store_status ON orders(store_id, order_status);
CREATE INDEX idx_orders_store_payment ON orders(store_id, payment_status);
CREATE INDEX idx_orders_number ON orders(order_number);
CREATE INDEX idx_orders_created ON orders(created_at);

-- Customers
CREATE INDEX idx_customers_store_id ON customers(store_id);
CREATE INDEX idx_customers_email ON customers(store_id, email);
CREATE INDEX idx_customers_phone ON customers(store_id, phone);

-- Inventory
CREATE INDEX idx_inventory_store_id ON inventory(store_id);
CREATE INDEX idx_inventory_product ON inventory(store_id, product_id);
CREATE INDEX idx_inventory_warehouse ON inventory(warehouse_id);
```

### Query Optimization

**config/database.php**:
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => 'InnoDB',
    
    // Connection Pool
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_PERSISTENT => true, // Connection pooling
        PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements
    ]) : [],
],
```

### MySQL Configuration

**/etc/mysql/my.cnf** (production server):
```ini
[mysqld]
# Connection Settings
max_connections = 200
connect_timeout = 10
wait_timeout = 600
max_allowed_packet = 64M

# Query Cache (MySQL 5.7)
query_cache_type = 1
query_cache_size = 128M
query_cache_limit = 2M

# InnoDB Settings
innodb_buffer_pool_size = 1G  # 50-70% of available RAM
innodb_log_file_size = 256M
innodb_log_buffer_size = 8M
innodb_flush_log_at_trx_commit = 2
innodb_lock_wait_timeout = 50

# Binary Logging (for replication)
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 1
```

---

## 3. Caching Strategy

### Cache Configuration

**config/cache.php**:
```php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
```

### Laravel Optimization Commands

```bash
# Production optimization - run after every deployment
php artisan config:cache    # Cache configuration files
php artisan route:cache     # Cache routes
php artisan view:cache      # Cache Blade views
php artisan event:cache     # Cache events and listeners

# Clear all caches (if needed)
php artisan optimize:clear
```

### API Response Caching

**app/Http/Middleware/CacheResponse.php**:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheResponse
{
    public function handle(Request $request, Closure $next, int $minutes = 10)
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Generate cache key
        $key = $this->getCacheKey($request);

        // Return cached response if exists
        if (Cache::has($key)) {
            return response()->json(Cache::get($key))
                ->header('X-Cache', 'HIT');
        }

        // Get fresh response
        $response = $next($request);

        // Cache successful responses
        if ($response->isSuccessful()) {
            Cache::put($key, $response->getData(), now()->addMinutes($minutes));
        }

        return $response->header('X-Cache', 'MISS');
    }

    protected function getCacheKey(Request $request): string
    {
        $storeId = $request->header('X-Store-ID', 'global');
        $uri = $request->getRequestUri();
        
        return "api_cache:{$storeId}:" . md5($uri);
    }
}
```

**Usage**:
```php
// Cache product list for 30 minutes
Route::get('/products', [ProductController::class, 'index'])
    ->middleware('cache.response:30');
```

### Cache Tags (Multi-Tenant)

```php
// Cache with store-specific tags
Cache::tags(['store:' . $storeId, 'products'])->put('products_list', $products, 3600);

// Invalidate all products for a store
Cache::tags(['store:' . $storeId, 'products'])->flush();
```

---

## 4. Queue Configuration

### Redis Queue Setup

**config/queue.php**:
```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => env('REDIS_QUEUE_CONNECTION', 'queue'),
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

### Queue Workers

**supervisor configuration** (`/etc/supervisor/conf.d/laravel-worker.conf`):
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/platform/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/platform/backend/storage/logs/worker.log
stopwaitsecs=3600
```

**Start supervisor**:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Failed Jobs Handler

**migration**:
```php
php artisan queue:failed-table
php artisan migrate
```

**Retry failed jobs**:
```bash
# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry {job-id}

# Forget failed job
php artisan queue:forget {job-id}

# Clear all failed jobs
php artisan queue:flush
```

### Queue Priorities

```php
// High priority jobs (order processing)
dispatch((new ProcessOrder($order))->onQueue('high'));

// Default priority (email sending)
dispatch(new SendInvoiceEmail($order));

// Low priority (analytics)
dispatch((new UpdateAnalytics($data))->onQueue('low'));
```

**Worker with priorities**:
```bash
php artisan queue:work --queue=high,default,low
```

---

## 5. Security Hardening

### Rate Limiting

**app/Providers/RouteServiceProvider.php**:
```php
protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip())
            ->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                ], 429, $headers);
            });
    });

    RateLimiter::for('api-guest', function (Request $request) {
        return Limit::perMinute(10)->by($request->ip());
    });

    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

**Apply rate limiting**:
```php
Route::middleware(['throttle:api'])->group(function () {
    // Authenticated API routes
});

Route::middleware(['throttle:api-guest'])->group(function () {
    // Public API routes
});

Route::post('/login')->middleware('throttle:login');
```

### CORS Configuration

**config/cors.php**:
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

### Security Headers

**app/Http/Middleware/SecurityHeaders.php**:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
```

**Register in Kernel.php**:
```php
protected $middleware = [
    // ...
    \App\Http\Middleware\SecurityHeaders::class,
];
```

### Input Validation

**Always use Form Requests**:
```php
// ✅ Good
class StoreProductRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'sku' => [
                'required',
                'string',
                Rule::unique('products', 'sku')->where('store_id', tenant()->id),
            ],
        ];
    }
}
```

### SQL Injection Prevention

```php
// ✅ Good - Use parameter binding
$products = DB::table('products')
    ->where('store_id', $storeId)
    ->where('status', 'active')
    ->get();

// ❌ Bad - Raw SQL injection risk
$products = DB::select("SELECT * FROM products WHERE store_id = {$storeId}");
```

### XSS Prevention

```blade
{{-- ✅ Good - Blade escapes by default --}}
<h1>{{ $product->name }}</h1>

{{-- ⚠️ Careful - Renders raw HTML --}}
<div>{!! $product->description !!}</div>

{{-- ✅ Better - Sanitize HTML --}}
<div>{!! clean($product->description) !!}</div>
```

### File Upload Security

```php
public function uploadProductImage(Request $request)
{
    $request->validate([
        'image' => [
            'required',
            'image',
            'mimes:jpeg,png,jpg,gif,webp',
            'max:5120', // 5MB
            'dimensions:max_width=4000,max_height=4000',
        ],
    ]);

    // Store with random filename
    $path = $request->file('image')->store('products', 's3');

    return response()->json(['path' => $path]);
}
```

---

## 6. Performance Configuration

### OPcache (PHP)

**/etc/php/8.2/fpm/conf.d/10-opcache.ini**:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0  # Set to 1 in staging
opcache.fast_shutdown=1
```

### PHP-FPM Configuration

**/etc/php/8.2/fpm/pool.d/www.conf**:
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
request_terminate_timeout = 60s
```

### Nginx Configuration

**/etc/nginx/sites-available/api.yourdomain.com**:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.yourdomain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.yourdomain.com;

    root /var/www/html/platform/backend/public;
    index index.php;

    # SSL
    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    # Client max body size
    client_max_body_size 20M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 7. Deployment Checklist

### Pre-Deployment
- [ ] Run tests: `php artisan test`
- [ ] Check code quality: `./vendor/bin/phpstan analyse`
- [ ] Update `.env` with production values
- [ ] Generate application key: `php artisan key:generate`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`

### Database
- [ ] Backup current database
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed production data (if needed)
- [ ] Verify indexes

### Optimization
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan event:cache`

### Queue & Workers
- [ ] Start queue workers via supervisor
- [ ] Verify workers are running: `sudo supervisorctl status`
- [ ] Clear old failed jobs if necessary

### Monitoring
- [ ] Verify Sentry is configured
- [ ] Check UptimeRobot monitors
- [ ] Test health check endpoints
- [ ] Verify logs are being written

### Post-Deployment
- [ ] Test critical user flows
- [ ] Monitor error logs for 24 hours
- [ ] Check application performance
- [ ] Verify background jobs are processing

---

## 8. Production Maintenance

### Daily
```bash
# Check application logs
tail -f storage/logs/laravel.log

# Check queue worker status
sudo supervisorctl status laravel-worker:*

# Monitor failed jobs
php artisan queue:failed
```

### Weekly
```bash
# Prune old Telescope entries (if enabled)
php artisan telescope:prune --hours=168

# Clear old sessions
php artisan session:gc

# Check disk space
df -h
```

### Monthly
```bash
# Backup database
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# Review and optimize indexes
php artisan model:show Product --database

# Update dependencies
composer update
php artisan optimize
```

---

## Related Documentation
- [docs/08-scalability.md](08-scalability.md) - Scaling strategies
- [docs/09-security.md](09-security.md) - Security guidelines
- [docs/21-monitoring-strategy.md](21-monitoring-strategy.md) - Monitoring setup
