# Database Schema Design

## Overview

This document outlines the complete database schema for the multi-tenant e-commerce platform. The schema follows a **single database, shared schema** approach with tenant isolation through `store_id` foreign keys.

## Schema Design Principles

1. **Multi-tenancy**: All tenant-specific tables include `store_id` column
2. **Soft Deletes**: Most tables support soft deletes for data recovery
3. **Timestamps**: All tables include `created_at` and `updated_at`
4. **UUID Support**: Optional UUID primary keys for distributed systems
5. **Indexing**: Strategic indexes for performance
6. **Normalized**: Follows 3NF with selective denormalization for performance

## Entity Relationship Diagram

```
┌──────────┐        ┌──────────┐        ┌──────────────┐
│  stores  │◄───────│ products │◄───────│product_images│
└────┬─────┘        └────┬─────┘        └──────────────┘
     │                   │
     │                   ├───────► categories (M:M)
     │                   │
     │                   ├───────► product_variants
     │                   │
     │                   └───────► inventories
     │
     ├───────► orders ────┬───► order_items
     │                    ├───► order_addresses
     │                    └───► payments
     │
     ├───────► customers ─┬───► customer_addresses
     │                    └───► wishlists
     │
     ├───────► promotions ───► promotion_rules
     │
     ├───────► coupons ──────► coupon_usages
     │
     └───────► offers ───────► offer_conditions
```

## Core Tables

### stores
Multi-tenant store/tenant table

