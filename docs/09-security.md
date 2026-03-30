# Security Guidelines

## Overview

Security is paramount for an e-commerce platform handling sensitive customer data and financial transactions. This document outlines security best practices and implementation guidelines.

## Authentication & Authorization

### 1. Password Security

**Requirements**:
- Minimum 8 characters
- Must include uppercase, lowercase, number, and special character
- Password strength meter on registration
- Bcrypt hashing with cost factor 12

```php
// Laravel implementation
protected function password(): string
{
    return Hash::make($this->password, ['rounds' => 12]);
}

// Validation
'password' => [
    'required',
    'string',
    'min:8',
    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
    'confirmed'
],
```

### 2. Token Management

**API Tokens**:
- Use Laravel Sanctum for token-based auth
- Token expiration: 24 hours
- Automatic token rotation
- Secure token storage (httpOnly cookies for web, secure storage for mobile)

```php
// Generate token with expiry
$token = $user->createToken('api-token', ['*'], now()->addDay())->plainTextToken;

// Revoke token on logout
$user->currentAccessToken()->delete();

// Revoke all tokens
$user->tokens()->delete();
```

### 3. Multi-Factor Authentication (MFA)

**Implementation** (Future enhancement):
- TOTP-based 2FA
- SMS-based 2FA
- Backup codes
- Recovery options

## Data Protection

### 1. Encryption

**At Rest**:
```php
// Encrypt sensitive fields
protected $casts = [
    'ssn' => 'encrypted',
    'credit_card' => 'encrypted',
];

// Database encryption
ALTER TABLE customers 
MODIFY COLUMN ssn VARBINARY(255);
```

**In Transit**:
- Enforce HTTPS/TLS 1.3
- HSTS headers
- Secure WebSocket connections (WSS)

```php
// Force HTTPS in production
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

### 2. PII (Personally Identifiable Information)

**Handling**:
- Minimize data collection
- Encrypt sensitive data
- Provide data export (GDPR)
- Implement data deletion (right to be forgotten)
- Audit logs for PII access

```php
// Customer data export
public function exportData(Customer $customer): array
{
    return [
        'profile' => $customer->only(['email', 'name', 'phone']),
        'orders' => $customer->orders()->get(),
        'addresses' => $customer->addresses()->get(),
    ];
}

// Customer data deletion
public function deleteData(Customer $customer): void
{
    DB::transaction(function () use ($customer) {
        // Anonymize instead of hard delete when orders exist
        if ($customer->orders()->exists()) {
            $customer->update([
                'email' => 'deleted_' . $customer->id . '@anonymized.com',
                'first_name' => 'Deleted',
                'last_name' => 'User',
                'phone' => null,
            ]);
            $customer->addresses()->delete();
        } else {
            $customer->forceDelete();
        }
    });
}
```

### 3. Payment Security

**PCI DSS Compliance**:
- Never store full credit card numbers
- Never store CVV codes
- Use payment gateway tokenization
- Implement 3D Secure (SCA)

```php
// Use Stripe for payment processing
$paymentIntent = $stripe->paymentIntents->create([
    'amount' => $order->total * 100,
    'currency' => 'usd',
    'customer' => $customer->stripe_id,
    'payment_method' => $paymentMethodId,
    'confirmation_method' => 'manual',
    'confirm' => true,
    'payment_method_options' => [
        'card' => [
            'request_three_d_secure' => 'automatic',
        ],
    ],
]);

// Store only payment method ID, not card details
$customer->update([
    'stripe_payment_method_id' => $paymentMethodId,
]);
```

## Input Validation & Sanitization

### 1. Request Validation

```php
// Form Request validation
class CreateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:draft,active,archived'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }
}
```

### 2. XSS Protection

```php
// Sanitize HTML input
use Illuminate\Support\Str;

$clean = Str::of($input)->trim()->stripTags()->toString();

// For rich text, use HTML Purifier
use HTMLPurifier;

$purifier = new HTMLPurifier();
$clean = $purifier->purify($input);
```

### 3. SQL Injection Prevention

```php
// Good - Using Eloquent ORM (parameterized)
Product::where('store_id', $storeId)
    ->where('status', $status)
    ->get();

// Good - Using Query Builder with bindings
DB::table('products')
    ->where('store_id', '=', $storeId)
    ->where('status', '=', $status)
    ->get();

// Bad - Raw SQL with concatenation
DB::select("SELECT * FROM products WHERE status = '$status'"); // Vulnerable!

