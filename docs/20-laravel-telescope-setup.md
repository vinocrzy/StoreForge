# Laravel Telescope Setup Guide

## Overview

Laravel Telescope is an elegant debug assistant for Laravel applications. It provides insight into requests, exceptions, database queries, queued jobs, mail, notifications, cache operations, scheduled tasks, and more.

**Use Cases**:
- Development debugging and profiling
- Performance monitoring
- Query optimization
- Exception tracking
- Request/response inspection

**⚠️ Important**: Telescope should only be enabled in development and staging environments, NOT in production (unless properly secured with authentication).

---

## Installation

### Step 1: Install Telescope Package

```bash
cd platform/backend
composer require laravel/telescope --dev
```

### Step 2: Publish Configuration and Assets

```bash
php artisan telescope:install
php artisan migrate
```

This will create:
- `config/telescope.php` - Configuration file
- Database migration for telescope_entries table
- Public assets in `public/vendor/telescope`

### Step 3: Configure Environment

Add to `.env`:

```env
# Telescope Configuration
TELESCOPE_ENABLED=true
TELESCOPE_PATH=telescope
```

---

## Configuration

### Basic Configuration (`config/telescope.php`)

```php
<?php

use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers;

return [
    'path' => env('TELESCOPE_PATH', 'telescope'),

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    'enabled' => env('TELESCOPE_ENABLED', true),

    'middleware' => [
        'web',
        Laravel\Telescope\Http\Middleware\Authorize::class,
    ],

    'ignore_paths' => [
        'nova-api*',
        'horizon*',
    ],

    'ignore_commands' => [
        //
    ],

    'watchers' => [
        Watchers\CacheWatcher::class => env('TELESCOPE_CACHE_WATCHER', true),
        
        Watchers\CommandWatcher::class => [
            'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\DumpWatcher::class => env('TELESCOPE_DUMP_WATCHER', true),
        
        Watchers\EventWatcher::class => env('TELESCOPE_EVENT_WATCHER', true),
        
        Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),
        
        Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),
        
        Watchers\LogWatcher::class => env('TELESCOPE_LOG_WATCHER', true),
        
        Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),

        Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => ['created', 'updated', 'deleted'],
        ],

        Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),

        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'slow' => 100, // milliseconds
        ],

        Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),

        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
        ],

        Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        
        Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],
];
```

### Security: Authorize Access

Edit `app/Providers/TelescopeServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        // Only enable Telescope in local and staging environments
        Telescope::filter(function (IncomingEntry $entry) {
            if ($this->app->environment('local')) {
                return true;
            }

            // In production, only log exceptions and failed jobs
            return $entry->isReportableException() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            // Allow access for super admins only
            return in_array($user->email, [
                'admin@example.com',
                'dev@example.com',
            ]) || $user->hasRole('super-admin');
        });
    }
}
```

---

## Usage

### Accessing Telescope

Navigate to: `http://localhost:8000/telescope`

### Available Tabs

1. **Requests** - View all HTTP requests
   - Method, path, status, duration
   - Request/response bodies
   - Headers
   - Session data

2. **Commands** - Artisan commands executed
   - Command name
   - Arguments and options
   - Exit code
   - Duration

3. **Schedule** - Scheduled tasks
   - Task name
   - Execution time
   - Output

4. **Jobs** - Queued jobs
   - Job class
   - Queue name
   - Status (pending, processed, failed)
   - Payload

5. **Exceptions** - Application exceptions
   - Exception class
   - Message
   - Stack trace
   - File and line number

6. **Logs** - Application logs
   - Level (debug, info, warning, error)
   - Message
   - Context

7. **Queries** - Database queries
   - SQL statement
   - Bindings
   - Duration
   - Connection

8. **Models** - Eloquent model events
   - Model class
   - Event type (created, updated, deleted)
   - Changes

9. **Mail** - Emails sent
   - Subject
   - Recipients
   - Content
   - Attachments

10. **Notifications** - Notifications sent
    - Channel (mail, database, etc.)
    - Notifiable
    - Content

11. **Events** - Application events
    - Event class
    - Listeners
    - Payload

12. **Cache** - Cache operations
    - Operation (hit, miss, set, forget)
    - Key
    - Value
    - TTL

13. **Redis** - Redis commands
    - Command
    - Connection
    - Duration

14. **Dumps** - Debug dumps (`dump()`, `dd()`)
    - Variable contents

---

## Performance Optimization

### 1. Prune Old Entries

Telescope stores all entries in the database. Prune old entries regularly:

