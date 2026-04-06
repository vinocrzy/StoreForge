# Phone Number Authentication Strategy

## Overview

Phone numbers are the **primary authentication method** for the platform, with email as a secondary option. Phone numbers are **mandatory** for all users and customers, particularly during order checkout.

## Why Phone-First Authentication?

1. **Better Conversion**: Customers more willing to share phone than email
2. **Direct Communication**: WhatsApp/SMS for order updates
3. **Fraud Prevention**: Phone verification reduces fake accounts
4. **Regional Preference**: Many markets prefer phone-based login
5. **OTP Support**: Easy SMS/WhatsApp OTP integration

## Implementation Strategy

### Database Schema

#### Users Table (Admin/Staff)
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,                    -- REQUIRED, unique per store
    email VARCHAR(255) NOT NULL,                   -- REQUIRED for admin communications
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,              -- NEW: Track phone verification
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'manager', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    avatar_url VARCHAR(500) NULL,
    last_login_at TIMESTAMP NULL,
    last_login_method VARCHAR(20) DEFAULT 'phone',  -- 'phone' or 'email'
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_phone (store_id, phone),   -- Phone unique per store
    UNIQUE KEY uk_store_email (store_id, email),   -- Email unique per store
    INDEX idx_phone (phone),                        -- For login lookup
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Customers Table
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,                    -- REQUIRED, unique per store
    email VARCHAR(255) NOT NULL,                   -- REQUIRED for order confirmations
    password VARCHAR(255) NULL,                    -- NULL for guest checkout with phone
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birthday DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    phone_verified_at TIMESTAMP NULL,              -- NEW: Track phone verification
    email_verified_at TIMESTAMP NULL,
    accepts_marketing BOOLEAN DEFAULT FALSE,
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(10, 2) DEFAULT 0.00,
    average_order_value DECIMAL(10, 2) DEFAULT 0.00,
    last_order_at TIMESTAMP NULL,
    tags JSON NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_phone (store_id, phone),   -- Phone unique per store (PRIMARY)
    UNIQUE KEY uk_store_email (store_id, email),   -- Email unique per store (SECONDARY)
    INDEX idx_phone (phone),                        -- For login lookup (prioritized)
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Customer Addresses
```sql
CREATE TABLE customer_addresses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    type ENUM('shipping', 'billing', 'both') DEFAULT 'shipping',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(255) NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(2) NOT NULL,
    phone VARCHAR(20) NOT NULL,                    -- REQUIRED for delivery
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Authentication Flow

### Admin Login (Phone-First)

**Priority 1: Login with Phone**
```http
POST /api/v1/admin/auth/login
Content-Type: application/json

{
  "phone": "+1234567890",
  "password": "password123"
}
```

**Priority 2: Login with Email (Fallback)**
```http
POST /api/v1/admin/auth/login
Content-Type: application/json

{
  "email": "admin@store.com",
  "password": "password123"
}
```

**Auto-Detection: Login with Phone or Email**
```http
POST /api/v1/admin/auth/login
Content-Type: application/json

{
  "login": "+1234567890",  // or "admin@store.com"
  "password": "password123"
}
```

**Backend Logic**:
```php
public function login(Request $request)
{
    $request->validate([
        'login' => 'required|string',  // Accept phone or email
        'password' => 'required|string',
    ]);
    
    $loginField = $this->detectLoginField($request->login);
    
    $user = User::where($loginField, $request->login)->first();
    
    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'login' => ['The provided credentials are incorrect.'],
        ]);
    }
    
    // Update last login method
    $user->update([
        'last_login_at' => now(),
        'last_login_method' => $loginField,
    ]);
    
    $token = $user->createToken($request->device_name ?? 'unknown')->plainTextToken;
    
    return response()->json([
        'user' => $user,
        'token' => $token,
        'stores' => $user->stores,
    ]);
}

private function detectLoginField(string $login): string
{
    // Check if input is phone number (contains only digits, +, -, spaces)
    if (preg_match('/^[\d\s\-\+\(\)]+$/', $login)) {
        return 'phone';
    }
    
    // Check if input is email
    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        return 'email';
    }
    
    // Default to phone for ambiguous cases
    return 'phone';
}
```

### Customer Registration/Login (Phone-First)

**Storefront Registration**
```http
POST /api/v1/storefront/auth/register
Content-Type: application/json

{
  "phone": "+1234567890",       // REQUIRED
  "email": "john@example.com",  // REQUIRED
  "first_name": "John",          // REQUIRED
  "last_name": "Doe",            // REQUIRED
  "password": "password123"      // REQUIRED
}
```

**Validation Rules**:
```php
$request->validate([
    'phone' => 'required|string|max:20|unique:customers,phone,NULL,id,store_id,' . tenant()->id,
    'email' => 'required|email|max:255|unique:customers,email,NULL,id,store_id,' . tenant()->id,
    'first_name' => 'required|string|max:100',
    'last_name' => 'required|string|max:100',
    'password' => 'required|string|min:8|confirmed',
]);
```

**Login (Phone Priority)**
```http
POST /api/v1/storefront/auth/login
Content-Type: application/json

{
  "login": "+1234567890",  // or "john@example.com"
  "password": "password123"
}
```

## Checkout Flow (Phone Required)

### Guest Checkout
Even for guest checkout, phone number is **mandatory**:

```http
POST /api/v1/storefront/checkout
Content-Type: application/json

