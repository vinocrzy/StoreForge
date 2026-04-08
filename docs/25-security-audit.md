# Security Audit & Hardening Guide

## Overview

Comprehensive security audit checklist and hardening guide for the multi-tenant e-commerce platform. Covers OWASP Top 10 vulnerabilities, penetration testing, and security best practices.

**Security Goals**:
- Zero critical vulnerabilities
- OWASP Top 10 compliance
- PCI DSS compliance (if handling cards)
- GDPR compliance
- Regular security audits

---

## 1. OWASP Top 10 Security Checklist

### A01: Broken Access Control

**✅ Implemented Protections**:
- [x] API authentication (Laravel Sanctum)
- [x] Tenant isolation (X-Store-ID header validation)
- [x] Role-based permissions (Spatie permissions)
- [x] Global scopes for multi-tenant models
- [x] Authorization gates and policies

**Verification**:
```php
// Test: User cannot access another store's data
$store1 = Store::factory()->create();
$store2 = Store::factory()->create();
$product = Product::factory()->create(['store_id' => $store2->id]);

$this->actingAs($user1, 'sanctum')
    ->withHeader('X-Store-ID', $store1->id)
    ->getJson("/api/v1/products/{$product->id}")
    ->assertNotFound(); // ✅ Should return 404
```

**Additional Steps**:
```php
// Always validate ownership
public function update(Request $request, Product $product)
{
    // ✅ Good - Check ownership
    if ($product->store_id !== tenant()->id) {
        abort(403, 'Unauthorized');
    }
    
    $product->update($request->validated());
    return new ProductResource($product);
}

// ❌ Bad - No ownership check
public function update(Request $request, Product $product)
{
    $product->update($request->validated());
    return new ProductResource($product);
}
```

---

### A02: Cryptographic Failures

**✅ Implemented Protections**:
- [x] HTTPS enforced (SSL/TLS certificates)
- [x] Password hashing (bcrypt with cost 12)
- [x] Encrypted session data
- [x] Secure cookie settings
- [x] Environment variables for secrets

**Verification**:
```php
// .env - Never commit to Git
APP_KEY=base64:GENERATED_KEY_HERE
DB_PASSWORD=STRONG_PASSWORD
REDIS_PASSWORD=LONG_RANDOM_PASSWORD
AWS_SECRET_ACCESS_KEY=SECRET_KEY

// config/session.php
'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
'http_only' => true, // Prevent JavaScript access
'same_site' => 'lax', // CSRF protection
```

**Additional Steps**:
```bash
# Generate strong application key
php artisan key:generate

# Never store secrets in code
# ❌ Bad
$apiKey = 'sk_live_abc123';

# ✅ Good
$apiKey = env('STRIPE_SECRET_KEY');
```

---

### A03: Injection

**✅ Implemented Protections**:
- [x] Parameterized queries (Eloquent ORM)
- [x] Input validation (Form Requests)
- [x] SQL injection prevention
- [x] XSS prevention (Blade escaping)
- [x] Command injection prevention

**SQL Injection Prevention**:
```php
// ✅ Good - Parameterized queries
$products = DB::table('products')
    ->where('store_id', $storeId)
    ->where('status', $status)
    ->get();

// ✅ Good - Eloquent ORM
$products = Product::where('store_id', $storeId)
    ->where('status', $status)
    ->get();

// ❌ Bad - SQL injection risk
$products = DB::select("SELECT * FROM products WHERE store_id = {$storeId}");

// ❌ Bad - Dangerous
$products = DB::select($request->input('query'));
```

**XSS Prevention**:
```blade
{{-- ✅ Good - Blade escapes by default --}}
<h1>{{ $product->name }}</h1>

{{-- ⚠️ Dangerous - Raw HTML --}}
<div>{!! $product->description !!}</div>

{{-- ✅ Better - Sanitize HTML --}}
<div>{!! clean($product->description) !!}</div>
```

**Install HTML Purifier**:
```bash
composer require mews/purifier
```

**Sanitize HTML**:
```php
use Mews\Purifier\Facades\Purifier;

$cleanHtml = Purifier::clean($dirtyHtml);
```

---

### A04: Insecure Design

**✅ Implemented Protections**:
- [x] Rate limiting (60/min auth, 10/min guest)
- [x] Input validation on all endpoints
- [x] Business logic in service classes
- [x] Atomic database transactions
- [x] Idempotency keys for critical operations

**Rate Limiting**:
```php
// RateLimiter configuration
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Too many login attempts. Try again in 1 minute.',
            ], 429);
        });
});

// Apply to routes
Route::post('/login')->middleware('throttle:login');
```