// Good - Raw SQL with bindings
DB::select("SELECT * FROM products WHERE status = ?", [$status]);
```

## CSRF Protection

**Laravel Built-in Protection**:

```php
// Automatic for web routes
Route::middleware(['web'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
});

// Blade templates automatically include CSRF token
<form method="POST" action="/orders">
    @csrf
    <!-- form fields -->
</form>

// API routes exempt (use Sanctum instead)
Route::middleware(['api'])->group(function () {
    Route::post('/api/orders', [OrderController::class, 'store']);
});
```

## Rate Limiting

### 1. API Rate Limits

```php
// Global rate limiting
Route::middleware(['throttle:60,1'])->group(function () {
    // 60 requests per minute
});

// Custom rate limiting per user/tenant
RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(120)->by($request->user()->id)
        : Limit::perMinute(60)->by($request->ip());
});

// Apply custom limiter
Route::middleware(['throttle:api'])->group(function () {
    // routes
});
```

### 2. Login Attempt Limiting

```php
// Built-in throttling for login
use Illuminate\Foundation\Auth\ThrottlesLogins;

class LoginController extends Controller
{
    use ThrottlesLogins;

    protected $maxAttempts = 5;        // Max login attempts
    protected $decayMinutes = 15;      // Lockout duration
}

// Custom implementation
if (RateLimiter::tooManyAttempts('login:' . $request->ip(), 5)) {
    $seconds = RateLimiter::availableIn('login:' . $request->ip());
    
    throw ValidationException::withMessages([
        'email' => ["Too many login attempts. Try again in {$seconds} seconds."],
    ]);
}

RateLimiter::hit('login:' . $request->ip(), 900); // 15 minutes
```

## Access Control

### 1. Policy-Based Authorization

```php
// Product Policy
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->store_id === $product->store_id 
            && $user->can('products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->store_id === $product->store_id 
            && $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->store_id === $product->store_id 
            && $user->can('products.delete');
    }
}

// Controller usage
public function show(Product $product)
{
    $this->authorize('view', $product);
    
    return new ProductResource($product);
}
```

### 2. Tenant Isolation Enforcement

```php
// Middleware to verify tenant access
class ValidateTenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        $storeId = $request->header('X-Store-ID');
        $user = $request->user();
        
        if ($user && $user->store_id !== (int) $storeId) {
            Log::warning('Tenant access violation attempt', [
                'user_id' => $user->id,
                'user_store' => $user->store_id,
                'requested_store' => $storeId,
                'ip' => $request->ip(),
            ]);
            
            abort(403, 'Unauthorized store access');
        }
        
        return $next($request);
    }
}
```

## Secure Headers

```php
// config/secure-headers.php
return [
    'x-content-type-options' => 'nosniff',
    'x-frame-options' => 'SAMEORIGIN',
    'x-xss-protection' => '1; mode=block',
    'strict-transport-security' => 'max-age=31536000; includeSubDomains',
    'content-security-policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com",
    'referrer-policy' => 'strict-origin-when-cross-origin',
    'permissions-policy' => 'geolocation=(), microphone=(), camera=()',
];

// Middleware
class SecureHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        foreach (config('secure-headers') as $header => $value) {
            $response->headers->set($header, $value);
        }
        
        return $response;
    }
}
```

## Logging & Monitoring

### 1. Security Event Logging

```php
// Log security events
Log::channel('security')->warning('Failed login attempt', [
    'email' => $request->email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'timestamp' => now(),
]);

Log::channel('security')->alert('Unauthorized access attempt', [
    'user_id' => $user->id,
    'resource' => get_class($resource),
    'resource_id' => $resource->id,
    'action' => 'delete',
]);

// Sensitive data access logging
Log::channel('audit')->info('Customer PII accessed', [
    'accessed_by' => $user->id,
    'customer_id' => $customer->id,
    'data_type' => 'full_profile',
]);
```

### 2. Failed Login Monitoring

```php
// Alert on suspicious patterns
Event::listen(LoginFailed::class, function ($event) {
    $failedAttempts = Cache::increment("failed_login:{$event->credentials['email']}", 1, 3600);
    
    if ($failedAttempts >= 5) {
        Notification::route('mail', env('SECURITY_ALERT_EMAIL'))
            ->notify(new SuspiciousActivityDetected([
                'type' => 'multiple_failed_logins',
                'email' => $event->credentials['email'],
                'attempts' => $failedAttempts,
            ]));
    }
});
```

## File Upload Security

### 1. Validation

```php
'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB max
'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB max