{
  "customer": {
    "phone": "+1234567890",      // REQUIRED
    "email": "guest@example.com", // REQUIRED
    "first_name": "John",
    "last_name": "Doe"
  },
  "shipping_address": {
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890",      // REQUIRED for delivery
    "address_line1": "123 Main St",
    "city": "New York",
    "postal_code": "10001",
    "country": "US"
  },
  "billing_address": {
    // Same as shipping or different
    "phone": "+1234567890"       // REQUIRED
  },
  "payment_method": "manual",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

**Validation**:
```php
$request->validate([
    'customer.phone' => 'required|string|max:20',
    'customer.email' => 'required|email|max:255',
    'customer.first_name' => 'required|string|max:100',
    'customer.last_name' => 'required|string|max:100',
    'shipping_address.phone' => 'required|string|max:20',
    'billing_address.phone' => 'required|string|max:20',
    // ... other fields
]);
```

### Registered Customer Checkout
```http
POST /api/v1/storefront/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "shipping_address_id": 1,  // Must have phone in address
  "billing_address_id": 1,   // Must have phone in address
  "payment_method": "manual",
  "items": [...]
}
```

**Pre-validation**: Ensure selected addresses have phone numbers:
```php
$shippingAddress = $customer->addresses()->find($request->shipping_address_id);

if (!$shippingAddress || !$shippingAddress->phone) {
    return response()->json([
        'message' => 'Shipping address must include a phone number'
    ], 422);
}
```

## Phone Number Format

### Storage Format
Store phone numbers in **E.164 format** (international standard):
- Format: `+[country code][number]`
- Examples: `+12025551234`, `+918765432100`, `+442071234567`
- No spaces, dashes, or parentheses

### Display Format
Format for display based on user's locale:
- US: `+1 (202) 555-1234`
- India: `+91 87654 32100`
- UK: `+44 20 7123 4567`

### Validation
```php
use libphonenumber\PhoneNumberUtil;

public function validatePhone(string $phone, string $country = null): bool
{
    try {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $numberProto = $phoneUtil->parse($phone, $country);
        return $phoneUtil->isValidNumber($numberProto);
    } catch (\Exception $e) {
        return false;
    }
}

// Normalize to E.164
public function normalizePhone(string $phone, string $country = 'US'): string
{
    $phoneUtil = PhoneNumberUtil::getInstance();
    $numberProto = $phoneUtil->parse($phone, $country);
    return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
}
```

**Composer Package**:
```bash
composer require giggsey/libphonenumber-for-php
```

## OTP Verification (Future Phase)

### SMS OTP Flow
```http
# Step 1: Request OTP
POST /api/v1/auth/send-otp
{
  "phone": "+1234567890"
}

Response:
{
  "message": "OTP sent to +1234567890",
  "expires_at": "2026-04-06T10:10:00Z"
}

# Step 2: Verify OTP
POST /api/v1/auth/verify-otp
{
  "phone": "+1234567890",
  "otp": "123456"
}

Response:
{
  "token": "...",
  "user": {...}
}
```

**OTP Storage**:
```sql
CREATE TABLE otp_verifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(20) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_phone_expires (phone, expires_at)
);
```

## Migration Path

### Step 1: Update Migrations (Immediate)
```bash
php artisan make:migration update_users_phone_required
php artisan make:migration update_customers_phone_required
php artisan make:migration update_addresses_phone_required
```

### Step 2: Update Seeders
Ensure all test users/customers have phone numbers:
```php
User::create([
    'name' => 'Admin User',
    'phone' => '+12025551001',  // REQUIRED
    'email' => 'admin@store.com',
    'password' => Hash::make('password'),
]);
```

### Step 3: Update Validation Rules
Add phone validation to all controllers:
```php
// AuthController, CheckoutController, etc.
'phone' => 'required|string|max:20'
```

### Step 4: Frontend Updates
- Add phone number field to login forms
- Make phone input primary (top of form)
- Add phone validation and formatting
- Support international phone input

## API Endpoints Summary

### Admin Authentication
```
POST   /api/v1/admin/auth/login           # Phone or email login
POST   /api/v1/admin/auth/register        # Phone + email required
PUT    /api/v1/admin/auth/update-phone    # Update phone number
POST   /api/v1/admin/auth/verify-phone    # Verify phone via OTP
```

### Customer Authentication
```
POST   /api/v1/storefront/auth/login      # Phone or email login
POST   /api/v1/storefront/auth/register   # Phone + email required
POST   /api/v1/storefront/auth/send-otp   # Request OTP (future)
POST   /api/v1/storefront/auth/verify-otp # Verify OTP (future)
PUT    /api/v1/storefront/profile/phone   # Update phone number
```

### Checkout
```
POST   /api/v1/storefront/checkout        # Phone required in all addresses
POST   /api/v1/storefront/checkout/guest  # Phone required for guest
```

## Security Considerations

1. **Rate Limiting**: Limit OTP requests to 3 per phone per 10 minutes
2. **Phone Verification**: Verify phone ownership before allowing orders
3. **Privacy**: Don't expose full phone numbers in public APIs
4. **Unique Constraints**: Phone unique per store (tenant isolation)
5. **Input Sanitization**: Validate and normalize phone format
6. **Fraud Prevention**: Flag suspicious phone patterns

## Testing Checklist

- [ ] Admin can login with phone number
- [ ] Admin can login with email (fallback)
- [ ] Customer can register with phone + email
- [ ] Customer can login with phone number
- [ ] Guest checkout requires phone number
- [ ] Shipping address requires phone number
- [ ] Billing address requires phone number
- [ ] Phone numbers are validated correctly
- [ ] Phone numbers are stored in E.164 format
- [ ] Phone numbers are unique per store
- [ ] OTP flow works for verification (future)

---

**Last Updated**: April 6, 2026  
**Status**: Phase 1 - Phone-first authentication active  
**Next Phase**: OTP verification (SMS/WhatsApp)