**Idempotency for Payment Processing**:
```php
public function processPayment(Request $request, Order $order)
{
    $idempotencyKey = $request->header('Idempotency-Key');
    
    if (!$idempotencyKey) {
        return response()->json(['error' => 'Idempotency-Key required'], 400);
    }
    
    // Check if already processed
    $cached = Cache::get("payment:{$idempotencyKey}");
    if ($cached) {
        return response()->json($cached);
    }
    
    // Process payment
    DB::transaction(function () use ($order, $idempotencyKey) {
        $result = $this->paymentService->charge($order);
        
        // Cache result for 24 hours
        Cache::put("payment:{$idempotencyKey}", $result, 86400);
        
        return $result;
    });
}
```

---

### A05: Security Misconfiguration

**✅ Implemented Protections**:
- [x] APP_DEBUG=false in production
- [x] Error reporting disabled in production
- [x] Security headers configured
- [x] Unnecessary services disabled
- [x] Default credentials changed

**Security Headers**:
```php
// app/Http/Middleware/SecurityHeaders.php
public function handle(Request $request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");
    
    return $response;
}
```

**Environment Check**:
```bash
# ✅ Production .env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# ❌ Development settings in production
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
```

---

### A06: Vulnerable and Outdated Components

**Dependency Scanning**:
```bash
# Composer security audit
composer audit

# Check for vulnerabilities
composer outdated

# Update dependencies (with testing)
composer update --with-dependencies
```

**Automated Scanning**:
```yaml
# .github/workflows/security.yml
name: Security Audit

on:
  push:
    branches: [ main ]
  schedule:
    - cron: '0 0 * * 0'  # Weekly

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install
      
      - name: Security audit
        run: composer audit
      
      - name: SAST scan
        run: ./vendor/bin/phpstan analyse
```

**Keep Dependencies Updated**:
```json
{
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

---

### A07: Identification and Authentication Failures

**✅ Implemented Protections**:
- [x] Strong password requirements
- [x] Password hashing (bcrypt, cost 12)
- [x] Token-based authentication (Sanctum)
- [x] Failed login attempt tracking
- [x] Account lockout after failures
- [x] Phone-first authentication

**Password Validation**:
```php
// app/Http/Requests/RegisterRequest.php
public function rules()
{
    return [
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(), // Check against data breaches
        ],
    ];
}
```

**Account Lockout**:
```php
// After 5 failed attempts, lock for 15 minutes
public function login(Request $request)
{
    $key = 'login_attempts:' . $request->ip();
    $attempts = Cache::get($key, 0);
    
    if ($attempts >= 5) {
        return response()->json([
            'message' => 'Too many failed attempts. Try again in 15 minutes.',
        ], 429);
    }
    
    if (!Auth::attempt($request->only('login', 'password'))) {
        Cache::put($key, $attempts + 1, now()->addMinutes(15));
        
        return response()->json([
            'message' => 'Invalid credentials',
        ], 422);
    }
    
    // Clear attempts on success
    Cache::forget($key);
    
    return $this->respondWithToken(Auth::user());
}
```

---

### A08: Software and Data Integrity Failures

**✅ Implemented Protections**:
- [x] Composer lock file committed
- [x] Package signature verification
- [x] CI/CD pipeline with tests
- [x] Code review process
- [x] Git commit signing

**Verify Package Integrity**:
```bash
# Check Composer lock file
git diff composer.lock

# Verify signatures
composer validate

# Install with audit
composer install --audit
```

---

### A09: Security Logging and Monitoring Failures

**✅ Implemented Protections**:
- [x] Application logging (Laravel logs)
- [x] Error tracking (Sentry)
- [x] Access logging (Nginx)
- [x] Failed login tracking
- [x] Anomaly detection
- [x] Real-time alerts

**Security Event Logging**:
```php
// Log security events
Log::channel('security')->warning('Failed login attempt', [
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'login' => $request->input('login'),
    'timestamp' => now(),
]);

Log::channel('security')->critical('Unauthorized access attempt', [
    'user_id' => auth()->id(),
    'resource' => $request->path(),
    'store_id' => $request->header('X-Store-ID'),
]);
```

**Log Configuration**:
```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90, // Keep for 90 days
    ],
],
```

---

### A10: Server-Side Request Forgery (SSRF)

**✅ Implemented Protections**:
- [x] Whitelist allowed domains for external requests
- [x] Validate URLs before making requests
- [x] Disable URL redirects
- [x] Use signed URLs for webhooks

**Safe External Requests**:
```php
use Illuminate\Support\Facades\Http;

// ✅ Good - Whitelist domains
$allowedDomains = ['api.stripe.com', 'api.paypal.com'];

