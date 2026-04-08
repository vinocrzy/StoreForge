# Production Monitoring & Observability Strategy

## Overview

Comprehensive monitoring strategy for multi-tenant e-commerce platform covering application performance, errors, infrastructure, and user experience.

**Goals**:
- 99.9% uptime (< 43 minutes downtime per month)
- < 500ms API response time (p95)
- < 1% error rate
- Real-time alerting for critical issues
- Performance insights for optimization

---

## Monitoring Stack

### Development & Staging

| Tool | Purpose | Cost |
|------|---------|------|
| Laravel Telescope | Request/query debugging | Free |
| Laravel Log Viewer | Log file inspection | Free |
| Browser DevTools | Frontend debugging | Free |

### Production

| Tool | Purpose | Cost (approx) |
|------|---------|---------------|
| Sentry | Error tracking & performance | $26/month |
| UptimeRobot | Uptime monitoring | Free (50 monitors) |
| Laravel Logs | Application logs | Free |
| Papertrail/Logtail | Log aggregation | $7-15/month |
| New Relic/Datadog | APM (optional) | $99+/month |

---

## 1. Error Tracking (Sentry)

### Installation

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN_HERE
```

### Configuration

**config/sentry.php**:
```php
<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'livewire' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],

    'traces_sample_rate' => (float) (env('SENTRY_TRACES_SAMPLE_RATE', 0.2)),

    'profiles_sample_rate' => (float) (env('SENTRY_PROFILES_SAMPLE_RATE', 0.2)),

    'send_default_pii' => false,

    'environment' => env('APP_ENV', 'production'),

    'release' => env('SENTRY_RELEASE'),

    'before_send' => function (\\Sentry\\Event $event, ?\\Sentry\\EventHint $hint): ?\\Sentry\\Event {
        // Don't send to Sentry in local environment
        if (app()->environment('local')) {
            return null;
        }

        return $event;
    },

    'before_send_transaction' => function (\\Sentry\\Event $event): ?\\Sentry\\Event {
        return $event;
    },

    'integrations' => [
        new \\Sentry\\Integration\\IgnoreErrorsIntegration([
            'ignore_exceptions' => [
                \\Illuminate\\Auth\\AuthenticationException::class,
                \\Illuminate\\Validation\\ValidationException::class,
            ],
        ]),
    ],
];
```

**.env**:
```env
SENTRY_LARAVEL_DSN=https://xxxxx@xxxxx.ingest.sentry.io/xxxxx
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_PROFILES_SAMPLE_RATE=0.2
```

### Usage

**Automatic Exception Reporting**:
```php
// Exceptions are automatically sent to Sentry
throw new \\Exception('Something went wrong!');
```

**Manual Context**:
```php
\\Sentry\\configureScope(function (\\Sentry\\State\\Scope $scope): void {
    $scope->setUser([
        'id' => auth()->id(),
        'email' => auth()->user()->email,
        'store_id' => tenant()->id,
    ]);
    
    $scope->setTag('store_id', tenant()->id);
    $scope->setContext('order', [
        'id' => $order->id,
        'amount' => $order->total_amount,
    ]);
});
```

**Performance Monitoring**:
```php
$transaction = \\Sentry\\startTransaction(['name' => 'process-order']);
\\Sentry\\SentrySdk::getCurrentHub()->setSpan($transaction);

// Your code here...
$this->orderService->process($order);

$transaction->finish();
```

### Multi-Tenant Tagging

**app/Http/Middleware/TenantMiddleware.php**:
```php
\\Sentry\\configureScope(function (\\Sentry\\State\\Scope $scope): void {
    if (tenant()->exists()) {
        $scope->setTag('store_id', tenant()->id);
        $scope->setTag('store_name', tenant()->name);
        $scope->setContext('store', [
            'id' => tenant()->id,
            'name' => tenant()->name,
            'domain' => tenant()->domain,
        ]);
    }
});
```

---

## 2. Application Performance Monitoring (APM)

### Metrics to Track

#### API Performance
- Response time (p50, p95, p99)
- Throughput (requests per second)
- Error rate
- Slowest endpoints

#### Database
- Query count per request
- Slow queries (> 100ms)
- Connection pool usage
- Deadlocks

#### Queue
- Job processing time
- Failed jobs count
- Queue depth
- Job retries

#### Cache
- Hit rate
- Miss rate
- Eviction rate
- Memory usage

### Custom Performance Logs

**app/Http/Middleware/LogPerformance.php**:
```php
<?php

namespace App\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Log;

class LogPerformance
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        // Log slow requests (> 500ms)
        if ($duration > 500) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
                'queries' => \\DB::getQueryLog() ? count(\\DB::getQueryLog()) : 0,
                'store_id' => tenant()->id ?? null,
            ]);
        }

        return $response;
    }
}
```

---

## 3. Uptime Monitoring

### UptimeRobot Configuration

**Monitors to Create**:

1. **Main API Health Check**
   - URL: `https://api.yourdomain.com/health`
   - Interval: 5 minutes
   - Alert on: Down

2. **Admin Panel**
   - URL: `https://admin.yourdomain.com`
   - Interval: 5 minutes
   - Alert on: Down or keywords missing

3. **Store 1 Storefront**
   - URL: `https://store1.yourdomain.com`
   - Interval: 10 minutes
   - Alert on: Down

4. **Database Connection**
   - URL: `https://api.yourdomain.com/health/database`
   - Interval: 5 minutes
   - Alert on: Down

5. **Redis Connection**
   - URL: `https://api.yourdomain.com/health/redis`
   - Interval: 5 minutes
   - Alert on: Down

### Health Check Endpoint