```sql
CREATE TABLE stores (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    domain VARCHAR(255) NULL UNIQUE,
    subdomain VARCHAR(255) NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    description TEXT NULL,
    logo_url VARCHAR(500) NULL,
    favicon_url VARCHAR(500) NULL,
    status ENUM('active', 'inactive', 'suspended', 'trial') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    subscription_plan VARCHAR(50) NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    currency VARCHAR(3) DEFAULT 'USD',
    locale VARCHAR(10) DEFAULT 'en',
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### store_settings
Key-value store for store-specific settings

```sql
CREATE TABLE store_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    key VARCHAR(100) NOT NULL,
    value TEXT NULL,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_key (store_id, key),
    INDEX idx_store_id (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## User & Authentication Tables

### users
Admin and staff users

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NULL,  -- NULL for super admin
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,                    -- REQUIRED: Primary login method
    email VARCHAR(255) NOT NULL,                   -- REQUIRED: Secondary login + communications
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,              -- Track phone verification
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'manager', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    avatar_url VARCHAR(500) NULL,
    last_login_at TIMESTAMP NULL,
    last_login_method VARCHAR(20) DEFAULT 'phone', -- 'phone' or 'email'
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_phone (store_id, phone),   -- Phone unique per store
    UNIQUE KEY uk_store_email (store_id, email),   -- Email unique per store
    INDEX idx_phone (phone),                        -- For phone-based login (primary)
    INDEX idx_email (email),                        -- For email-based login (fallback)
    INDEX idx_store_id (store_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: Phone numbers are stored in E.164 format (+12025551234)
-- See docs/18-phone-authentication-strategy.md for complete implementation
```

### roles
RBAC roles

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NULL,  -- NULL for global roles
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_slug (store_id, slug),
    INDEX idx_store_id (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### permissions
RBAC permissions

```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    group VARCHAR(50) NULL,  -- products, orders, customers, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_group (group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### role_permissions
Many-to-many pivot table

```sql
CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### user_roles
Many-to-many pivot table

```sql
CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Product Tables

### categories
Product categories with nested set model

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_url VARCHAR(500) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    _lft INT NOT NULL DEFAULT 0,      -- Nested set left
    _rgt INT NOT NULL DEFAULT 0,      -- Nested set right
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    UNIQUE KEY uk_store_slug (store_id, slug),
    INDEX idx_store_id (store_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_nested_set (_lft, _rgt),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### products
Main products table

```sql
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    short_description TEXT NULL,
    description LONGTEXT NULL,
    price DECIMAL(10, 2) NOT NULL,
    compare_at_price DECIMAL(10, 2) NULL,  -- Original price for discount display
    cost_per_item DECIMAL(10, 2) NULL,     -- For profit calculation
    type ENUM('simple', 'variable', 'digital', 'bundle') DEFAULT 'simple',
    status ENUM('draft', 'active', 'archived') DEFAULT 'draft',
    featured BOOLEAN DEFAULT FALSE,
    is_taxable BOOLEAN DEFAULT TRUE,
    requires_shipping BOOLEAN DEFAULT TRUE,
    track_inventory BOOLEAN DEFAULT TRUE,
    weight DECIMAL(8, 2) NULL,             -- In store's weight unit
    weight_unit VARCHAR(10) DEFAULT 'kg',
    dimensions JSON NULL,                  -- {length, width, height, unit}
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    meta_keywords VARCHAR(500) NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_sku (store_id, sku),
    UNIQUE KEY uk_store_slug (store_id, slug),
    INDEX idx_store_id (store_id),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_price (price),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### product_categories
Many-to-many pivot table

```sql
CREATE TABLE product_categories (
    product_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### product_variants
Product variations (size, color, etc.)

```sql
CREATE TABLE product_variants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(100) NOT NULL,
    name VARCHAR(255) NULL,              -- e.g., "Large / Red"
    price DECIMAL(10, 2) NULL,           -- Override product price
    compare_at_price DECIMAL(10, 2) NULL,
    cost_per_item DECIMAL(10, 2) NULL,
    barcode VARCHAR(100) NULL,
    weight DECIMAL(8, 2) NULL,
    image_url VARCHAR(500) NULL,
    position INT DEFAULT 0,
    options JSON NOT NULL,                -- {"size": "L", "color": "Red"}
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uk_product_sku (product_id, sku),
    INDEX idx_product_id (product_id),
    INDEX idx_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### product_images
Product media

```sql
CREATE TABLE product_images (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL,
    url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) NULL,
    position INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### product_attributes
Custom product attributes

```sql
CREATE TABLE product_attributes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    type ENUM('text', 'number', 'boolean', 'select', 'multiselect') DEFAULT 'text',
    values JSON NULL,                    -- Predefined values for select types
    is_filterable BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_slug (store_id, slug),
    INDEX idx_store_id (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### product_attribute_values
Product attribute values

```sql
CREATE TABLE product_attribute_values (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    attribute_id BIGINT UNSIGNED NOT NULL,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES product_attributes(id) ON DELETE CASCADE,
    UNIQUE KEY uk_product_attribute (product_id, attribute_id),
    INDEX idx_product_id (product_id),
    INDEX idx_attribute_id (attribute_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Inventory Tables

### inventories
Product inventory tracking

```sql
CREATE TABLE inventories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    quantity INT NOT NULL DEFAULT 0,
    reserved_quantity INT NOT NULL DEFAULT 0,  -- Reserved during checkout
    available_quantity INT GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,
    low_stock_threshold INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    UNIQUE KEY uk_product_variant_warehouse (product_id, variant_id, warehouse_id),
    INDEX idx_store_id (store_id),
    INDEX idx_product_id (product_id),
    INDEX idx_available_quantity (available_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### warehouses
Warehouse/location management

```sql
CREATE TABLE warehouses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY uk_store_code (store_id, code),
    INDEX idx_store_id (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### stock_movements
Inventory movement history

```sql
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    inventory_id BIGINT UNSIGNED NOT NULL,
    type ENUM('purchase', 'sale', 'return', 'adjustment', 'damage', 'lost') NOT NULL,
    quantity INT NOT NULL,                -- Positive or negative
    reference_type VARCHAR(100) NULL,     -- Order, PurchaseOrder, etc.
    reference_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_store_id (store_id),
    INDEX idx_inventory_id (inventory_id),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Promotion & Discount Tables

### promotions
Promotion campaigns

```sql
CREATE TABLE promotions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('percentage', 'fixed', 'buy_x_get_y', 'bundle', 'free_shipping') NOT NULL,
    value DECIMAL(10, 2) NULL,            -- Percentage or fixed amount
    status ENUM('draft', 'active', 'scheduled', 'expired', 'inactive') DEFAULT 'draft',
    priority INT DEFAULT 0,                -- For stacking/conflict resolution
    is_stackable BOOLEAN DEFAULT FALSE,
    usage_limit INT NULL,                  -- Total usage limit
    usage_limit_per_customer INT NULL,
    minimum_purchase_amount DECIMAL(10, 2) NULL,
    maximum_discount_amount DECIMAL(10, 2) NULL,
    applies_to ENUM('all', 'specific_products', 'specific_categories', 'specific_customers') DEFAULT 'all',
    applies_to_ids JSON NULL,              -- Product/Category/Customer IDs
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_store_id (store_id),
    INDEX idx_status (status),
    INDEX idx_dates (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### coupons
Coupon codes

```sql
CREATE TABLE coupons (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    promotion_id BIGINT UNSIGNED NULL,     -- Link to promotion
    code VARCHAR(50) NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    usage_limit INT NULL,
    used_count INT DEFAULT 0,
    usage_limit_per_customer INT NULL,
    minimum_purchase_amount DECIMAL(10, 2) NULL,
    maximum_discount_amount DECIMAL(10, 2) NULL,
    applies_to ENUM('all', 'specific_products', 'specific_categories') DEFAULT 'all',
    applies_to_ids JSON NULL,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL,
    UNIQUE KEY uk_store_code (store_id, code),
    INDEX idx_store_id (store_id),
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### coupon_usages
Coupon usage tracking

```sql
CREATE TABLE coupon_usages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    coupon_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    discount_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_coupon_id (coupon_id),
    INDEX idx_order_id (order_id),
    INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### offers
Special offers (BOGO, bundles, etc.)

```sql
CREATE TABLE offers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('buy_x_get_y', 'bundle', 'quantity_discount', 'tiered_discount') NOT NULL,
    status ENUM('active', 'inactive', 'scheduled') DEFAULT 'active',
    priority INT DEFAULT 0,
    config JSON NOT NULL,                  -- Type-specific configuration
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_store_id (store_id),
    INDEX idx_status (status),
    INDEX idx_dates (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Customer Tables

### customers
Customer accounts

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,                    -- REQUIRED: Primary login method
    email VARCHAR(255) NOT NULL,                   -- REQUIRED: Order confirmations
    password VARCHAR(255) NULL,                    -- NULL for guest customers
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birthday DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    phone_verified_at TIMESTAMP NULL,              -- Track phone verification
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
    INDEX idx_phone (phone),                        -- For phone-based login (priority)
    INDEX idx_email (email),                        -- For email-based login (fallback)
    INDEX idx_store_id (store_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: Phone numbers are MANDATORY for checkout and account creation
-- Phone numbers stored in E.164 format (+12025551234)
-- See docs/18-phone-authentication-strategy.md
```

### customer_addresses
Customer shipping/billing addresses

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
    phone VARCHAR(20) NOT NULL,                    -- REQUIRED for delivery contact
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: Phone number REQUIRED for all addresses (delivery contact)
```

Due to character limits, I'll continue with Order tables in the next section...

### orders
Main orders table

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'partially_refunded') DEFAULT 'pending',
    fulfillment_status ENUM('unfulfilled', 'partial', 'fulfilled') DEFAULT 'unfulfilled',
    currency VARCHAR(3) DEFAULT 'USD',
    subtotal DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    shipping_amount DECIMAL(10, 2) DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    coupon_code VARCHAR(50) NULL,
    customer_note TEXT NULL,
    admin_note TEXT NULL,
    
    -- Payment fields (manual payment support)
    payment_method VARCHAR(100) NULL,           -- 'manual', 'bank_transfer', 'cash_on_delivery', 'card', etc.
    paid_at TIMESTAMP NULL,                     -- When payment was marked as received
    paid_by_user_id BIGINT UNSIGNED NULL,       -- Which admin/vendor marked it as paid
    payment_notes TEXT NULL,                    -- Internal notes (reference numbers, etc.)
    payment_proof_url VARCHAR(500) NULL,        -- Customer uploaded payment proof
    
    billing_address_id BIGINT UNSIGNED NULL,
    shipping_address_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    placed_at TIMESTAMP NULL,
    confirmed_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_store_id (store_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_paid_at (paid_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: Manual payment system active. See docs/17-payment-strategy.md for details.
-- Payment gateway integration (Stripe, PayPal, etc.) planned for Phase 3+
```

### order_items
Order line items

```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,         -- Price at time of order
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    product_snapshot JSON NOT NULL,         -- Product details at time of order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### payments
Payment transaction records

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    transaction_id VARCHAR(255) NULL,               -- Gateway transaction ID (null for manual)
    gateway VARCHAR(50) NOT NULL DEFAULT 'manual',  -- 'manual', 'stripe', 'paypal', 'razorpay', etc.
    payment_method VARCHAR(50) NOT NULL,            -- 'bank_transfer', 'cash', 'card', 'upi', etc.
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    failure_reason TEXT NULL,
    metadata JSON NULL,                              -- Store reference numbers, gateway data, etc.
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_store_id (store_id),
    INDEX idx_order_id (order_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_gateway (gateway),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: Currently supports manual payments (gateway = 'manual')
-- Automated payment gateways (Stripe, PayPal) planned for Phase 3+
-- See docs/17-payment-strategy.md for implementation details
```

## Indexes Summary

Key indexes for performance:

1. **Foreign Keys**: All foreign key columns
2. **Tenant Isolation**: `store_id` on all tenant tables
3. **Status Fields**: Order status, product status, etc.
4. **Search**: Fulltext on product name/description
5. **Dates**: Created_at, starts_at, ends_at for time-based queries
6. **Unique Constraints**: Email, slug, SKU combinations

## Sample Queries

### Get active products for a store
```sql
SELECT p.*, i.available_quantity
FROM products p
LEFT JOIN inventories i ON i.product_id = p.id
WHERE p.store_id = 1
  AND p.status = 'active'
  AND p.deleted_at IS NULL
ORDER BY p.created_at DESC;
```

### Get orders with customer details
```sql
SELECT o.*, c.email, c.first_name, c.last_name
FROM orders o
INNER JOIN customers c ON c.id = o.customer_id
WHERE o.store_id = 1
  AND o.status IN ('pending', 'confirmed')
ORDER BY o.created_at DESC;
```

### Apply promotions to cart
```sql
SELECT p.*
FROM promotions p
WHERE p.store_id = 1
  AND p.status = 'active'
  AND (p.starts_at IS NULL OR p.starts_at <= NOW())
  AND (p.ends_at IS NULL OR p.ends_at >= NOW())
  AND (p.usage_limit IS NULL OR p.used_count < p.usage_limit)
ORDER BY p.priority DESC;
```

## Migration Strategy

1. Create base tables (stores, users, roles, permissions)
2. Create product-related tables
3. Create inventory tables
4. Create promotion tables
5. Create customer and order tables
6. Add indexes
7. Seed master data (permissions, default roles)

## Next Steps

1. Review [API Design](04-api-design.md)
2. Review [Multi-Tenancy Strategy](07-multi-tenancy.md)
3. Create Laravel migrations