public function fetchExternalData(string $url)
{
    $domain = parse_url($url, PHP_URL_HOST);
    
    if (!in_array($domain, $allowedDomains)) {
        throw new InvalidArgumentException('Domain not whitelisted');
    }
    
    return Http::withoutRedirecting()
        ->timeout(5)
        ->get($url);
}

// ❌ Bad - User-controlled URL
public function fetchData(Request $request)
{
    return Http::get($request->input('url'));
}
```

---

## 2. File Upload Security

### Validation Rules

```php
public function uploadImage(Request $request)
{
    $request->validate([
        'image' => [
            'required',
            'file',
            'image', // Only image MIME types
            'mimes:jpeg,png,jpg,gif,webp',
            'max:5120', // 5MB
            'dimensions:max_width=4000,max_height=4000',
        ],
    ]);
    
    // Generate random filename
    $filename = Str::uuid() . '.' . $request->file('image')->extension();
    
    // Store outside public directory
    $path = $request->file('image')->storeAs('products', $filename, 's3');
    
    return response()->json(['path' => $path]);
}
```

### Prevent Malicious Files

```php
// Check real MIME type (not extension)
$mimeType = $request->file('image')->getMimeType();

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mimeType, $allowedMimes)) {
    throw ValidationException::withMessages([
        'image' => ['Invalid file type detected'],
    ]);
}

// Scan for viruses (optional)
// Use ClamAV or similar
```

---

## 3. API Security

### CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS')),
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Store-ID', 'X-Requested-With'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];
```

### API Rate Limiting

```php
// Aggressive rate limiting for sensitive endpoints
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});

// Apply to routes
Route::post('/login')->middleware('throttle:auth');
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // API routes
});
```

---

## 4. Database Security

### Prepared Statements

```php
// ✅ Good - Always use parameter binding
$products = DB::table('products')
    ->where('store_id', $storeId)
    ->where('name', 'like', '%' . $search . '%')
    ->get();

// ❌ Bad - SQL injection risk
$products = DB::select("SELECT * FROM products WHERE name LIKE '%{$search}%'");
```

### Database Credentials

```bash
# Use least privilege principle
# Create read-only user for reporting
mysql> CREATE USER 'report_user'@'localhost' IDENTIFIED BY 'password';
mysql> GRANT SELECT ON ecommerce_prod.* TO 'report_user'@'localhost';

# Separate user for backups
mysql> CREATE USER 'backup_user'@'localhost' IDENTIFIED BY 'password';
mysql> GRANT SELECT, LOCK TABLES ON ecommerce_prod.* TO 'backup_user'@'localhost';
```

### Soft Deletes

```php
// Use soft deletes for sensitive data
Schema::table('customers', function (Blueprint $table) {
    $table->softDeletes();
});

// Model
class Customer extends Model
{
    use SoftDeletes;
}

// Hard delete only after legal retention period
Customer::onlyTrashed()
    ->where('deleted_at', '<', now()->subYears(7))
    ->forceDelete();
```

---

## 5. GDPR Compliance

### Data Privacy

**Right to Access**:
```php
public function exportData(Request $request)
{
    $customer = Customer::with([
        'orders.items',
        'addresses',
    ])->findOrFail($request->user()->id);
    
    return response()->json($customer);
}
```

**Right to Erasure**:
```php
public function deleteAccount(Request $request)
{
    $customer = $request->user();
    
    DB::transaction(function () use ($customer) {
        // Anonymize orders (keep for records)
        $customer->orders()->update([
            'customer_name' => 'Deleted User',
            'customer_email' => 'deleted@example.com',
            'customer_phone' => null,
        ]);
        
        // Delete addresses
        $customer->addresses()->delete();
        
        // Soft delete customer
        $customer->delete();
    });
    
    return response()->json(['message' => 'Account deleted successfully']);
}
```

### Cookie Consent

```javascript
// Frontend cookie consent banner
if (!localStorage.getItem('cookie_consent')) {
  showCookieConsent();
}

function acceptCookies() {
  localStorage.setItem('cookie_consent', 'true');
  enableAnalytics();
}
```

---

## 6. Penetration Testing

### Automated DAST Scanning

**OWASP ZAP**:
```bash
# Docker-based scan
docker run -t owasp/zap2docker-stable zap-baseline.py \
  -t https://api.yourdomain.com \
  -r zap_report.html
```

**Burp Suite**:
1. Configure browser proxy
2. Navigate through application
3. Run active scan
4. Review vulnerabilities

### Manual Testing Checklist

**Authentication**:
- [ ] Test password reset flow
- [ ] Test account lockout
- [ ] Test session timeout
- [ ] Test concurrent sessions
- [ ] Test token expiration

**Authorization**:
- [ ] Test horizontal privilege escalation
- [ ] Test vertical privilege escalation
- [ ] Test tenant isolation
- [ ] Test API endpoint authorization