// Custom validation for file content
$validator = Validator::make($request->all(), [
    'image' => ['required', 'image', function ($attribute, $value, $fail) {
        $image = getimagesize($value);
        if ($image === false) {
            $fail('The file is not a valid image.');
        }
    }],
]);
```

### 2. Secure Storage

```php
// Store with random name
$path = $request->file('avatar')->store('avatars', 'public');

// Or use hash name
$path = $request->file('avatar')->hashName('avatars');

// Never allow direct execution
// .htaccess in storage directory
<FilesMatch "\.(php|phtml|php3|php4|php5|phps)$">
    Deny from all
</FilesMatch>
```

## Dependency Management

### 1. Regular Updates

```bash
# Check for outdated packages
composer outdated

# Update dependencies
composer update

# Security audit
composer audit
```

### 2. Verified Packages Only

- Use packages from trusted sources
- Check package popularity and maintenance
- Review security advisories
- Pin specific versions in production

## Environment Security

### 1. Environment Variables

```env
# .env - Never commit to repository
APP_KEY=base64:generated_key_here
DB_PASSWORD=strong_password_here
STRIPE_SECRET=sk_live_xxx

# Use strong, unique values for production
APP_DEBUG=false
APP_ENV=production
```

### 2. Configuration Caching

```bash
# Cache config in production (prevents .env access)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Database Security

### 1. Principle of Least Privilege

```sql
-- Application user with limited permissions
CREATE USER 'ecom_app'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON ecommerce.* TO 'ecom_app'@'%';

-- No DROP, ALTER, or GRANT permissions
FLUSH PRIVILEGES;
```

### 2. Prepared Statements

Laravel Eloquent and Query Builder automatically use prepared statements, protecting against SQL injection.

## API Security

### 1. CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => [
        env('ADMIN_PANEL_URL'),
        env('STOREFRONT_URL'),
    ],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Store-ID'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 2. API Versioning

- Version all APIs (v1, v2)
- Deprecate old versions gradually
- Document breaking changes

## Incident Response

### 1. Security Incident Plan

1. **Detection**: Monitor logs, alerts
2. **Containment**: Isolate affected systems
3. **Eradication**: Remove threat, patch vulnerability
4. **Recovery**: Restore services
5. **Post-Incident**: Review and improve

### 2. Breach Notification

- Document GDPR compliance procedures
- Prepare customer notification templates
- Establish communication protocols

## Security Checklist

### Development
- [ ] Input validation on all user inputs
- [ ] Output escaping to prevent XSS
- [ ] CSRF protection enabled
- [ ] SQL injection protection (ORM usage)
- [ ] Secure password hashing (bcrypt)
- [ ] Rate limiting implemented
- [ ] Authorization checks on all routes
- [ ] Tenant isolation verified

### Deployment
- [ ] HTTPS/TLS configured
- [ ] Security headers set
- [ ] Debug mode disabled
- [ ] Error messages sanitized
- [ ] Default credentials changed
- [ ] Firewall configured
- [ ] Database access restricted
- [ ] Backup strategy implemented

### Monitoring
- [ ] Security logging enabled
- [ ] Failed login monitoring
- [ ] Unusual activity alerts
- [ ] Regular security scans
- [ ] Dependency vulnerability checks
- [ ] Penetration testing scheduled

## Compliance

### GDPR (EU)
- [ ] Data protection by design
- [ ] User consent management
- [ ] Right to access (data export)
- [ ] Right to be forgotten (data deletion)
- [ ] Data breach notification (<72 hours)
- [ ] Privacy policy published

### PCI DSS (Payment Card Industry)
- [ ] Never store full card numbers
- [ ] Never store CVV codes
- [ ] Use tokenization
- [ ] Encrypt cardholder data
- [ ] Maintain secure network
- [ ] Regular security testing

## Security Training

- Regular security training for all developers
- Security code review guidelines
- Incident response drills
- Stay updated on OWASP Top 10

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PCI DSS Requirements](https://www.pcisecuritystandards.org/)
- [GDPR Compliance](https://gdpr.eu/)

## Next Steps

1. Implement authentication system
2. Set up authorization policies
3. Configure security headers
4. Enable security logging
5. Schedule regular security audits