```bash
# Prune entries older than 24 hours
php artisan telescope:prune --hours=24

# Prune entries older than 7 days
php artisan telescope:prune --hours=168
```

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Prune Telescope entries older than 48 hours daily
    $schedule->command('telescope:prune --hours=48')->daily();
}
```

### 2. Disable Expensive Watchers in Production

In `.env` for production:

```env
TELESCOPE_ENABLED=false
TELESCOPE_QUERY_WATCHER=false
TELESCOPE_MODEL_WATCHER=false
TELESCOPE_REQUEST_WATCHER=false
```

### 3. Limit Query Logging

Only log slow queries in production:

```php
'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', false),
        'slow' => 500, // Only log queries > 500ms
    ],
],
```

---

## Multi-Tenant Considerations

### Tag Entries by Store

To filter Telescope entries by store, add tags in `TelescopeServiceProvider`:

```php
public function register(): void
{
    Telescope::tag(function (IncomingEntry $entry) {
        $tags = [];

        // Tag by store ID from request header
        if (request()->hasHeader('X-Store-ID')) {
            $tags[] = 'store:' . request()->header('X-Store-ID');
        }

        // Tag by authenticated user
        if ($user = auth()->user()) {
            $tags[] = 'user:' . $user->id;
            if ($user->currentStore) {
                $tags[] = 'store:' . $user->currentStore->id;
            }
        }

        // Tag API vs web requests
        if (request()->is('api/*')) {
            $tags[] = 'api';
        }

        return $tags;
    });
}
```

### Filter Telescope by Store

In Telescope UI, filter by tag:
- Click "Filter" button
- Enter `store:1` to see entries for store ID 1

---

## Common Use Cases

### 1. Debug Slow Queries

1. Navigate to **Queries** tab
2. Sort by duration (descending)
3. Identify N+1 queries
4. Optimize with eager loading

Example:
```php
// ❌ N+1 query problem
$products = Product::all();
foreach ($products as $product) {
    echo $product->category->name; // Additional query per product
}

// ✅ Optimized with eager loading
$products = Product::with('category')->all();
foreach ($products as $product) {
    echo $product->category->name; // No additional queries
}
```

### 2. Track Exception Patterns

1. Navigate to **Exceptions** tab
2. Group by exception class
3. Identify most common errors
4. Fix root causes

### 3. Monitor API Performance

1. Navigate to **Requests** tab
2. Filter by `api` tag
3. Sort by duration
4. Identify slow endpoints

### 4. Debug Failed Jobs

1. Navigate to **Jobs** tab
2. Filter by status: failed
3. View exception and stack trace
4. Retry or fix job logic

---

## Alternatives to Telescope

For production monitoring, consider:

1. **Sentry** - Error tracking and performance monitoring
   ```bash
   composer require sentry/sentry-laravel
   ```

2. **New Relic** - Application performance monitoring (APM)
3. **Datadog** - Infrastructure and application monitoring
4. **Laravel Horizon** - Queue monitoring (Redis-specific)
5. **Laravel Pulse** - Real-time application metrics (Laravel 10+)

---

## Best Practices

### DO:
✅ Use Telescope in local and staging environments  
✅ Prune old entries regularly  
✅ Secure Telescope with authentication gates  
✅ Tag entries for multi-tenant filtering  
✅ Disable expensive watchers in production  
✅ Use Telescope to identify performance bottlenecks  

### DON'T:
❌ Enable Telescope in production without security  
❌ Log sensitive data (passwords, tokens, credit cards)  
❌ Keep Telescope entries indefinitely  
❌ Enable all watchers in production  
❌ Expose Telescope publicly without authentication  

---

## Troubleshooting

### Telescope not accessible

**Problem**: 404 error when accessing `/telescope`

**Solution**:
```bash
# Re-publish assets
php artisan telescope:publish

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database size growing rapidly

**Problem**: `telescope_entries` table is huge

**Solution**:
```bash
# Prune old entries
php artisan telescope:prune --hours=24

# Schedule automatic pruning
# Add to app/Console/Kernel.php
$schedule->command('telescope:prune')->daily();
```

### Too many queries logged

**Problem**: Telescope logging slowing down application

**Solution**:
```php
// config/telescope.php
'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', false),
        'slow' => 1000, // Only log queries > 1 second
    ],
],
```

---

## Next Steps

1. Install Telescope in development environment
2. Configure security gates for production
3. Set up automatic pruning schedule
4. Integrate with error tracking service (Sentry)
5. Document common debugging workflows for team

---

**Related Documentation**:
- [Laravel Telescope Official Docs](https://laravel.com/docs/11.x/telescope)
- [docs/08-scalability.md](08-scalability.md) - Performance optimization
- [docs/09-security.md](09-security.md) - Security best practices