**Input Validation**:
- [ ] Test SQL injection (all inputs)
- [ ] Test XSS (all text fields)
- [ ] Test file upload (malicious files)
- [ ] Test CSRF protection
- [ ] Test parameter tampering

**Business Logic**:
- [ ] Test negative prices
- [ ] Test race conditions
- [ ] Test payment bypass
- [ ] Test discount abuse
- [ ] Test inventory manipulation

---

## 7. Security Headers Testing

**Check Headers**:
```bash
curl -I https://api.yourdomain.com

# Should include:
# - Strict-Transport-Security
# - X-Content-Type-Options: nosniff
# - X-Frame-Options: SAMEORIGIN
# - X-XSS-Protection: 1; mode=block
# - Content-Security-Policy
```

**Online Tools**:
- [SecurityHeaders.com](https://securityheaders.com)
- [Mozilla Observatory](https://observatory.mozilla.org)
- [SSL Labs](https://www.ssllabs.com/ssltest/)

---

## 8. Incident Response Plan

### Detection

**Monitor for**:
- Multiple failed login attempts
- Unusual API access patterns
- Spike in error rates
- Unauthorized access attempts
- Data exfiltration attempts

### Response Steps

1. **Identify**: Confirm security incident
2. **Contain**: Isolate affected systems
3. **Eradicate**: Remove threat
4. **Recover**: Restore systems
5. **Lessons Learned**: Post-mortem analysis

### Breach Notification

**EU GDPR**: 72 hours to report data breach  
**US States**: Varies by state (30-90 days)

---

## 9. Security Audit Checklist

### Pre-Launch Security Audit

**Infrastructure**:
- [ ] SSL/TLS certificates valid and configured
- [ ] Firewall rules configured (only 80, 443, 22 open)
- [ ] SSH key-only authentication
- [ ] Automatic security updates enabled
- [ ] Fail2ban installed and configured
- [ ] Database not publicly accessible
- [ ] Redis password protected

**Application**:
- [ ] APP_DEBUG=false in production
- [ ] Error reporting disabled
- [ ] All secrets in environment variables
- [ ] Rate limiting enabled
- [ ] CORS configured correctly
- [ ] Security headers implemented
- [ ] Input validation on all endpoints
- [ ] SQL injection prevention verified
- [ ] XSS prevention verified
- [ ] CSRF protection enabled

**Authentication & Authorization**:
- [ ] Strong password requirements
- [ ] Password hashing (bcrypt, cost >= 12)
- [ ] Token-based authentication (Sanctum)
- [ ] Account lockout after failed attempts
- [ ] Session timeout configured
- [ ] Tenant isolation verified (critical!)

**Data Protection**:
- [ ] Encryption at rest (database, files)
- [ ] Encryption in transit (HTTPS)
- [ ] Sensitive data not logged
- [ ] PII handling compliant
- [ ] Data backup encryption
- [ ] Soft deletes for sensitive data

**Monitoring & Logging**:
- [ ] Error tracking enabled (Sentry)
- [ ] Security event logging
- [ ] Failed login tracking
- [ ] Uptime monitoring (UptimeRobot)
- [ ] Log aggregation configured
- [ ] Alerts configured

**Dependencies**:
- [ ] All dependencies up to date
- [ ] Composer audit passing
- [ ] No known vulnerabilities
- [ ] No unused dependencies

**Third-Party Integrations**:
- [ ] API keys secured
- [ ] Webhook signatures verified
- [ ] OAuth scopes minimized
- [ ] Payment gateway PCI compliant

---

## 10. Security Best Practices

### Development

**✅ DO**:
- Use parameterized queries
- Validate all input
- Escape all output
- Use HTTPS everywhere
- Keep dependencies updated
- Code review all changes
- Write security tests
- Use strong passwords
- Enable 2FA for admin accounts

**❌ DON'T**:
- Commit secrets to Git
- Use default credentials
- Trust user input
- Disable security features
- Use deprecated functions
- Store passwords in plain text
- Skip input validation
- Use weak encryption

### Regular Maintenance

**Weekly**:
- Review security logs
- Check for failed login attempts
- Monitor error rates

**Monthly**:
- Update dependencies
- Review access permissions
- Audit user accounts
- Check SSL certificate expiration

**Quarterly**:
- Penetration testing
- Security audit
- Disaster recovery drill
- Update security documentation

---

## Related Documentation
- [docs/09-security.md](09-security.md) - Security strategy
- [docs/22-production-configuration.md](22-production-configuration.md) - Security config
- [docs/21-monitoring-strategy.md](21-monitoring-strategy.md) - Security monitoring