**routes/api.php**:
```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

Route::get('/health/database', function () {
    try {
        \\DB::connection()->getPdo();
        return response()->json(['status' => 'ok', 'database' => 'connected']);
    } catch (\\Exception $e) {
        return response()->json(['status' => 'error', 'database' => 'disconnected'], 503);
    }
});

Route::get('/health/redis', function () {
    try {
        \\Redis::ping();
        return response()->json(['status' => 'ok', 'redis' => 'connected']);
    } catch (\\Exception $e) {
        return response()->json(['status' => 'error', 'redis' => 'disconnected'], 503);
    }
});
```

---

## 4. Log Management

### Structured Logging

**config/logging.php**:
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'sentry'],
        'ignore_exceptions' => false,
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],

    'json' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.json'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'formatter' => \\Monolog\\Formatter\\JsonFormatter::class,
    ],

    'sentry' => [
        'driver' => 'sentry',
    ],
],
```

### Log Context Best Practices

```php
// ✅ Good - Structured context
Log::info('Order created', [
    'order_id' => $order->id,
    'customer_id' => $order->customer_id,
    'store_id' => $order->store_id,
    'amount' => $order->total_amount,
    'items_count' => $order->items->count(),
]);

// ❌ Bad - Unstructured message
Log::info('Order ' . $order->id . ' created for customer ' . $order->customer_id);
```

### Log Aggregation (Papertrail/Logtail)

**Install Monolog Handler**:
```bash
composer require monolog/monolog
```

**config/logging.php**:
```php
'papertrail' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'debug'),
    'handler' => \\Monolog\\Handler\\SyslogUdpHandler::class,
    'handler_with' => [
        'host' => env('PAPERTRAIL_HOST'),
        'port' => env('PAPERTRAIL_PORT'),
        'connectionString' => 'tls://'.env('PAPERTRAIL_HOST').':'.env('PAPERTRAIL_PORT'),
    ],
],
```

---

## 5. Alerting Strategy

### Alert Levels

| Level | Response Time | Notification Channels |
|-------|---------------|----------------------|
| Critical | Immediate | SMS, Phone, Email, Slack |
| High | 15 minutes | Email, Slack |
| Medium | 1 hour | Email |
| Low | Next day | Email |

### Alert Rules

#### Critical Alerts
- API completely down (> 3 minutes)
- Database connection lost
- Payment processing failures
- Data corruption detected

#### High Priority
- API response time > 2s (p95)
- Error rate > 5%
- Queue backlog > 1000 jobs
- Disk space > 90%

#### Medium Priority
- API response time > 1s (p95)
- Error rate > 2%
- Queue backlog > 500 jobs
- Memory usage > 80%

#### Low Priority
- Slow queries detected
- Cache hit rate < 70%
- Log warnings

### Slack Integration

**config/logging.php**:
```php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'level' => 'critical',
    'emoji' => ':boom:',
],
```

**Send Critical Alerts**:
```php
Log::channel('slack')->critical('API Down!', [
    'url' => 'https://api.yourdomain.com',
    'status' => 'unreachable',
    'time' => now(),
]);
```

---

## 6. Business Metrics

### Order Metrics
- Orders per hour/day
- Average order value
- Conversion rate
- Cart abandonment rate
- Payment success rate

### Customer Metrics
- New registrations per day
- Active users
- Customer lifetime value
- Churn rate

### Store Performance
- Revenue per store
- Orders per store
- Active products per store
- Top-performing stores

### Custom Metrics Tracking

```php
// Track custom business metrics
Log::channel('metrics')->info('order_placed', [
    'metric' => 'order_count',
    'value' => 1,
    'store_id' => $order->store_id,
    'amount' => $order->total_amount,
    'timestamp' => now(),
]);
```

---

## 7. Dashboard Setup

### Laravel Nova (Admin Dashboard)

```bash
composer require laravel/nova
php artisan nova:install
```

### Metrics to Display

1. **System Health**
   - API uptime %
   - Average response time
   - Error rate
   - Active users

2. **Business Overview**
   - Total orders today
   - Revenue today
   - New customers
   - Active stores

3. **Performance**
   - Slowest endpoints
   - Most common errors
   - Cache hit/miss ratio
   - Queue depth

---

## Implementation Checklist

### Phase 1: Essential Monitoring (Week 1)
- [ ] Install Sentry for error tracking
- [ ] Configure UptimeRobot for uptime monitoring
- [ ] Create health check endpoints
- [ ] Set up Slack alerts for critical issues
- [ ] Enable Laravel logs with daily rotation

### Phase 2: Performance Monitoring (Week 2)
- [ ] Install Laravel Telescope (dev environment)
- [ ] Configure performance logging middleware
- [ ] Set up slow query logging
- [ ] Implement custom metrics tracking
- [ ] Create monitoring dashboard

### Phase 3: Advanced Observability (Week 3-4)
- [ ] Set up log aggregation (Papertrail/Logtail)
- [ ] Configure APM tool (optional)
- [ ] Implement business metrics tracking
- [ ] Create custom Grafana/Nova dashboards
- [ ] Document monitoring runbook

---

## Maintenance & Review

### Daily
- Check error rate in Sentry
- Review critical alerts
- Monitor uptime status

### Weekly
- Review performance trends
- Analyze slow queries
- Check disk/memory usage
- Update alert thresholds

### Monthly
- Review and prune old logs
- Analyze incident patterns
- Update monitoring documentation
- Optimize slow endpoints

---

## Related Documentation
- [docs/20-laravel-telescope-setup.md](20-laravel-telescope-setup.md) - Telescope setup guide
- [docs/08-scalability.md](08-scalability.md) - Performance optimization
- [docs/21-production-deployment.md](21-production-deployment.md) - Deployment guide
